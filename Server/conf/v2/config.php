<?php
define("CACHEHUB_WEB_PATH", '/usr/lib/php/pear/cacheHubWeb/server/'.CACHEHUB_WEB_VERSION);
define("CACHEHUB_CONF_PATH", '/data/conf/cacheHubWeb/server/'.CACHEHUB_WEB_VERSION);
define("CACHEHUB_WORKER_LOG_PATH", '/data/log/cacheHubWeb/worker/'.CACHEHUB_WEB_VERSION);

require_once CACHEHUB_WEB_PATH.'/WebCacheBuildWorker.class.php';