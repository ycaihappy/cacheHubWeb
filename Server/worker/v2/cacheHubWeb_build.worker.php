<?php
$version = 'v2';
define("CACHEHUB_WEB_VERSION",$version);

//¼ÓÔØcacheHubWeb
require_once '/data/conf/cacheHubWeb/server/'.CACHEHUB_WEB_VERSION.'/config.php';

if(!is_dir(CACHEHUB_WEB_PATH)) {
    die(CACHEHUB_WEB_PATH.' dir is not exists!');
}

$worker = WebCacheBuilderWorker::getInstance(true);
$worker->run('build_'.CACHEHUB_WEB_VERSION);
