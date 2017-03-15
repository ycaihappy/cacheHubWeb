<?php
interface IWebCacheBuilder {
    public function build($application,$cacheGroupName,$cacheKeyId);
}