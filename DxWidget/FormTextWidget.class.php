<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FormTextWidget
 *
 * @author zhangyud
 */
class FormTextWidget extends DxWidget{
    private $default=array(
        "value"=>'',
        "set"=>array(),
        'label'=>'',
        "name"=>'', 
        'placeholder'=>'',
        "maxvalue"=>'',
        "minvalue"=>'',
        "maxsize"=>'',
        "minsize"=>'',
        'class'=>'',
        'validclass'=>'',
        'custom_class'=>'',
        'width'=>0,
        'height'=>0,
        'cwidth'=>'',
        'readOnly'=>false
        );
    public function render($data) {
        $val        =  array_merge($this->default, $data["fieldSet"],$data);
        if($val['width']>0){
            $val['cwidth']="width:".$val['width']."px;";
        }
        $val['value']=  htmlentities($val['value'],ENT_QUOTES,"UTF-8");
        $val['placeholder']=  htmlentities($val['placeholder'],ENT_QUOTES,"UTF-8");
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}