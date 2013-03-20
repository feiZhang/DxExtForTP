<?php

/**
 * 显示出表单编辑器.
 *
 * @author zhangyud
 * 
 */
class FormEditorWidget extends DxWidget{
    private $default=array(
        "value"=>'', 
        'label'=>'',
        "name"=>'', 
        'placeholder'=>'',
        "maxvalue"=>'',
        "minvalue"=>'',
        "maxsize"=>'',
        "minsize"=>'',
        'class'=>'',
        'width'=>0,
        'height'=>0,
        'cwidth'=>'',
        'custom_class'=>'',
        'readOnly'=>false
        );
    public function render($data) {
        $val=  safe_merge($this->default, $data);
        if($val['width']>0){
            $val['cwidth']="width: {$val['width']}px;";
        }
        if($val['height']>0){
            $val['cheight']="height: {$val['height']}px;";
        }
        $val['id']=  uniqid($val['name']."_");
        $val['value']= escapeHtmlValue($val['value']);
        $val['placeholder']=  escapeHtmlValue($val['placeholder']);
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
