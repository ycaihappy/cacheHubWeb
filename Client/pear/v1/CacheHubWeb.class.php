<?php
defined('CACHEHUB_WEB_PATH') or define("CACHEHUB_WEB_PATH", dirname(__FILE__));
include_once CACHEHUB_WEB_PATH.'/CacheLog.class.php';
include_once CACHEHUB_WEB_PATH.'/CHWebFactory.class.php';
include_once CACHEHUB_WEB_PATH.'/helper/CHWebHelper.class.php';
include_once CACHEHUB_WEB_PATH.'/helper/CHFileWebHelper.class.php';
include_once CACHEHUB_WEB_PATH.'/helper/CHXCacheWebHelper.class.php';
include_once CACHEHUB_WEB_PATH.'/helper/CHRedisWebHelper.class.php';
include_once CACHEHUB_WEB_PATH.'/helper/CHDBWebHelper.class.php';
include_once CACHEHUB_WEB_PATH.'/helper/CHMemcacheWebHelper.class.php';
class CacheHubWeb {

    public static $instances;
    public static $appConfigs;
    public static $groupConfigs;
    
    public $cacheFound;
    public $applicationName;
    public $cacheGroupName;

    public static function getInstance($applicationName=null, $cacheGroupName=null){
        if(empty($applicationName)){
            CacheLog::error('null', 'null', '$applicationName is empty', __FILE__, __LINE__);
            throw new Exception('$applicationName is empty'); 
        }
        if(empty($cacheGroupName)){
            CacheLog::error($applicationName, 'null', '$cacheGroupName is empty', __FILE__, __LINE__);
            throw new Exception('$cacheGroupName is empty'); 
        }
        if(empty(self::$instances[$applicationName][$cacheGroupName])){
            $obj = new self;
            $obj->applicationName = $applicationName;
            $obj->cacheGroupName = $cacheGroupName;
            $config_path = rtrim(CACHEHUB_CONF_PATH, '/');
            if(empty(self::$appConfigs[$applicationName])){
                $config_file = $config_path.'/'.$applicationName.'.web.conf.php';
                if(!is_file($config_file)){
                    CacheLog::error($applicationName, $cacheGroupName, "app config file '{$config_file}' is not exists", __FILE__, __LINE__);
                    throw new Exception("app config file '{$config_file}' is not exists"); 
                }
                self::$appConfigs[$applicationName] = include($config_file);;
            }
            $group_conf_file = $config_path.'/'.$applicationName.'/'.$cacheGroupName.'.conf.php';
            if(!is_file($group_conf_file)){
                CacheLog::error($applicationName, $cacheGroupName, "group config file '{$group_conf_file}' is not exists", __FILE__, __LINE__);
                throw new Exception("group config file '{$group_conf_file}' is not exists"); 
            }
            $group_conf = include($group_conf_file);
            self::$groupConfigs[$applicationName][$cacheGroupName] = $group_conf;
            self::$instances[$applicationName][$cacheGroupName] = $obj;
        }
        return self::$instances[$applicationName][$cacheGroupName];
    }
    public static function mget($applicationName=null,$cacheGroup) {
        if(empty($applicationName)){
            CacheLog::error('null', 'null', '$applicationName is empty', __FILE__, __LINE__);
            throw new Exception('$applicationName is empty'); 
            return false;
        }
        if(!is_array($cacheGroup) || count($cacheGroup)<=0) {
            CacheLog::error(null, null, "mget param is empty", __FILE__, __LINE__);
            return false;
        }
    
        $returnData = array();
        foreach($cacheGroup as $group=>$cacheKeys) {
            $_this =  self::getInstance($applicationName,$group);
            if(!isset(self::$groupConfigs[$_this->applicationName][$_this->cacheGroupName])) {
                CacheLog::error(null, null, "getInstance not include cacehGroup:".$group, __FILE__, __LINE__);
                continue;
            }
            $group_conf = self::$groupConfigs[$_this->applicationName][$_this->cacheGroupName];
            $caches = $group_conf['caches'];
            if(empty($caches)){
                CacheLog::error($_this->applicationName, $_this->cacheGroupName, "caches is empty", __FILE__, __LINE__);
                continue;
            }
            if(isset($group_conf['cacheKey']) && $group_conf['cacheKey'] == 1 && count($cacheKeys)<0){
                CacheLog::error($_this->applicationName, $_this->cacheGroupName, "cacheKey is empty", __FILE__, __LINE__);
                continue;
            }
            $cacheKeys = is_array($cacheKeys) ?  array_unique($cacheKeys) : null;
            
            $needUpdate = array();
            $failData = array();
            $succData = array();
            foreach($caches as $type => $cacheConfig){
                $obj = CHWebFactory::getObj($_this->applicationName, $_this->cacheGroupName, $cacheConfig, $type);
                if(!$obj){
                    continue;
                }
                $cacheData = $obj->mget($cacheKeys);
                $cacheSuccData = $cacheData['succ'];
                $cacheFailData = $cacheData['fail'];
                if(!empty($cacheSuccData)) {
                    $succData += $cacheSuccData;
                    if(!empty($cacheFailData)){
                        $needUpdate[] = $obj;
                        $failData[] = $cacheFailData;
                        $cacheKeys = $cacheFailData;
                    }else{
                        break; 
                    }
                }else{
                    $needUpdate[] = $obj;
                    $failData[] = $cacheFailData;
                }
            }
            //cache types put data
            if(!empty($succData) && !empty($needUpdate)) {
                foreach($needUpdate as $k=>$updateObj) {
                    $updateObj->mput($failData[$k],$succData);
                }
            }
            //fail data input succData
            if(!empty($failData)) {
                foreach(end($failData) as $failId) {
                    $succData+=(array($failId=>null));
                }
            }
            $returnData[$group] = $succData; 
        }
        //sort return
        $dataList = array();
        foreach($cacheGroup as $groupKey=>$valuekeys) {
            if(!empty($valuekeys)) {
                foreach($valuekeys as $key) {
                    $dataList[$groupKey][$key] = $returnData[$groupKey][$key];  
                }
            }else{
                $dataList[$groupKey] = $returnData[$groupKey];
            }
        }
        return $dataList;
    }
    public function get($cacheKey=null, $whereCall=null){
        $group_conf = self::$groupConfigs[$this->applicationName][$this->cacheGroupName];
        if(empty($group_conf['caches'])){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "caches is empty", __FILE__, __LINE__);
            return false;
        }
        $caches = $group_conf['caches'];
        if(isset($group_conf['cacheKey']) && $group_conf['cacheKey'] == 1 && (is_null($cacheKey) || $cacheKey == '')){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "cacheKey is empty", __FILE__, __LINE__);
            return false;
        }
        $needUpdate = array();
        $cacheData = null;
        $jsonCacheData = null;
        foreach($caches as $type => $cacheConfig){
            $obj = CHWebFactory::getObj($this->applicationName, $this->cacheGroupName, $cacheConfig, $type);
            if(!$obj){
                continue;
            }
            $getCacheData = $obj->get($cacheKey);
            if($getCacheData){
                CacheLog::debug($this->applicationName, $this->cacheGroupName, $cacheKey, $type, 'succ', $whereCall);
                if(is_string($getCacheData)){
                    $jsonCacheData = $getCacheData;
                    $decodeCacheData = json_decode($jsonCacheData, true);
                    if($decodeCacheData){
                        $cacheData = $decodeCacheData;
                    }else{
                        $cacheData = $jsonCacheData;
                    }
                }else{
                    $cacheData = $getCacheData;
                }
                $this->cacheFound = $type;
                break;
            }else{
                CacheLog::debug($this->applicationName, $this->cacheGroupName, $cacheKey, $type, 'error', $whereCall);
                array_push($needUpdate, $obj);
            }
        }
        if($cacheData){
            foreach($needUpdate as $updateObj){
                if(!empty($updateObj->no_json) && $updateObj->no_json){
                    $updateObj->put($cacheKey, $cacheData);
                }else{
                            $jsonCacheData = json_encode($cacheData);
                    $updateObj->put($cacheKey, $jsonCacheData);
                }
            }
        }
        return $cacheData;
    }

    public static function clear($applicationName=null){
        if(empty($applicationName)){
            self::$instances = null;
        }else{
            self::$instances[$applicationName] = null;
        }
    }

    public function __destruct() {
        CHWebFactory::$objs = null;
        self::$groupConfigs = null;
        self::$appConfigs = null;
    }
    
}
