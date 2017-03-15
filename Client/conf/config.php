<?php
defined('CACHEHUBWEB_APPLICATION_NAME') or define('CACHEHUBWEB_APPLICATION_NAME', 'www.lizhili.com');
define('CACHEHUBWEB_CLIENT_VERSION', 'v1');
define('CACHEHUBWEB_SERVER_VERSION', 'v2');

#define("CACHEHUB_CONF_PATH", '/data/conf/cacheHubWeb/client/'.CACHEHUBWEB_CLIENT_VERSION);
# 为方便单元测试, 开发环境下, 配置文件的根目录, 更改为动态的相对路径
define("CACHEHUB_CONF_PATH",  dirname(__FILE__) . '/' .CACHEHUBWEB_CLIENT_VERSION);


require_once 'cacheHubWeb/client/'.CACHEHUBWEB_CLIENT_VERSION.'/CacheHubWeb.class.php';
