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
class CutPhotoWidget extends DxWidget {
    private $default=array(
        //是否允许显示默认值
        "allowdefault"=>false,
        //默认值
        "default"=>'', 
        //默认值
        "value"=>'',
        "id"=>'', 
        "new_photo_id"=>'',
        "name"=>'', 
        'validclass'=>'',
        //保留
        'readOnly'=>false,
        //占位符,酷吧
        'placeholder'=>'',
        //自定义css类
        'class'=>'',
        'cwidth'=>'',
        //显示宽度
        'width'=>100,
        'baseClass'=>'',
        //自定义css,推荐使用此字段
        'custom_class'=>'',
        //是否这只读字段
        );
    public function render($data) {
        $val=  safe_merge($this->default, $data);
        //默认id与name相同
        if(empty($val['id'])){
            $val['id']=  uniqid(escapeHtmlValue($val['name']));
        }
        if(empty($val['new_photo_id'])){
            $val['new_photo_id']=  uniqid(escapeHtmlValue("newphoto_"));
        }
        
        $val['value']= escapeHtmlValue($val['value']);
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  escapeHtmlValue($val['default']);
        }
        $val['placeholder']= escapeHtmlValue($val['placeholder']);
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
