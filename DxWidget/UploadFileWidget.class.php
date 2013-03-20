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
class UploadFileWidget extends DxWidget {
    private $default=array(
        //当前值
        "value"=>'',
        "id"=>'', 
        "new_photo_id"=>'',
        "name"=>'', 
        'validclass'=>'',
        //提示信息
        'prompt'=>'新增文件',
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
        
        if(empty($val['value'])){
            $val['value']=  json_decode("{}");
        }else{
            $val['value']= json_decode($val['value'], true);
        }
        {
        	//默认按钮为“新增文件” 
            if(!empty($val['upload']['buttonValue'])){
                $val['prompt']=$val['upload']['buttonValue'];
            }
            $filetype=empty($val["upload"]["filetype"])?C("UPLOAD_FILETYPE"):$val["upload"]["filetype"];
            $max		= intval($val["upload"]["maxNum"])<0?1:intval($val["upload"]["maxNum"]);
            if($max>1){
                $val['prompt'].="最多".$max."个";
            }
            //默认文件最大大小为2M
            $maxSize	= empty($val["upload"]["maxSize"])?1024*1024*2:intval($val["upload"]["maxSize"]);
            //文件上传组件参数
            $option=array(
                "acceptFileTypes"=>$filetype,
                "maxNumberOfFiles"=>$max,
                "maxFileSize"=>$maxSize,
                "inputFieldName"=>$val['name'],
                "initValue"=>$val["value"],
                "downLoadBaseUrl"=>C("UPLOAD_BASE_URL"),
                );
            $val['option']=$option;
        }
        $val['uploadType'] = ($max==1)?"":'multiple';
        $val['placeholder']= escapeHtmlValue($val['placeholder']);
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}

?>
