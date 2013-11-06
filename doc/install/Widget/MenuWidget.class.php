<?php
class MenuWidget extends Widget{
    public function render($data){
        switch($data["type"]){
            case "two":
                $menu_list  = D('Menu')->getChildrenMenu($data['parent_id']);
                $content    = $this->renderFile('left',array("list"=>$menu_list,"root_id"=>$data["parent_id"]));
                return str_replace("__DXPUBLIC__", C("DX_PUBLIC"), $content);
                break;
            default:
                $menu_list  = D('Menu')->getChildrenMenu($data['parent_id']);
                $content    = $this->renderFile('menu',array("menu_list"=>$menu_list));
                return str_replace("__DXPUBLIC__", C("DX_PUBLIC"), $content);
                break;
        }
    }
}
