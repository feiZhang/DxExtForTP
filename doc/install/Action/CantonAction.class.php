<?php
class CantonAction extends DataOpeAction {
    public function getSelectSelectSelect(){
        $list   = $this->model->getSelectSelectSelect();
        echo json_encode($list);
    }
}

