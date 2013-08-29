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
        "valFormat"=>'yyyy-MM-dd',
        'width'=>100,
    );
    public function render($data) {
        if(!is_array($data["fieldSet"])) $data["fieldSet"] = array();
        $val=  array_merge($this->default, $data["fieldSet"],$data);
        //宽度
        if($val['width']>0){
            $val['cwidth']="width: {$val['width']}px;";
        }
        //设置弹出的格式及限制条件
        $attr=array("dateFmt"=>$val['valFormat']);
        //最大值
        if(!empty($val['maxvalue'])){
            $attr['maxDate']=$val['maxvalue'];
        }
        //最小值
        if(!empty($val['minvalue'])){
            $attr['minDate']=$val['minvalue'];
        }
        $str=  json_encode($attr);
        $val['focus']=  DxFunction::escapeHtmlValue("WdatePicker($str)") ;
        
        $val['value']= DxFunction::escapeHtmlValue($val['value']);
        if(empty($val['value'])){
            $val['value']=  htmlentities($val['default'],ENT_QUOTES,"UTF-8");
        }
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
