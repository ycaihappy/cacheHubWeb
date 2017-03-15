<?php
include_once CACHEHUB_WEB_PATH."/WebCacheClient.class.php";
class CHDBWebHelper extends CHWebHelper {

    public $WebCacheClient;

    public function get($cacheKey){
        $ttl = $this->ttl();
        $WebCacheClient = $this->WebCacheClient();
        if(!$WebCacheClient){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},get failure,WebCacheClient getInstance failure", __FILE__, __LINE__);
            return false;
        }
        $params = array(
            'ttl' => $ttl,
        );
        //mget call type
        $cacheData = array();
        if(is_array($cacheKey) && count($cacheKey)) {
            /**
             * parmas['mget_group']  this is mget cache group
             * added by mark.liu
             */
            $params ['is_mget'] = 1;
        }   
        $cacheData = $WebCacheClient->build($cacheKey, $params);
    
        if(!$cacheData){
            //关闭客户端日志报错
            //CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},get failure, cacheData error", __FILE__, __LINE__);
            return false;
        }
        return $cacheData;
    }

    public function mget($cacheKeys){
        $dataList = array();
        $cacheData = $this->get($cacheKeys);
        if(!empty($cacheData)) {
            foreach($cacheData as $key=>$val) {
                if(!empty($val)) {
                    $dataList[$key] = $val;
                }
            }
        }
        $returnData = $this->filterData($cacheKeys,$dataList);
        return $returnData;
    }
    public function put($cacheKey, $cacheData){
        return false;
    }
    public function mput($cacheKeys,$cacheData) {
        return false;
    }

    public function WebCacheClient(){
        if(empty($this->WebCacheClient)){
            $this->WebCacheClient = WebCacheClient::getInstance($this->applicationName, $this->cacheGroupName);
        }
        return $this->WebCacheClient;
    }
    
}
