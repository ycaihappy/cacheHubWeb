<?php

define('CACHEHUBWEB_FILE_ROOT_PATH', '/lizhili/www/cache/' . CACHEHUBWEB_APPLICATION_NAME);
define('CACHEHUBWEB_REDIS_HOST','192.168.0.xx');
define('CACHEHUBWEB_REDIS_PORT','6379');
define('CACHEHUBWEB_REDIS_DB',xx);
return array(
    'file_group' => 'www',
    'chw_log_path' => '/data/log/cacheHubWeb/client',
    'chw_log_gearman' => array(
        'host' => '192.168.0.xx',
        'port' => '4730',
        'functionName' => 'default',
    ),
    'chw_debug_log_type' => 'no', //记录debug日志的方式，1.gearman 通过gearman写到日志系统 2.file 本地文件 3.no 不记录(默认)
    'chw_error_log' => 1, //1 开启错误日志 0关闭错误日志
    'gearman' => array(
        'host' => '192.168.0.xx',
        'port' => '4730',
        'worker_timeout' => 3000, //milliseconds
        'ch_token' => '33a247276f1377216d95',
    ),
    'XCache' => array(
        'default' => array(
            'ttl' => 300,
            'key_0' => CACHEHUBWEB_APPLICATION_NAME . '_%cacheGroupName%',
            'key_1' => CACHEHUBWEB_APPLICATION_NAME . '_%cacheGroupName%_%cacheKey%',
        ),
    ),
    'Memcache' => array(
        'default' => array(
            'ttl' => 300,
            'host' => '192.168.0.xx',
            'port' => '12120',
            'key_0' => CACHEHUBWEB_APPLICATION_NAME . '_%cacheGroupName%',
            'key_1' => CACHEHUBWEB_APPLICATION_NAME . '_%cacheGroupName%_%cacheKey%',
        ),
    ),
    'File' => array(
        'default' => array(
            'ttl' => 0,
            'useBigTable' => false,
            'isSplitKey' => true, //是否把cacheKey中的下滑线转换成目录
            'update_rate' => 1, //当文件过期时, 有1/update_rate的几率删除文件
            'filepath_0' => CACHEHUBWEB_FILE_ROOT_PATH . '/%cacheGroupName%.json',
            'filepath_1' => CACHEHUBWEB_FILE_ROOT_PATH . '/%cacheGroupName%/%cacheKey%.json',
        )
    ),
    'Redis' => array(
        'default' => array(
            'ttl' => 300,
            'host' => CACHEHUBWEB_REDIS_HOST,
            'port' => CACHEHUBWEB_REDIS_PORT,
            'db' => CACHEHUBWEB_REDIS_DB,
            'key_0' => CACHEHUBWEB_APPLICATION_NAME . '_%cacheGroupName%',
            'key_1' => CACHEHUBWEB_APPLICATION_NAME . '_%cacheGroupName%_%cacheKey%',
        ),
    ),
    'DB' => array(
        'default' => array(
            'ttl' => 30,
        ),
    ),
);
