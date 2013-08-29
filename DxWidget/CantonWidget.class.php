<?php
class CantonWidget extends DxWidget {
    public function render($data) {
        if(!is_array($data["fieldSet"])) $data["fieldSet"] = array();
    	$field			= array_merge($data["fieldSet"],$data);
    	$rootCantonId	= $field["canton"]["rootCantonId"];
    	if(intval($rootCantonId)<1) $rootCantonId	= C("SYS_ROOTCANTONID");
    	if(intval($rootCantonId)<1) $rootCantonId	= 3520;
    	 
        $val = array_merge($field, array("rootCantonId"=>$rootCantonId,"id_name"=>$field["canton"]["id_name"]));
        //默认id与name相同
        if(empty($val['value']) && $val['allowdefault'] && !$val['readonly']){
            $val['value']   = DxFunction::escapeHtmlValue($val['default']);
        }else{
            $val['value']   = DxFunction::escapeHtmlValue($val['value']);
        }
        $ret    = $this->renderFile("render", $val);
        return preg_replace('/<!--(.*)-->/Uis', '', $ret);
    }
}
