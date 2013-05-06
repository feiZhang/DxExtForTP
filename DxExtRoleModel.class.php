<?php
class DxExtRoleModel extends DxExtCommonModel {
    protected $listFields = array(
    );
    
    protected $modelInfo=array(
        "title"=>'用户角色','readOnly'=>true,"dictTable"=>"name","helpInfo"=>"用户角色由系统初始化，无法进行增删!"
    );
}
?>