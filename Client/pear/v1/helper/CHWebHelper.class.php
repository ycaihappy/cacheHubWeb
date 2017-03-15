<?php
include_once CACHEHUB_WEB_PATH.'/helper/ICHWebHelper.interface.php';
abstract class CHWebHelper implements ICHWebHelper {

    public $applicationName;
    public $cacheGroupName;
    public $cacheConfig;
    public $keys;
    public $type;

    public function buildKey($cacheKey, $key_field_name='key'){
        if(empty($this->keys[$cacheKey])){
            if(!empty($this->cacheConfig[$key_field_name])){
                $key_str = $this->cacheConfig[$key_field_name];
            }else{
                $has_cacheKey = isset(CacheHubWeb::$groupConfigs[$this->applicationName][$this->cacheGroupName]['cacheKey']) ? CacheHubWeb::$groupConfigs[$this->applicationName][$this->cacheGroupName]['cacheKey'] : 0;
                if($has_cacheKey == 1 && !empty($this->cacheConfig[$key_field_name."_1"])){
                    $key_str = $this->cacheConfig[$key_field_name."_1"];
                }elseif($has_cacheKey != 1 && !empty($this->cacheConfig[$key_field_name."_0"])){
                    $key_str = $this->cacheConfig[$key_field_name."_0"];
                }else{
                    CacheLog::error($this->applicationName, $this->cacheGroupName, 'type:'.$this->type.',function buildKey,config '.$key_field_name.' empty', __FILE__, __LINE__);
                return false;
                }
            }
            if(is_null($cacheKey) || $cacheKey == ''){
                $rs = strpos($key_str, '%cacheKey%');
                if($rs){
                    CacheLog::error($this->applicationName, $this->cacheGroupName, 'type:'.$this->type.',"'.$key_str.'" cacheKey is empty', __FILE__, __LINE__);
                    return false;
                }
            }
            $key_str = str_replace('%cacheKey%', $cacheKey, $key_str);
            $key_str = str_replace('%cacheGroupName%', $this->cacheGroupName, $key_str);
            $this->keys[$cacheKey] = $key_str;
        }
        return $this->keys[$cacheKey];
    }

    public function ttl($default=0){
        $ttl = $default;
        if(isset($this->cacheConfig['ttl'])){
            $ttl = $this->cacheConfig['ttl'];
        }
        return $ttl;
    }
    public function filterData($keys,$data=array()) {
        $returnData = array('succ'=>$data,'fail'=>array());
        if(empty($keys)) {
            return $returnData;
        }
        if(empty($data)) {
            $returnData['fail'] = $keys;
            return $returnData;
        }
        foreach($keys as $k) {
            if(!array_key_exists($k,$data)) {
                $returnData['fail'][] = $k;
            }
        }
        return $returnData;
    }
    
}
