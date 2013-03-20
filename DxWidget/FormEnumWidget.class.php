<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FormEnum
 *
 * @author zhangyud
 */
class FormEnumWidget extends DxWidget {
    private $default=array(
        //是否允许显示默认值
        "allowdefault"=>false,
        //默认值
        "default"=>'',
        //当前值,当前值为空时显示
        "value"=>'',
        //显示列表,结构array('v'=>'label')
        "set"=>array(),
        "name"=>'', 
        //占位符
        'placeholder'=>'',
        //自定义css类
        'class'=>'',
        //表单验证使用的css类
        'validclass'=>'',
        //自定义css,推荐使用此字段
        'custom_class'=>'',
        'readOnly'=>false,
        //保留
        'label'=>'',
        'width'=>0,
        'height'=>0,
        'cwidth'=>'',
        );
    public function render($data) {
        $val=  safe_merge($this->default, $data);
        $val['value']=  htmlentities($val['value'],ENT_QUOTES,"UTF-8");
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  htmlentities($val['default'],ENT_QUOTES,"UTF-8");
        }
        $val['placeholder']=  htmlentities($val['placeholder'],ENT_QUOTES,"UTF-8");
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
