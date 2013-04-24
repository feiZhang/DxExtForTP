<?php
class DxExtRoleModel extends DxExtCommonModel {
    public $listFields = array(
            'role_id'    =>array('type'=>'int','size'=>10 ,'default' =>'0'),
            'name'       =>array('type'=>'varchar','size'=>45 ,'title'=>'角色名'),
            'menu_ids'   =>array('type'=>'varchar','size'=>1000,'title'=>'菜单ID'),
            'shortcut_ids'=>array('type'=>'varchar','size'=>1000,'title'=> '快捷方式ids'),
            'desk_ids'   =>array('type'=>'varchar','size'=>1000,'title'=>'桌面菜单ids'),
    );
    
    protected $modelInfo=array(
        "title"=>'用户角色','readOnly'=>true,"dictTable"=>"name","helpInfo"=>"用户角色由系统初始化，无法进行增删!"
    );
}
?>