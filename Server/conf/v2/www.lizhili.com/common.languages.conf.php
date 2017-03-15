<?php 
return array(
    'builder' => 'DB2HashBuilder',
    'sql' => 'select code as HASH_KEY,languages_id,name,code,currency_code,is_top,directory from languages where language_status=1 order by sort_order',
);
