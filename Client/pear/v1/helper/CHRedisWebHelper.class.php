<?php

class CHRedisWebHelper extends CHWebHelper {

    public static $redisObjs;
    public static $currentRedisSelect;

    public function get($cacheKey) {
        $key = $this->buildKey($cacheKey);
        if(!$key){
            return false;
        }
        $connect = $this->connect();
        if(!$connect){
            return false;
        }
        $config = $this->cacheConfig;
        $json = $connect->get($key);
//        $connect->close();
        if(!$json){
            //CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},get failure,{$config['host']}:{$config['port']},db:{$config['db']},key:{$key}", __FILE__, __LINE__);
            return false;
        }
        return $json;
    }

    public function mget($cacheKeys) {
        $buildKey = array();
        if(!empty($cacheKeys)) {
            foreach($cacheKeys as $key) {
                $buildKey[] = $this->buildKey($key);
            }
        }else{
            $buildKey[] = $this->buildKey(null);
        }
        $connect = $this->connect();
        if(!$connect) {
            return $this->filterData($cacheKeys);
        }
        $data = $connect->mget($buildKey);
        if(empty($data)) {
            return $this->filterData($cacheKeys);
        }
        $returnData = array();
        foreach($data as $k=>$v) {
            if(!empty($v)) {
                $valueData = json_decode($v,true);
                if(!empty($cacheKeys)) {
                    $returnData[$cacheKeys[$k]] = $valueData;
                }else{
                    $returnData = $valueData;
                }
            }
        }
        $returnData = $this->filterData($cacheKeys,$returnData);
        return $returnData;
    }
    public function mput($cacheKeys,$cacheData) {
         if(empty($cacheData)) {
            return false;
        } 
         if(!empty($cacheKeys)) {
             foreach($cacheKeys as $kName) {
                 if(!empty($cacheData[$kName])) {
                     $this->put($kName,json_encode($cacheData[$kName]));
                 }
             }
         }else {
             $this->put(null,json_encode($cacheData));
         }
    }
    public function put($cacheKey, $cacheData) {
        $key = $this->buildKey($cacheKey);
        if(!$key){
            return false;
        }
        $connect = $this->connect();
        if(!$connect){
            return false;
        }
        $ttl = $this->ttl();
        if(!empty($ttl)){
            $rs = $connect->SETEX($key, $ttl, $cacheData);
        }else{
            $rs = $connect->set($key, $cacheData);
        }
//        $connect->close();
        if(!$rs){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},put failure,key:{$key},", __FILE__, __LINE__);
            return false;
        }
        return $rs;
    }

    public function connect(){
        $config = $this->cacheConfig;
        $redisKey = $config['host'].'_'.$config['port'];
        if(empty(self::$redisObjs[$redisKey])){
            self::$redisObjs[$redisKey]  = new Redis();
            $con = self::$redisObjs[$redisKey]->pconnect($config['host'], $config['port']);
            if(!$con){
                CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},{$config['host']}:{$config['port']} connect error,", __FILE__, __LINE__);
                return false;
            }
        }
        try{
            self::$redisObjs[$redisKey]->select($config['db']);
        }catch(Exception $e) {
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},{$config['host']}:{$config['port']},db:{$config['db']},connect error:".($e->getMessage()), __FILE__, __LINE__);
            return false;
        }
        return self::$redisObjs[$redisKey];
    }

}
