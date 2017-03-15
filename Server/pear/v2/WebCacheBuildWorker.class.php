<?php
defined('CACHEHUB_WEB_PATH') or define("CACHEHUB_WEB_PATH", dirname(__FILE__));
defined('CACHEHUB_CONF_PATH') or define("CACHEHUB_CONF_PATH", '/data/conf/cacheHubWeb/server/v1/config');
include_once CACHEHUB_WEB_PATH."/WebCacheConfig.class.php";
include_once CACHEHUB_WEB_PATH."/MemcacheHelper.class.php";
include_once CACHEHUB_WEB_PATH."/WorkerLog.class.php";
include_once CACHEHUB_WEB_PATH."/PDODB.class.php";
require_once CACHEHUB_WEB_PATH."/builder/IWebCacheBuilder.iface.php";
require_once CACHEHUB_WEB_PATH."/builder/CacheBuilder.class.php";

class WebCacheBuilderWorker
{

    public $gearmanWorker;
    public $config;

    public static function getInstance($gearman = false)
    {
        $obj = new self();
        $configObj = WebCacheConfig::getPublicConfig();
        $obj->config = $configObj;
        if ($gearman) {
            $gearmanConfig = $configObj['gearman'];
            $obj->gearmanWorker = new GearmanWorker();
            $obj->gearmanWorker->addServer($gearmanConfig['host'], $gearmanConfig['port']);
        }
        return $obj;

    }

    public function run($funcName)
    {
        $_this = $this;
        $this->gearmanWorker->addFunction("WebCacheBuild_" . $funcName, function ($job) use ($_this, $funcName) {
            $params = json_decode($job->workload(), true);
            $ch_token = $_this->config['ch_token'];
            if($params["cacheHubWeb_token"] != $ch_token){
                $arr = array(
                    'client' => $params["cacheHubWeb_token"],
                    'server' => $ch_token,
                );
                WorkerLog::error('null','null',"token can not match", __FILE__, __LINE__, $arr);
                return false;
            }
            
           
                    $application = $params['application'];
                    $cacheGroupName = $params['cacheGroupName'];
                    $cacheKeyId = $params['cacheKeyId'];
                    $cacheCfg = $params['cacheCfg'];

                    $result = $_this->build($application, $cacheGroupName, $cacheKeyId, $cacheCfg);
                
            
            return json_encode(array('result' => $result));
        });
        while ($this->gearmanWorker->work()) ;
    }


    public function build($application, $cacheGroupName, $cacheKeyId, $cacheCfg=array('ttl'=>30))
    {

        //0 if mget cache 
        if(is_array($cacheKeyId) && !empty($cacheKeyId) && !empty($cacheCfg['is_mget']) && false == in_array($cacheGroupName,$this->config['allow_mget_group'])) {
            return false;
        }
        
        if ($cacheCfg['ttl'] != 0) {
            //0.5 record cacheKeyId
            $callKeyId = $cacheKeyId;

            //0.2 if mget 1 key : same get()
            if(is_array($cacheKeyId) && count($cacheKeyId)==1) {
                $cacheKeyId = current($cacheKeyId);
            }


            //1  check worker is done
            $localCacheData = $this->checkDone($application, $cacheGroupName, $cacheKeyId);
            $returnCacheData = $this->getDoneData($localCacheData,$cacheKeyId,$callKeyId);


            if($cacheKeyId===false) {
                //echo "memcache cache...\n";
                return $returnCacheData;
            }

            if(is_array($callKeyId) && count($callKeyId)>1) {
                $hadCacheKeyIds = array_diff($callKeyId,$cacheKeyId);
            }

            //2 check worker is doing
            $taskLockRs = $this->checkDoing($application, $cacheGroupName, $cacheKeyId);
            $returnTaskRs = $this->getDoingData($taskLockRs,$cacheKeyId);

            if ($returnTaskRs) {
                //echo "doing:".implode(' ',$cacheKeyId)."\n";    //已有worker正在做,等待中..
                //loop 10 times get cache data
                for($i=1;$i<=10;$i++) {
                    $localCacheData = $this->checkDone($application, $cacheGroupName, $cacheKeyId);
                    $returnCacheData = $this->getDoneData($localCacheData,$cacheKeyId,$callKeyId);
                    if(empty($cacheKeyId)) {
                        return $returnCacheData;
                    }
                    sleep(1);
                }
                return false;
            }

            //3 set task lock
            $taskLockKeys = $this->getTastLockKey($application, $cacheGroupName, $cacheKeyId);
            if(!empty($taskLockKeys)) {
                foreach($taskLockKeys as $taskLockKey) {
                    MemcacheHelper::set($taskLockKey, 1, $cacheCfg['ttl']);            
                }
            }

        }

        //4 build data
        $publicCfg = $this->config;
        $builderCfg = WebCacheConfig::getBuilderConfig($application,$cacheGroupName);
        if(!$builderCfg) {
            return false;
        }   
      
        $builder_full_name = $builderCfg['builder'];
        $builder_name_parts = explode('.', $builder_full_name);
        $builder_clz = end($builder_name_parts);

        $builder_source_path = str_replace('.', '/', $builder_full_name) . ".class.php";
        $builder_base_path = isset($publicCfg['builder_path']) && !empty($publicCfg['builder_path']) ? $publicCfg['builder_path'] : CACHEHUB_WEB_PATH.'/builder';

        try{
            require_once rtrim($builder_base_path,'/').'/'.$builder_source_path;
            $builder = new $builder_clz;
            //echo "3.build:".implode(' ',(array)$cacheKeyId)."\n";
            $cacheData = $builder->build($application, $cacheGroupName, $cacheKeyId);

            if ($cacheCfg['ttl'] == 0) {
                return $cacheData;
            }
          
            //6.set cache to memcache
            $cacheKeys = $this->getLocalCacheKey($application, $cacheGroupName, $cacheKeyId);
            if(!empty($cacheKeys)) {
                if(is_array($callKeyId) && count($callKeyId)>1) {   //set mulit key
                    foreach($cacheKeys as $cacheKey=>$formatKey) {
                        MemcacheHelper::set($formatKey, $cacheData[$cacheKey], $cacheCfg['ttl']);  
                    }
                }else{  //set sigle key
                    $cacheKey  = current($cacheKeys);
                    MemcacheHelper::set($cacheKey,$cacheData,$cacheCfg['ttl']);
                }

            }

            $cacheData = is_bool($cacheData) ? array() : $cacheData;
            if(is_array($callKeyId) && count($callKeyId)>1 && !empty($hadCacheKeyIds)) {
                 $hadCacheData = $this->checkDone($application,$cacheGroupName,$hadCacheKeyIds);
                 $cacheData += $hadCacheData;
                 $cacheData = $this->sortcacheData($callKeyId,$cacheData);
            }

            if(is_array($callKeyId) && count($callKeyId)==1) {
                $sigleKey = current($callKeyId);
                $cacheData = array($sigleKey=>$cacheData);
            }

        }catch(Exception $ex){
            $cacheData = false;
            WorkerLog::error($application, $cacheGroupName, $ex->getMessage(),__FILE__,__LINE__);
        }

        //7.del memcache task lock key
        if(!empty($taskLockKeys)) {
            foreach($taskLockKeys as $taskLockKey) {
                 MemcacheHelper::delete($taskLockKey);               
            }
        }
        return $cacheData;

    }

    //get local cache key
    private function getLocalCacheKey($application, $cacheGroupName, $cacheKeyId)
    {
        $cacheKeyFormat = 'LCK_%s_%s_%s';
        $cacheKeys = $this->getFormatKeyIds($application,$cacheGroupName,$cacheKeyId,$cacheKeyFormat);
        return $cacheKeys;
   
    }

    //get tast lock key
    private function getTastLockKey($application, $cacheGroupName, $cacheKeyId)
    {
        $cacheKeyFormat = 'TLK_%s_%s_%s';
        $cacheKeys = $this->getFormatKeyIds($application,$cacheGroupName,$cacheKeyId,$cacheKeyFormat);
        return $cacheKeys;
    }


    //get format key ids
    private function getFormatKeyIds($application,$cacheGroupName,$cacheKeyId,$cacheKeyFormat) {/*{{{*/
        $cacheKeys = array();
        if(is_array($cacheKeyId) && count($cacheKeyId)>0) {
            foreach($cacheKeyId as $kID) {
                $cacheKeys[$kID] = sprintf($cacheKeyFormat,$application,$cacheGroupName,$kID);
            }
        }else{
            $kID = empty($cacheKeyId) ? 0 : $cacheKeyId;      
            $cacheKeys[$kID] = sprintf($cacheKeyFormat,$application,$cacheGroupName,$kID);
        }
        return $cacheKeys; 
    }/*}}}*/

    //check is done?
    private function checkDone($application, $cacheGroupName, $cacheKeyId)/*{{{*/
    { 
        $localCacheKeys = $this->getLocalCacheKey($application, $cacheGroupName, $cacheKeyId);
        $localCacheData = $this->getMemcacheKeys($localCacheKeys);
        return $localCacheData;      
    }/*}}}*/ 

    //check is doing ?
    private function checkDoing($application, $cacheGroupName, $cacheKeyId)/*{{{*/
    {
        $taskLockKeys = $this->getTastLockKey($application, $cacheGroupName, $cacheKeyId);
        $taskLockData = $this->getMemcacheKeys($taskLockKeys);
        return $taskLockData;
    }/*}}}*/ 

    //memcache get key
    private function getMemcacheKeys($keys) {/*{{{*/
        $returnData = array();
        if(!empty($keys)) {
            foreach($keys as $kID=>$kValue) {
                $mCache = MemcacheHelper::get($kValue);
                if(!empty($mCache)) {
                    $returnData[$kID] = $mCache;
                }
            }
        }
#        if (empty($returnData)) {
#            return false;
#        }
        return $returnData;
    }/*} }}*/
  

    //get done Data
    private function getDoneData($localCacheData,&$cacheKeyId,$callKeyId) {/*{{{*/
        if ($localCacheData != false) {
            $key = $cacheKeyId == NULL ? 0 : $cacheKeyId;
            if(is_array($cacheKeyId)==false && $localCacheData[$key]!=false) {
                $returnCacheData = is_array($callKeyId) ? $localCacheData : $localCacheData[$key];
                $cacheKeyId = false; 
                return $returnCacheData;
            }
            $localCacheDataKeys = array_keys($localCacheData);
            $cacheKeyId = array_diff($cacheKeyId,$localCacheDataKeys);
            if(empty($cacheKeyId)) {
                $cacheKeyId = false;
                return $localCacheData;
            }
        }
    }/*}}}*/

    //get doing data
    private function getDoingData($taskLockData,&$cacheKeyId) {/*{{{*/
        if ($taskLockData != false) {
            if(is_array($cacheKeyId)==false && $taskLockData[$cacheKeyId]!=false) {
                return true;
            }
             $taskLockDataKeys = array_keys($taskLockData);
             $doingCacheKeyId = array_diff($cacheKeyId,$taskLockDataKeys);
            if(empty($doingCacheKeyId)) {
               return $cacheKeyId; 
            }
            $cacheKeyId = $doingCacheKeyId;
            return false;
         }
         return false;
    }/*}}}*/

    //sort cache Data
    private function sortcacheData($sortKeys,$cacheData) {/*{{{*/
        $sortData = array();
        if(empty($cacheData)) {
            return $sortData;
        }
        foreach($sortKeys as $sortKey) {
            $data = $cacheData[$sortKey];
            $data = empty($data) ? array() : $data;
            $sortData[$sortKey] = $data;
        }
        return $sortData;
    }/*}}}*/

}

