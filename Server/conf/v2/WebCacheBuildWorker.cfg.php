<?php
return array(
    //'builder_path' = > '###',
    'connections' => array(
            'default' => array(
            'host' => '192.168.0.xx',  
            'port' => 3306,
            'username'=>'lizhili',
            'encrypt' => true,   //是否加密
            'password' => 'CW0GPgM7XU5WPAJ7WkdTMgJuUmM=',
            'dbname' => 'lizhili'
        ),

        'master2' => array(
            'host' => '192.168.0.xx',  
            'port' => 3306,
            'username'=>'lizhili',
            'encrypt' => true,   //是否加密
            'password' => 'UTVUbFJqABNcNgR9VktRMFY6UmM=',
            'dbname' => 'lizhili'
        )
    ),


    'gearman' => array(
        'host' => '192.168.0.xx ',
        'port' => '4730'
    ),

    'ch_token' => '33a2048377216d95',   //worker tokden

    'memcache'=> array(
        'host' => '192.168.0.xx',
        'port' => '12120'
        ),
        'allow_mget_group' => array('product'),

);
