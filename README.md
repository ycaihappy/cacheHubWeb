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
    * DBBuilder & CacheBuilder
    * Builder extend your application.
* Gearman queue 
* Token verfiy
* Simple configuration,specilly expired time for different application


## Getting Started


### System Requirements

You need **PHP >= 5.3.0**.

### Tutorial

1. Start Gearman server
2. Define Builder byself
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

3. Call sample by php
```php
include "/data/conf/cacheHubWeb/client/config.php";

$cacheHubWeb = CacheHubWeb::getInstance(CACHEHUBWEB_APPLICATION_NAME, 'common.languages');
$cacheHubWeb = CacheHubWeb::getInstance(CACHEHUBWEB_APPLICATION_NAME, 'common.countries');
$cacheHubWeb = CacheHubWeb::getInstance(CACHEHUBWEB_APPLICATION_NAME, 'common.categoryPath');
$result = $cacheHubWeb->get();
#$result = $cacheHubWeb->mget();
```

## Community

### Wechat

Follow [µÁÂë¼Ç] to receive news and updates about the framework.

## Author

The Framework is created and maintained by [Jackie Lee](http://www.lizhili.com). 

## License

The Framework is released under the MIT public license.