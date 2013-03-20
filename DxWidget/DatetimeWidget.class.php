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
class DatetimeWidget extends DxWidget {
    private $default=array(
        //日期格式
        "format"=>'yyyy-MM-dd HH:mm:ss',
        //默认值
        "value"=>'',
        //mode==current && value==''时显示当前时间
        'mode'=>'',
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
        'width'=>160,
        'cwidth'=>'',
        'baseClass'=>'Wdate',
        //自定义css,推荐使用此字段
        'custom_class'=>'',
        //是否这只读字段
        'readOnly'=>false
        );
    public function render($data) {
        
        $val=  safe_merge($this->default, $data);
        //设置显示宽度
        if($val['width']>0){
            $val['cwidth']="width: {$val['width']}px;";
        }
        //设置默认值
        if($val['mode']=='current' && empty($val['val'])){
            $val['value']=  date("Y-m-d h:i:s");
        }
        //设置弹出样式及限制
        if(!isset($val['focus'])){
            $attr=array("dateFmt"=>$val['format']);
            if(!empty($val['maxvalue'])){
                $attr['maxDate']=$val['maxvalue'];
            }
            if(!empty($val['minvalue'])){
                $attr['minDate']=$val['minvalue'];
            }
            $str=  json_encode($attr);
            $val['focus']= escapeHtmlValue("WdatePicker($str)") ;
        }
        $val['value']=  escapeHtmlValue($val['value']);
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  escapeHtmlValue($val['default']);
        }
        $val['placeholder']=  escapeHtmlValue($val['placeholder']);
        
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
