<?php
class DxCantonModel extends DxFdnModel {
    //put your code here
    protected $modelInfo=array(
        "title"=>'行政区域','readOnly'=>true,
        "dictTable"=>array("fdn","full_name"),
        //"leftArea"=>"{:W('Menu',array('type'=>'one','parent_id'=>100))}",
        "helpInfo"=>"该区域部分不能修改删除，只能查看。"
    );

    public function getSelectSelectSelect($fdn){
        $list       = S("Cache_SELECT_SELECT_SELECT_Canton");
        if(empty($list) || APP_DEBUG){
            if(empty($fdn)){
                $list = $this->field("canton_id pkid,name,parent_id,fdn,full_name")->order("fdn ASC")->select();
            }else{
                $list = $this->where(array("fdn",array("like",$fdn."%")))->field("canton_id pkid,name,parent_id,fdn,full_name")->order("fdn ASC")->select();
            }
            fb::log($this->getLastSQL());
            foreach($list as $kk=>$ll){
                $list[$kk]["canton_id"] = intval($ll["canton_id"]);
                $list[$kk]["parent_id"] = intval($ll["parent_id"]);
            }
            S("Cache_SELECT_SELECT_SELECT_Canton",$list);
        }

        return $list;
    }
    /**
     * 得到所属区域的第一级子区域
     * @param int $canton_id
     * @return  array $canton_list
     */
    public function getChildCanton($canton_id=""){
        if(empty($canton_id)){
            $canton_id = session('canton_id');
        }
        $canton_list = $this->where(array('parent_id'=>$canton_id))->field('canton_id,fdn,name')->select();
        return $canton_list ;
    }
    /**
     * 通过canton_id得到区域名称
     */
    public function getCantonNameByID($canton_id){
        return $this->where(array('canton_id'=>$canton_id))->getField('text_name');
    }
    /**
     * $canton_uniqueno
     */
    public function getCantonUniqueno($canton_fdn){
        return $this->where(array('fdn'=>$canton_fdn))->getField('canton_uniqueno');
    }

    public function _after_update($data,$option){
        S("Cache_SELECT_SELECT_SELECT_Canton",null);
        parent::_after_update($data,$option);
    }
    public function _after_delete($data,$option){
        S("Cache_SELECT_SELECT_SELECT_Canton",null);
        parent::_after_delete($data,$option);
    }

    public function _after_insert($data,$option){
        S("Cache_SELECT_SELECT_SELECT_Canton",null);
        parent::_after_insert($data,$option);
    }
}

