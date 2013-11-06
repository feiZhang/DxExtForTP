<?php
class AccountAction extends DxAccountAction {
    public function __construct(){
        parent::__construct();
        $this->model->setLeftMenu(120);
    }
}

