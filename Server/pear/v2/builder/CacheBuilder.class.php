<?php

abstract class CacheBuilder implements IWebCacheBuilder{

    public $cacheConfig;
    public $builderConfig;
    public $DB;
    public $application;
    public $groupName;

    protected  function init($application,$cacheGroupName) {
        $this->cacheConfig = WebCacheConfig::getPublicConfig();
        $this->builderConfig = WebCacheConfig::getBuilderConfig($application,$cacheGroupName);
        $this->application = $application;
        $this->groupName = $cacheGroupName;
        $connectName = $this->getConnectName();
        $dbConfig = $this->getConnectData($connectName);
       // var_dump($dbConfig);
        $this->DB = PDODB::getInstance($dbConfig,true);
        if(!$this->DB) {
            WorkerLog::error($this->application,$this->groupName,'Connect DB Error!',__FILE__,__LINE__);
        }
    }

    protected  function getSql() {
        $cacheConfig = $this->builderConfig;
        $sqlQuery = $cacheConfig['sql'];
        if(!isset($sqlQuery)) {
            WorkerLog::error($this->application,$this->groupName,'Can Not Get Sql Query!',__FILE__,__LINE__);
        }
        return $sqlQuery;
    }

    protected function getConnectName() {
        $cacheConfig = $this->builderConfig;
        if(isset($cacheConfig['connect'])){
            return $cacheConfig['connect'];
        }
        return 'default';

    }

    protected function getConnectData($connectName) {
        $cacheConfig = $this->cacheConfig;
        if(!isset($cacheConfig['connections'][$connectName])) {
            WorkerLog::error($this->application,$this->groupName,'Mysql ConnectName:'.$connectName.' Can Not Get DB Config Data!',__FILE__,__LINE__);
            return array();
        }
        return $cacheConfig['connections'][$connectName];;

    }

}
