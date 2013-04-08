<?php
class DxWidget extends Widget {
	public function render($data='') {
		
	}
	protected function renderFile($templateFile='',$var='') {
		if(!file_exists_case($templateFile)){
			// 自动定位模板文件
			$name       = substr(get_class($this),0,-6);
			$filename   =  empty($templateFile)?$name:$templateFile;
<<<<<<< HEAD
			$templateFile = C(DX_INFO_PATH).'/DxWidget/'.$name.'/'.$filename.C('TMPL_TEMPLATE_SUFFIX');
=======
			$templateFile = dirname(__FILE__).'/DxWidget/'.$name.'/'.$filename.C('TMPL_TEMPLATE_SUFFIX');
>>>>>>> origin
			if(!file_exists_case($templateFile))
				throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
		}
		return parent::renderFile($templateFile,$var);
	}
}