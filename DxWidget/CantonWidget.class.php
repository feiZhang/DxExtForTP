<?php
class CantonWidget extends DxWidget {
    private $default=array(
        //是否允许显示默认值
        "allowdefault"=>false,
        //默认值
        "default"=>'', 
        //字段填充值
        "value"=>'',
        "id"=>'',
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
    	$field			= array_merge($data["field"],$data);
    	$rootCantonId	= $field["canton"]["rootCantonId"];
    	if(intval($rootCantonId)<1) $rootCantonId	= C("SYS_ROOTCANTONID");
    	if(intval($rootCantonId)<1) $rootCantonId	= 3520;
    	 
        $val=  array_merge($this->default, array("rootCantonId"=>$rootCantonId,
        											"name"=>$field["name"],
        											"value"=>$data["value"],
        											"validclass"=>$data["validclass"]
        											));
        //默认id与name相同
        if(empty($val['id'])){
            $val['id']=  uniqid(DxFunction::escapeHtmlValue($val['name']));
        }
        
        $val['value']= DxFunction::escapeHtmlValue($val['value']);
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']=  DxFunction::escapeHtmlValue($val['default']);
        }
        $ret=$this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}
