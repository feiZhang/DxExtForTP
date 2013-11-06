<?php
/**
 * 用户管理， 目前只有一个密码修改功能
 */
class DxAccountAction extends DataOpeAction {
    function editpass() {
       if ($_REQUEST["subEditPass"] == "修改") {
            $m = $this->model;
            $oldP = $m->field("login_pwd")->where(array('id'=>session(C('USER_AUTH_KEY'))))->find();
            if ($m->verifyPassword($oldP['login_pwd'],$_REQUEST['oldpass'])) {
                $r = $m->where(array("id" => session(C('USER_AUTH_KEY'))))->save(array("login_pwd" => $_REQUEST['newPass']));
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
            $v  = $this->model->where(array($this->model->getPk()=>intval($_REQUEST["i"])))->save(array("login_pwd"=>$_REQUEST['p']));
            if($v)
                $this->ajaxReturn(0,"重置成功!",1);
            else
                $this->ajaxReturn(0,"重置失败!",0);
        }else{
            $this->ajaxReturn(0,"非法请求!",0);
        }
    }
}

