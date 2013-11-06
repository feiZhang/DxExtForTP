<?php
class  OperationLogAction extends DataOpeAction{
    public function __construct(){
        parent::__construct();
        $this->model->setLeftMenu(120);
    }
}

