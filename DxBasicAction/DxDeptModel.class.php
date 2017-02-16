<?php
class DxDeptModel extends DxExtCommonModel {
    protected  $listFields = array (
        "name" => array('title'=>'部门名称','hide'=>00,),
        "parent_id" => array("title"=>"父ID","hide"=>00,"display_none"=>06),
        "layer" => array("title"=>"层次","hide"=>00,"display_none"=>06),
        "creater_user_id" => array('title'=>'创建人',"hide"=>06,"width"=>80,"valChange"=>array("model"=>"Account")),
        "create_time"    => array('title'=>'创建时间','type'=>'date','hide'=>06),
    );
    protected $modelInfo=array(
        "name"=>'部门管理','readOnly'=>true,
        "dictTable"=>array("fdn","full_name"),
        //"leftArea"=>"{:W('Menu',array('type'=>'one','parent_id'=>100))}",
        //"helpInfo"=>"该区域部分不能修改删除，只能查看。"
    );
    //更新fdn和full_name
    public function _after_insert($data,$option){
        if(!empty($data['parent_id'])){
            $map[$this->getPk()] = $data['parent_id'];
            $p_info = $this->where($map)->field('fdn,full_name')->find();//父fdn
            $data["fdn"] = $fdn = sprintf('%s%05d.',$p_info['fdn'],$data[$this->getPk()]);
            $full_name = sprintf('%s%s',$p_info['full_name'],$data['name']);
        }else{
            $data["fdn"] = $fdn = sprintf('%05d.',$data[$this->getPk()]);
            $full_name = $data['name'];
        }

        $this->where(array($this->getPk() => $data[$this->getPk()]))->save(array('fdn'=>$fdn,'full_name'=>$full_name));
        fb::log($data);
        parent::_after_insert($data,$option);
        return $data;
    }

    public function getSelectSelectSelect($fdn=""){
        if(empty($fdn)){
            $list = $this->field("dept_id pkid,name,parent_id,fdn,full_name")->select();
        }else{
            $list = $this->where(array("fdn"=>array("like",$fdn."%")))->field("dept_id pkid,name,parent_id,fdn,full_name")->select();
        }
        foreach($list as $kk=>$ll){
            $list[$kk]["pkid"] = intval($ll["pkid"]);
            $list[$kk]["parent_id"] = intval($ll["parent_id"]);
        }

        return $list;
    }
    /**
     * 得到所属区域的第一级子区域
     * @param int $canton_id
     * @return  array $canton_list
     */
    public function getChildCanton($parent_id=""){
        if(empty($parent_id)){
            $parent_id = 0;
        }
        $data_list = $this->where(array('parent_id'=>$parent_id))->field($this->getPk().',fdn,name')->select();
        return $data_list ;
    }
}

