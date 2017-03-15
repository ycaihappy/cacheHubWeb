<?php
class MemcacheHelper {
    const DEFAULT_EXPIRE = 30;

    public static $_memcache = null;

    public static function getInstance() {
        if (self::$_memcache === null) {
            self::$_memcache = new Memcache();
            // connect to memcached server
            $cacheConfigArr = WebCacheConfig::getPublicConfig();
            $memcacheConfig = $cacheConfigArr['memcache'];
            $conn =  self::$_memcache->connect($memcacheConfig['host'],$memcacheConfig['port']);
            if(!$conn) {
                WorkerLog::error('build','DB','Memcache Connect Fail!',__FILE__,__LINE__);
                return false;
            }
        }
        return self::$_memcache;
    }
    public static function set($key, $value, $expire=null) {
        if ($expire === null) {
            $expire = self::DEFAULT_EXPIRE;
        }
        if (!$key) {
            return false;
        }
        $instance = self::getInstance();
        if(self::getInstance()) {
            $instance->set($key, $value, 0, $expire);
        }
    }
    public static function get($key) {
        if (!$key) {
            return false;
        }
        $instance = self::getInstance();
        if($instance) {
            return $instance->get($key);
        }
        return false;
    }
    public static function delete($key) {
        if (!$key) {
            //$key = "DEFAULT_MEMCACHE_KEY";
        }
        $instance = self::getInstance();
        if($instance) {
            return $instance->delete($key);
        }
    }
}
