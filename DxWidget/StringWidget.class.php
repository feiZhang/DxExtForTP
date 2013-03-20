<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TestWidget
 *
 * @author zhangyud
 */
class StringWidget extends DxWidget {
    private $default=array(
        //是否允许显示默认值
        "allowdefault"=>false,
        //默认值
        "default"=>'', 
        //当前值,
        "value"=>'', 
        'label'=>'',
        "name"=>'', 
        'placeholder'=>'',
        "maxvalue"=>'',
        "minvalue"=>'',
        "maxsize"=>'',
        "minsize"=>'',
        //textarea默认显示50列字符
        "cols"=>50,
        //textarea默认显示5行
        "rows"=>5,
        //
        'width'=>0,
        'cwidth'=>'',
        'class'=>'',
        'custom_class'=>'',
        //验证类
        "validclass"=>'',
        'readOnly'=>false
        );
    public function render($data) {
        $val=  safe_merge($this->default, $data);
        $longText=false;
        if($val['width']>0){
            if($val['width']>1000){
                $row=$val['width']/1000;
                $val['rows']=$row>$val['rows']?$row:intval($val['rows']);
                $longText=true;
            }else{
                $val['cwidth']="width: {$val['width']}px;";
            }
        }
        $val['value']=  htmlentities($val['value'],ENT_QUOTES,"UTF-8");
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  htmlentities($val['default'],ENT_QUOTES,"UTF-8");
        }
        $val['placeholder']=  htmlentities($val['placeholder'],ENT_QUOTES,"UTF-8");

        if($longText){
            $ret=$this->renderFile("textarea", $val);
        }else{
            $ret=$this->renderFile("text", $val);
        }
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
