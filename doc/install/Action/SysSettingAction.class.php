<?php
class SysSettingAction extends DataOpeAction {
    public function __construct(){
        parent::__construct();
        $this->model->setLeftMenu(120);
    }
}

