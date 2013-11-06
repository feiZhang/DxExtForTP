<?php
class CantonTreeWidget extends Widget{
    public function render($data){
        if(isset($data["fdn"])) $fdn = $data["fdn"];
        else if(session("canton_fdn")!="") $fdn = session("canton_fdn");
        else $fdn = C("ROOT_CANTON_FDN");

        $m = D('Canton');

            if (isset($data["shengguanxian"])){
                $treeD  = $m->where(array("canton_id"=>array("in",$data["shengguanxian"])))->field("canton_id,parent_id,name,fdn")->select();
            }else{
                if(empty($fdn)){
                    $treeD  = array();
                }else{
                    $treeD  = $m->where(array("fdn"=>array("like",$fdn."%")))->field("canton_id,parent_id,name,fdn")->select();
                }
            }

        $treeData   = array("treeData"=>str_replace("{","{ ",json_encode($treeD)),"clickUrl"=>$data["clickJs"]);
        $content    = $this->renderFile('CantonTree',$treeData);
        return str_replace("__DXPUBLIC__", C("DX_PUBLIC"), $content);
    }
}
