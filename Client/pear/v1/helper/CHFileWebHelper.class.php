<?php

class CHFileWebHelper extends CHWebHelper {

    public $cacheKeys;

    public function get($cacheKey) {
        $cacheKey = $this->buildCacheKey($cacheKey);
        $key = $this->buildKey($cacheKey, 'filepath');
        if(!$key){
            return false;
        }
        if(!is_file($key)){
            //CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},function get,{$key} not exists", __FILE__, __LINE__);
            return false;
        }
        if(!is_readable($key)){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},function get,{$key} not readable", __FILE__, __LINE__);
            return false;
        }
        if(!empty($this->cacheConfig['ttl'])){
            $ttl = $this->cacheConfig['ttl'];
            $now = time();
            $filemtime = filemtime($key);
            if($now >= ($ttl + $filemtime)){
                $is_unlink = true;
                if(!empty($this->cacheConfig['update_rate']) && $this->cacheConfig['update_rate'] > 1){
                    $update_rate = $this->cacheConfig['update_rate'];
                    $rand = rand(1, $update_rate);
                    $is_unlink = ($rand == 1);
                }
                if($is_unlink){
                    $rs = unlink($key);
                        if(!$rs){
                        CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},function get,{$key} unlink error", __FILE__, __LINE__);
                    }
                    return false;
                }
            }
        }
        $json = file_get_contents($key);
        if(!$json){
            //CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},get failure,key:{$key}", __FILE__, __LINE__);
            return false;
        }
        return $json;
    }
    public function mget($cacheKeys) {
        $returnData = array();
        $cacheKeyArr = empty($cacheKeys) ? array(null) : $cacheKeys;
        foreach($cacheKeyArr as $keys) {
            $data = $this->get($keys);
            if(!empty($data)) {
                $decodeData = json_decode($data,true);
                if(!empty($keys)){
                    $returnData[$keys] = $decodeData;
                }else{
                    $returnData = $decodeData;
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
            foreach($cacheKeys as $cacheKey) {
                if(!empty($cacheData[$cacheKey])){
                    $this->put($cacheKey,json_encode($cacheData[$cacheKey]));
                }
            }
        }else {
            $this->put(null,json_encode($cacheData));
        }
    }

    public function put($cacheKey, $cacheData) {
        $cacheKey = $this->buildCacheKey($cacheKey);
        $key = $this->buildKey($cacheKey, 'filepath');
        if(!$key){
            return false;
        }
        $dir = dirname($key);
        if(is_dir($dir)){
            if(!is_writeable($dir)){
                CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},put failure,{$dir} is not writeable", __FILE__, __LINE__);
                return false;
            }
        }else{
            $mkdir = mkdir($dir, 0775, true);
            if(!$mkdir){
                CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},put failure,{$dir} mkdir failure", __FILE__, __LINE__);
                return false;
            }
        }
        if(is_file($key) && !is_writable($key)){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},function put,{$key} is not writable", __FILE__, __LINE__);
            return false;
        }
        $rs = file_put_contents($key, $cacheData);
        if(!$rs){
            CacheLog::error($this->applicationName, $this->cacheGroupName, "type:{$this->type},put failure,key:{$key}", __FILE__, __LINE__);
            return false;
        }
        $file_group = empty(CacheHubWeb::$appConfigs[$this->applicationName]['file_group']) ? 'tdss' : CacheHubWeb::$appConfigs[$this->applicationName]['file_group'];
        chgrp($dir, $file_group);
        chmod($dir, 0775);
        return true;
    }
    
    public function buildCacheKey($cacheKey){
        if(is_null($cacheKey) || $cacheKey == ''){
            return $cacheKey;
        }
        if(empty($this->cacheKeys[$cacheKey])){
            $new_key = $cacheKey;
            $useBigTable = false;
            if(isset($this->cacheConfig['useBigTable'])){
                $useBigTable = $this->cacheConfig['useBigTable'];
            }
            if($useBigTable){
                $key_str = $cacheKey;
                $dir_str = '';
                $key_str_arr = explode('_', $cacheKey);
                if(count($key_str_arr) > 1){
                    $key_str = array_pop($key_str_arr);
                    $dir_str = implode('_', $key_str_arr).'/';
                }
                $len = strlen($key_str);
                $str = $key_str;
                if($len < 4){
                    $str = sprintf("%04d", $key_str);
                }
                $dir_one = substr($str, 0, 2);
                $dir_two = substr($str, 2, 2);
                $new_key = $dir_str.$dir_one.'/'.$dir_two.'/'.$key_str;
            }
            $isSplitKey = isset($this->cacheConfig['isSplitKey']) ? $this->cacheConfig['isSplitKey'] : true;
            if($isSplitKey == true){
                $new_key = str_replace('_', '/', $new_key);
            }
            $this->cacheKeys[$cacheKey] = $new_key;
        }
        return $this->cacheKeys[$cacheKey];
    }

}
