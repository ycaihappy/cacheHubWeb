<?php
interface ICHWebHelper {
    
    public function get($cacheKey);

    public function put($cacheKey, $cacheData);
    
}
