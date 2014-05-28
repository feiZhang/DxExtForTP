<?php
class DxDataTreeAction extends DataOpeAction {
    public function getSelectSelectSelect(){
        $list   = $this->model->getSelectSelectSelect();
        echo json_encode($list);
    }

    public function index(){
        $this->assign('dx_data_list', DXINFO_PATH."/DxTpl/data_list.html");
        $this->display("data_list");
    }

    /** 通过ajax提交删除请求 **/
    public function delete(){
        $deleteState    = false;
        $model          = $this->model;
        if (! empty ( $model )) {
            $id = $_REQUEST["id"];
            if (intval ( $id )>0) {
                $pk = $model->getPk ();
                $condition = array($pk=>intval($id));
                $list = $model->where ( $condition )->find();
                if($list){
                    $model->where ( array("fdn"=>array("like",$list["fdn"]."%")) )->delete();
                    $deleteState    = true;
                }
            }
        }

        if($deleteState) $this->ajaxReturn(array("data"=>0,"info"=>"删除成功!","status"=>1),"JSON");
        else $this->ajaxReturn(array("data"=>0,"info"=>"删除失败!","status"=>0),"JSON");
    }
}

