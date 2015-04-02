<?php
class DxMenuModel extends  DxExtCommonModel{
    public $listFields=array(
        "menu_id"      => array('type'=>'int','size'=>5,'pk'=>true),
        "parent_id"    => array('type'=>'int','size'=>5,'title'=>'上级菜单编号'),
        "order_no"     => array('type'=>'int','size'=>10 ,'default' =>'0','comment'=> '序号,等于:父亲的order_no+自己的显示order_no*power(32,6-order_level)'),
        "order_level"  => array('type'=>'int','size'=>1, 'default' => '0', 'comment' =>'order层次'),
        "click_times"  => array('type'=>'int','default' => '0', 'comment' =>'被点击的次数'),
        "menu_name"    => array('type'=>'varchar','size'=>45, 'default' =>'', 'comment'=> '菜单名称'),
        "module_name"  => array('type'=> 'varchar','size'=>45 ,'default'  =>'', 'comment' => '模块名称'),
        "action_name"  => array('type'=>'varchar','size'=>31, 'default'  =>'', 'comment' => 'Action名称'),
        "args"         => array('type'=>'varchar','size'=>127,'default'  =>'', 'comment' => '参数,某些菜单提供默认参数'),
        "type"         => array('type'=>"enum",'default' =>'action','valChange'=>array('quick_menu','menu','action','hide_action'), 'comment'=> '菜单类型：快捷菜单、菜单、显示动作、后台动作'),
        "is_desktop"   => array('type'=> 'tinyint','size'=>4, 'default' => '0'),
        "desktop_url"  => array('type'=>'varchar','size'=>31, 'default' =>'','comment'=>'桌面菜单URL'),
        "other_info"   => array('type'=> 'varchar','size'=>127,'default' =>'','comment'=>'附加信息')
    );

    protected $modelInfo=array(
        "title"=>'用户菜单','readOnly'=>true,"enablePage"=>false,"order"=>"order_no",
        "dictTable"=>"menu_id,menu_name","helpInfo"=>""
    );

    public function getDongTaiMenu(){
        return $this->where(array("type"=>array('in',"menu,sub_menu"),$this->getPk()=>array('in',D("Role")->getMenuID())))->order("click_times desc,order_no asc")->select();
    }
    public function getAllMenu(){
        $data	= $this->where(array("type"=>array('in',"hide_sub_menu,sub_menu,menu"),'menu_id'=>array('in',D("Role")->getMenuID())))->order("order_no asc")->select();
        //die($this->getLastSQL());
        return $data;
    }
    public function getMenu(){
        $data	= $this->where(array("type"=>array('in',"menu"),'menu_id'=>array('in',D("Role")->getMenuID())))->order("order_no asc")->select();
        //die($this->getLastSQL());
        return $data;
    }

    public function getAllAction(){
        return $this->order("order_no")->select();
    }
    public function getMyAction(){
        $data = $this->where(array('menu_id'=>array('in',D("Role")->getMenuID())))->order("order_no asc")->select();
        return $data;
    }
    public function getRoleDeskTop(){
        $data	= $this->where(array("is_desktop"=>"1",'menu_id'=>array('in',D("Role")->getMenuID())))->order("order_no asc")->select();
        return $data;
    }
    public function getMenuID( $ModuleName,$ActionName){
        return $this->where(array('module_name'=>$ModuleName,'action_name'=>$ActionName))->getField($this->getPk());
    }
    public function getChildrenMenu($parent_id){
        $my = $this->where(array("menu_id"=>$parent_id))->find();
        $len        = 5*(6-intval($my["order_level"]));
        $order_no   = intval($my["order_no"])>>$len;
        $data   = $this->where(array("_string"=>"`order_no`>>".$len."=".$order_no." AND menu_id<>".$my["menu_id"],'menu_id'=>array('in',D("Role")->getMenuID()),'type'=>array('in','sub_menu,hide_sub_menu')))->order("order_no asc")->select();
        return $data;
    }
    public function updateClickTimes($where){
        $info = $this->where($where)->select();
        $mcM = D("MenuClick");
        if($mcM){
            foreach($info as $data){
                $where["user_id"] = $_SESSION[C("USER_AUTH_KEY")];
                $where["menu_id"] = $data["menu_id"];
                $rv = $mcM->where($where)->save(array("click_times"=>array("exp","click_times+1")));
                if($rv===false || $rv<1){
                    $where["click_times"] = 1;
                    $mcM->add($where);
                    // dump($mcM->getLastSQL());dump($info);die();
                }
            }
        }
        // if(in_array("click_times",$this->getDbFields())){
        //     $this->where($where)->save(array("click_times"=>array("exp","click_times+1")));
        // }
    }
}

