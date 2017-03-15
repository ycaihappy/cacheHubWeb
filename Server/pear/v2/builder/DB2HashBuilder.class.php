<?php

class DB2HashBuilder extends CacheBuilder {

    public function build($application,$cacheGroupName,$cacheKeyId) {
        $this->init($application,$cacheGroupName);

        $sqlQuery = $this->getSql();
        $rs = $this->DB->getAll($sqlQuery);
        $dataList = array();
        if(count($rs)) {
            foreach($rs as $v) {
                $hash_key = $v['HASH_KEY'];
                unset($v['HASH_KEY']);
                $dataList[$hash_key] = $v;
            }
        }
        return $dataList;
    }





}
