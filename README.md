# CacheHubWeb Framework

CacheHubWeb is a framework that helps you quickly power your web applications by Mutiple level cache
It is easy to use for both beginners and professionals. I think you're going to love it.

## Features

* Support follow cache
    * Xcache
    * File
    * Redis
    * Memcache
    * Therolly,all nosql support, but need development
* Support Builder
    * DBBuilder & CacheBuilder is Base
    * Builder extend your application.
* Gearman queue 
* Token verfiy
* Simple configuration,specilly expired time for different application


## Getting Started


### System Requirements

You need **PHP >= 5.3.0  AND Gearman >= 1.0.1**. 

### Tutorial

1. Configuration Server  accord by your builder,such as language,directy use sql to build.
```php
return array(
    'builder' => 'DB2HashBuilder',
    'sql' => 'select code as HASH_KEY,languages_id,name,code,currency_code,is_top,directory from languages where language_status=1 order by sort_order',
);
```

2. Start Gearman server and excute the worker
```shell
gearmand -d -l /var/log/gearmand.log -p4379
nohup php cacheHubWeb_build.worker.php &
```

3. configuration client  accord by your business,such as language,named common.languages.conf
```php
return array(
    'caches' => array(
        'XCache' => array(),
        'File' => array(
            'ttl' => 3600,
        ),
        'Redis' => array(
            'ttl' => 1800,
        ),
        'DB' => array(),
    ),
);
```

4. Example call by php client
```php
include "/data/conf/cacheHubWeb/client/config.php";

$cacheHubWeb = CacheHubWeb::getInstance(CACHEHUBWEB_APPLICATION_NAME, 'common.languages');
$cacheHubWeb = CacheHubWeb::getInstance(CACHEHUBWEB_APPLICATION_NAME, 'common.countries');
$cacheHubWeb = CacheHubWeb::getInstance(CACHEHUBWEB_APPLICATION_NAME, 'common.categoryPath');
$result = $cacheHubWeb->get();
#$result = $cacheHubWeb->mget();
```

## Community

### 公众号

Follow [盗码记] to receive news and updates about the framework.

## Author

The Framework is created and maintained by JK and his followers

## License

The Framework is released under the MIT public license.
