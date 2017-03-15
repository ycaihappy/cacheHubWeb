<?php

class WebCacheConfig {

    public static function path() {
        $path = CACHEHUB_CONF_PATH."/WebCacheBuildWorker.cfg.php";
        return $path;
    }

    public static function getPublicConfig() {
        $configFile = self::path();
        if(file_exists($configFile)) {
            return require($configFile);
        }else {
            return false;
        }
    }

    public static function getBuilderConfig($application,$cacheGroupName) {
        $builder_config_file = CACHEHUB_CONF_PATH.'/'.$application.'/'.$cacheGroupName.'.conf.php';
        if(!file_exists($builder_config_file)) {
            WorkerLog::error($application, $cacheGroupName,'Builder Config File Not Found!',__FILE__,__LINE__);
            return false;
        }
        $builder_config_arr = include($builder_config_file);
        if(!is_array($builder_config_arr) || empty($builder_config_arr)) {
             WorkerLog::error($application, $cacheGroupName,'Builder Config File Format Error!',__FILE__,__LINE__);
            return false;
        }
        return $builder_config_arr;

    }

    

}
