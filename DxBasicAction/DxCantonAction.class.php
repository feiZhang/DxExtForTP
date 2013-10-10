<?php
class DxCantonAction extends DataOpeAction{
    public function getSelectSelectSelect(){
        $list   = $this->model->getSelectSelectSelect();
        //dump($this->model->getLastSQL());
        echo json_encode($list);
    }
}

