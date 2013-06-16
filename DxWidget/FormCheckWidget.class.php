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
        //返回字符串
        "returnString"=>false,
        //返回数组时:
        //字段名称,不用添加[],自动添加
        "name"=>'', 
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
        $val           = array_merge($this->default, $data["fieldSet"], $data);
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']   = $val['default'];
        }
        if(is_string($val['value'])){
          if($val["valFormat"]=="json")
            $val['value']   = empty($val['value'])?array():json_decode($val['value'],true);
          else
            $val['value']   = empty($val['value'])?array():explode(",",$val["value"]);
        }
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
