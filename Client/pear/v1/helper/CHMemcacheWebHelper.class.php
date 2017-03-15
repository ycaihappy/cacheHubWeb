<?php

class CHMemcacheWebHelper extends CHWebHelper {

    public static $_memcache = null;

    public  function connect() {
        if (self::$_memcache === null) {
            self::$_memcache = new Memcache();
            // connect to memcached server
            $memcacheConfig = $this->cacheConfig;
            $conn =  self::$_memcache->connect($memcacheConfig['host'],$memcacheConfig['port']);
            if(!$conn) {
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},connect faild!,", __FILE__, __LINE__);
                return false;
            }
        }
        return self::$_memcache;
    }
    public function get($cacheKey) {
        $key = $this->buildKey($cacheKey);
        if(!$key){
            return false;
        }
        $memcacheConn = $this->connect();
        $rs = $memcacheConn->get($key);
        return $rs;
    }

    public function mget($cacheKeys){

        /** modify by stephen. */
        if (empty($cacheKeys)) return false;

        $returnData = array();

        if (is_array($cacheKeys)) {
           $realLongKey = array();
           foreach ($cacheKeys as $short_key) {
               $realLongKey[$short_key] = $this->buildKey($short_key);
           }
           if (empty($realLongKey)) return false;
           $memcacheConn = $this->connect();
           $result = $memcacheConn->get(array_values($realLongKey));
           $origKeys = array_flip($realLongKey);
           foreach ($origKeys as $key=>$val) {
                $returnData[$val] = (isset($result[$key])) ? $result[$key] : null;
           }
        }
        else {
           $returnData = $this->get($cacheKeys);
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
        $memcacheConn = $this->connect();
        $rs = $memcacheConn->set($key, $cacheData, $ttl);
        if(!$rs){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},put failure,key:{$key},", __FILE__, __LINE__);
            return false;
        }
        return $rs;
    }
    
}
