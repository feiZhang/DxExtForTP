<?php

/**
 * 用户管理， 目前只有一个密码修改功能
 */
class DxAccountAction extends DataOpeAction {
    public function __construct(){
        parent::__construct();
        $menu_id = D('Menu')->where(array('menu_name'=>'系统管理','parent_id'=>0))->getField('id');
        $this->assign('menu_id',$menu_id);
        //修改的时候，密码非必填
        if(array_key_exists("id",$_REQUEST)){
            $this->model->setListField("pwd",array("note"=>"为空则不更新密码"));
        }
    }

    function editpass() {
       if ($_REQUEST["subEditPass"] == "修改") {
            $m = D("Account");
            $oldP = $m->field("pwd")->where(array('id'=>session(C('USER_AUTH_KEY'))))->find();
            $newP =  $m->getEncryptPwd($_REQUEST['newPass']);
            if ($m->verifyPassword($oldP['pwd'],$_REQUEST['oldpass'])) {
                $r = $m->where(array("id" => session(C('USER_AUTH_KEY'))))->save(array("pwd" => $newP));
                if (FALSE !== $r) {
                    $this->assign("message", "密码修改成功!");
                } else {
                    $this->assign("message", "密码修改失败!");
                }
            }else
                $this->assign("message", "旧密码不正确!");
        }
        $this->display();
    }

    function resetPassword(){
        if(intval($_REQUEST["i"])>0){
            $m  = D("Account");
            $v  = $m->save(array("pwd"=>$m->getEncryptPwd($_REQUEST['p']),$m->getPk()=>intval($_REQUEST["i"])));
            if($v)
                $this->ajaxReturn(0,"重置成功!",1);
            else
                $this->ajaxReturn(0,"重置失败!",0);
        }else{
            $this->ajaxReturn(0,"非法请求!",0);
        }
    }
}
