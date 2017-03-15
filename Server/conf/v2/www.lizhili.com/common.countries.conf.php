<?php 
return array(
    'builder' => 'DB2HashBuilder',
    'sql' => 'SELECT countries_iso_code_2 as HASH_KEY ,countries_id,countries_iso_code_2,countries_iso_code_3,countries_name,address_format_id,countries_iso_language,countries_iso_currency,time_zone,chinese_name FROM countries order by countries_id',
);
