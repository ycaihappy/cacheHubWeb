<?php
class CHWebFactory {

    public static $objs;

    public static function getObj($applicationName, $cacheGroupName, $cacheConfig, $type){
        $className = 'CH'.$type.'WebHelper';
        if(empty(self::$objs[$applicationName][$cacheGroupName][$className])){
            if(!class_exists($className)){
                CacheLog::error($applicationName, $cacheGroupName, "{$className} class does not exist", __FILE__, __LINE__);
                $obj = false;
            }else{
                $config_id = 'default';
                if(!empty($cacheConfig['config_id'])){
                    $config_id = $cacheConfig['config_id'];
                    if(empty(CacheHubWeb::$appConfigs[$applicationName][$type][$config_id])){
                        $config_id = 'default';
                    }
                }
                if(!empty(CacheHubWeb::$appConfigs[$applicationName][$type][$config_id])){
                    $type_id_config = CacheHubWeb::$appConfigs[$applicationName][$type][$config_id];
                    $type_config = array_merge($type_id_config, $cacheConfig);
                }
                $obj = new $className;
                $obj->cacheGroupName = $cacheGroupName;
                $obj->type = $type;
                $obj->applicationName = $applicationName;
                $obj->cacheConfig = $type_config;
                self::$objs[$applicationName][$cacheGroupName][$className] = $obj;
            }
        }
        return self::$objs[$applicationName][$cacheGroupName][$className];
    }
    
}
