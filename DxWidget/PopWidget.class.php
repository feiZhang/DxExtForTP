<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PopWidget
 *
 * @author zhangyud
 */
class PopWidget extends DxWidget {
    private $default=array("width"=>'100', 
        "val"=>'', 
        'label'=>'',
        "name"=>'', 
        'baseclass'=>'itemAddText',
        'class'=>'',
        'id'=>'',
        'vid'=>'',
        'url'=>'',
        'did'=>'',
        'multi'=>false,
        'callback'=>"",
        'readOnly'=>false
        );
    public function render($data) {
        $val=  array_merge($this->default, $data);
        if(empty($val['id'])){
            $val['id']=  uniqid("pop_");
        }
        if(empty($val['vid'])){
            $val['vid']=  uniqid("v_");
        }
        if(empty($val['did'])){
            $val['did']=  uniqid("did_");
        }
        $val['val']=htmlentities($val['val'],ENT_QUOTES,"UTF-8");
        $val["label"]=htmlentities($val['label'],ENT_QUOTES,"UTF-8");
        $val["placeholder"]=htmlentities($val['placeholder'],ENT_QUOTES,"UTF-8");
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
