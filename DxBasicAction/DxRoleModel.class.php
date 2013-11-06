<?php
class DxRoleModel extends DxExtCommonModel {
    public $listFields = array(
        'role_id'    =>array('type'=>'int','size'=>10 ,'default' =>'0'),
        'name'       =>array('type'=>'varchar','size'=>45 ,'title'=>'角色名'),
        'menu_ids'   =>array('type'=>'varchar','size'=>1000,'title'=>'菜单ID'),
        'shortcut_ids'=>array('type'=>'varchar','size'=>1000,'title'=> '快捷方式ids'),
        'desk_ids'   =>array('type'=>'varchar','size'=>1000,'title'=>'桌面菜单ids'),
    );

    protected $modelInfo=array(
        "title"=>'用户角色','readOnly'=>true,"enablePage"=>false,
        "dictTable"=>"name","helpInfo"=>"用户角色由系统初始化，无法进行增删!" 
    );

    public function getMenuID(){
        $role_id            = session("role_id");
        if(intval($role_id)<1) return "0";
        $data   = $this->where(array($this->getPk()=>$role_id))->field("menu_ids")->find();
        if(empty($data["menu_ids"]) && session("DP_ADMIN")==true){
            $menu = D("Menu");
            $menuPkId = $menu->getPk();
            $data = $menu->field($menuPkId)->select();
            $val = array();
            foreach($data as $v){
                $val[] = $v[$menuPkId];
            }
            return implode(",",$val);
        }

        return empty($data["menu_ids"])?"0":$data["menu_ids"];
    }
}

