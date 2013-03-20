<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FormCheckWidget
 *
 * @author zhangyud
 */
class FormCheckWidget extends DxWidget{
    private $default=array(
        //默认值,使用,隔开,也可以使用array.
        "value"=>'',
        //显示列表,结构array('v'=>'label')
        "set"=>array(),
        //返回字符串
        "returnString"=>false,
        //返回数组时:
        //字段名称,不用添加[],自动添加
        "name"=>'', 
        //占位符
        'placeholder'=>'',
        //自定义css类
        'class'=>'',
        //表单验证使用的css类
        'validclass'=>'',
        //自定义css,推荐使用此字段
        'custom_class'=>'',
        //保留,当前还没有使用.
        'readOnly'=>false,
        //保留.
        'label'=>'',
        'width'=>0,
        'height'=>0,
        'cwidth'=>'',
        );
    public function render($data) {
        $val=  safe_merge($this->default, $data);
        $val['id']=  uniqid($val['name']."_");
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  escapeHtmlValue($val['default']);
        }
        if(is_string($val['value'])){
            $val['value']=empty($val['value'])?array():explode(",", $val['value']);
        }
        if(is_array($val['value'])){
            array_walk($val['value'], "escapeHtmlValue");
        }
        $val['stringResult']=  implode(",", $val['value']);
        //$val['value']=  escapeHtmlValue($val['value']);
        $val['placeholder']=  escapeHtmlValue($val['placeholder']);
        array_walk($val['set'], "escapeHtmlValue");
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
