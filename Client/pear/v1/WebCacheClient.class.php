<?php

class WebCacheClient {

    public $application;
    public $groupName;
    public static $gearManClient;

    public static function getInstance($application, $groupName) {
        $obj = new self();
        $obj->application = $application;
        $obj->groupName = $groupName;
        if (empty(self::$gearManClient)) {
            if (empty(CacheHubWeb::$appConfigs[$application]['gearman'])) {
                CacheLog::error($application, $groupName, "config gearman is empty", __FILE__, __LINE__);
            }
            $gconf = CacheHubWeb::$appConfigs[$application]['gearman'];
            self::$gearManClient = new GearmanClient();
            $rs = self::$gearManClient->addServer($gconf["host"], $gconf["port"]);
            if (!$rs) {
                CacheLog::error($application, $groupName, "gearman add server falure", __FILE__, __LINE__, $gconf);
                return false;
            }
           # $worker_timeout = empty($gconf['worker_timeout']) ? 3000 : $gconf['worker_timeout'];
           # self::$gearManClient->setTimeout($worker_timeout);
        }
        return $obj;
    }

    public function build($cacheKeyId = null, $cacheCfg = array(), $isbackground = false) {
        $arr['application'] = $this->application;
        $arr['cacheGroupName'] = $this->groupName;
        $arr['cacheKeyId'] = $cacheKeyId;
        $arr['cacheCfg'] = $cacheCfg;

        return $this->doGearmanRequest('build', $arr, $isbackground);
    }

    public function doGearmanRequest($funcName, $jsonParams, $isbackground = false) {/* {{{ */
        $ch_token = CacheHubWeb::$appConfigs[$this->application]['gearman']['ch_token'];
        $jsonParams['cacheHubWeb_token'] = $ch_token;
        $jsonParams_str = json_encode($jsonParams);
        $fun = 'WebCacheBuild_'.$funcName.'_'.CACHEHUBWEB_SERVER_VERSION;
        if ($isbackground) {
            $result = self::$gearManClient->doBackground($fun, $jsonParams_str);
        } else {
            $result = self::$gearManClient->doNormal($fun, $jsonParams_str);
        }
        if ($result) {
            $result = json_decode($result, true);
            return $result['result'];
        }
        return false;
    }

/* }}} */
}
