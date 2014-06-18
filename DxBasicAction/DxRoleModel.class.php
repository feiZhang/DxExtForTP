<?php
class DxRoleModel extends DxExtCommonModel {
    public $listFields = array(
        'role_id'    =>array('title'=>'操作','hide'=>06,'width'=>80 ,'default' =>'0','renderer'    => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v   = '<a class=\"btn btn-xs btn-success\" href=\"javascript:dataOpeEdit( { \'id\':' + value + '});\">修改</a>';
                                    return v;
                                }"),
        'name'       =>array('type'=>'string','width'=>80 ,'title'=>'角色名'),
        'menu_ids'   =>array('type'=>'set','width'=>600,'title'=>'操作列表','valChange'=>array("model"=>"Menu")),
        'shortcut_ids'=>array('type'=>'string','width'=>1000,'title'=> '快捷方式ids','hide'=>07777),
        'desk_ids'   =>array('type'=>'string','width'=>1000,'title'=>'桌面菜单ids','hide'=>07777),
    );

    protected $modelInfo=array(
        "title"=>'用户角色','readOnly'=>true,"enablePage"=>false,
        "dictTable"=>"role_id,name","helpInfo"=>"<div class='alert alert-warning'>用户角色由系统初始化，无法进行增删!</div>"
    );

    public function getMenuID(){
        if(session("DP_ADMIN")==true){
            $menu = D("Menu");
            $menuPkId = $menu->getPk();
            $data = $menu->field($menuPkId)->select();
            $val = array();
            foreach($data as $v){
                $val[] = $v[$menuPkId];
            }
            return implode(",",$val);
        }else{
            $role_id            = session("role_id");
            if(intval($role_id)<1) return "0";
            $data   = $this->where(array($this->getPk()=>$role_id))->field("menu_ids")->find();
        }

        return empty($data["menu_ids"])?"0":$data["menu_ids"];
    }
}

