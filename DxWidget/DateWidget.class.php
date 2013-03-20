<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DatetimeWidget
 *
 * @author zhangyud
 */
class DateWidget extends DxWidget {
    private $default=array(
        //是否允许显示默认值
        "allowdefault"=>false,
        //默认值
        "default"=>'', 
        //日期格式
        "format"=>'yyyy-MM-dd',
        //默认值
        "value"=>'',
        //mode==current && value==''时显示当前时间
        'mode'=>'',
        "id"=>'', 
        "name"=>'', 
        //占位符,酷吧
        'placeholder'=>'',
        //最大值
        "maxvalue"=>'',
        //最小值
        "minvalue"=>'',
        //自定义css类
        'class'=>'',
        //显示宽度
        'width'=>100,
        'cwidth'=>'',
        'baseClass'=>'Wdate',
        //自定义css,推荐使用此字段
        'custom_class'=>'',
        //是否这只读字段
        'readOnly'=>false
        );
    public function render($data) {
        
        $val=  safe_merge($this->default, $data);
        if($val['width']>0){
            $val['cwidth']="width: {$val['width']}px;";
        }
        //设置默认值
        if($val['mode']=='current' && empty($val['value'])){
            $val['value']=  date("Y-m-d");
        }
        //设置弹出的格式及限制条件
        if(!isset($val['focus'])){
            $attr=array("dateFmt"=>$val['format']);
            //最大值
            if(!empty($val['maxvalue'])){
                $attr['maxDate']=$val['maxvalue'];
            }
            //最小值
            if(!empty($val['minvalue'])){
                $attr['minDate']=$val['minvalue'];
            }
            $str=  json_encode($attr);
            $val['focus']=  escapeHtmlValue("WdatePicker($str)") ;
        }
        //默认id与name相同
        if(empty($val['id'])){
            $val['id']=$val['name'];
        }
        $val['value']= escapeHtmlValue($val['value']);
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  htmlentities($val['default'],ENT_QUOTES,"UTF-8");
        }
        $val['placeholder']= escapeHtmlValue($val['placeholder']);
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
