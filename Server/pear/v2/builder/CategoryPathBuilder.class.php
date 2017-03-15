<?php

class CategoryPathBuilder extends CacheBuilder{

    public function build($application,$cacheGroupName,$cacheKeyId) {/*{{{*/
        $this->init($application,$cacheGroupName); 
       
        $category_list = $this->getCategoryList();
        if(empty($category_list)) {
            WorkerLog::error($this->application,$this->groupName,'Not Found Category Data!',__FILE__,__LINE__);
            return array();
        }

        $category_level_list = $this->getCategoryLevelList($category_list);

        //var_dump($category_level_list);exit;
        return $category_level_list;
    }/*}}}*/

    //查询分类级别为level:1,2,3的分类
    public function getCategoryList(){
        $sql = "select categories_id,parent_id,level from categories where categories_status=1 and level in(1,2,3) order by level";
        $rs = $this->DB->getAllWithColumnIndex($sql,'categories_id');
        if(count($rs)) { 
             return $rs;
        }    
    } 

    //根据分类级别构造数据
    public function getCategoryLevelList($category_list) {
        $category_level = array();
        foreach($category_list as $ck=>$cv) {
            $level = $cv['level'];
            $parent_id = $cv['parent_id'];
            if($level==1) {
                $category_level[$ck] = "{$ck}";  
            }elseif($level==2) {
                $category_level[$ck] = "{$parent_id}_{$ck}";
            }elseif($level==3 && isset($category_level[$parent_id])) {
                $category_level[$ck] = $category_level[$parent_id]."_{$ck}";
            }
        }
        return $category_level;
    }

}



