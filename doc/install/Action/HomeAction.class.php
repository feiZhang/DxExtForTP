<?php
class HomeAction extends DxExtCommonAction {
    public function home_top(){
        $this->display();
    }
    public function index(){
        if(empty($_REQUEST["showURL"])){
            $this->assign("mainURL",__URL__."/main");
        }else{
            $this->assign("mainURL",$_REQUEST["showURL"]);
        }

        if(C("INDEX_IFRAME")){
            $this->display("Public:home");
        }else{
            $this->display();
        }
    }
    public function main(){
        $this->display("Home:index");
    }
}

