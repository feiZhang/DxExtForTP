<?php
class DxWidget extends Widget {
	public function render($data='') {
		
	}
	protected function renderFile($templateFile='',$var='') {
		if(!file_exists_case($templateFile)){
			// 自动定位模板文件
			$name   = substr(get_class($this),0,-6);
			$filename   =  empty($templateFile)?$name:$templateFile;
			$templateFile = LIB_PATH.'Widget/DxDataOpe/'.$name.'/'.$filename.C('TMPL_TEMPLATE_SUFFIX');
			if(!file_exists_case($templateFile))
				throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
		}
		return parent::renderFile($templateFile,$var);
	}
}