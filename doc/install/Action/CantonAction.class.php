<?php
class CantonAction extends DataOpeAction {
    public function getSelectSelectSelect(){
        $list   = $this->model->getSelectSelectSelect();
        echo json_encode($list);
    }

    function add_node(){
        $model  = $this->model;
        if(empty($model)) die();
        //判断是否为修改数据
        $vo = array();$pkId = 0;
        if(isset($_REQUEST["canton_id"]))
            $pkId     = intval($_REQUEST["canton_id"]);
        $this->assign("listFields",$model->getEditFields($pkId));

        if($pkId>0){
            //要修改的 数据内容
            $vo     = $model->find( $pkId );
            if($vo){
                $this->assign('pkId',array($model->getPk(),$pkId));
            }else{
                $this->error('要修改的数据不存在!请确认操作是否正确!');
            }
        }

//         $this->assign('valid', $model->getValidate(Model::MODEL_INSERT));
        $this->assign('objectData', array_merge($vo,$_REQUEST));
        $tempFile   = TEMP_PATH.'/'.$this->theModelName.'_'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX');
        if(!$this->cacheTpl || C('APP_DEBUG') || !file_exists($tempFile)){
            $tempT  = $this->fetch("data_edit");
            file_put_contents($tempFile, $tempT);
        }
        $this->display($tempFile);
    }

     public function index(){
         $this->assign('dx_data_list', DXINFO_PATH."/DxTpl/data_list.html");
         $this->display("data_list");
     }
}

