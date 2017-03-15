<?php

class CHXCacheWebHelper extends CHWebHelper {

    public $no_json = true;
    
    public function get($cacheKey) {
        $key = $this->buildKey($cacheKey);
        if(!$key){
            return false;
        }
        $rs = xcache_get($key);
        return $rs;
    }

    public function mget($cacheKeys){
        $returnData = array(); 
        if(!empty($cacheKeys)) { 
            foreach($cacheKeys as $k) {
                $data =  $this->get($k);
                if(!empty($data)) {
                    $returnData[$k] = $data;
                }
            }
        }else{
            $returnData = $this->get(null);
        }
        $returnData = $this->filterData($cacheKeys,$returnData);
        return $returnData;
    }
    public function mput($cacheKeys,$cacheData) {
        if(empty($cacheData)) {
            return false;  
        }
        if(!empty($cacheKeys)) {
            foreach($cacheKeys as $cacheKey) {
                if(!empty($cacheData[$cacheKey])) {
                    $this->put($cacheKey,$cacheData[$cacheKey]);
                }
            }
        }else {
            $this->put(null,$cacheData);
        }
    }
    public function put($cacheKey, $cacheData) {
        $key = $this->buildKey($cacheKey);
        if(!$key){
            return false;
        }
        $ttl = $this->ttl(300);
        $rs = xcache_set($key, $cacheData, $ttl);
        if(!$rs){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},put failure,key:{$key},", __FILE__, __LINE__);
            return false;
        }
        return $rs;
    }
    
}
