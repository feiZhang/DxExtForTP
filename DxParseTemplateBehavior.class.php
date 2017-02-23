<?php
defined('THINK_PATH') or exit();
/**
 * 系统行为扩展：定位模板文件  和  模板内容处理
 * @category   DxInfoBasic
 * @package  DxInfoBasic
 * @subpackage  Behavior
 * @author   liangpeng
 */
class DxParseTemplateBehavior extends Behavior {
    // 行为扩展的执行入口必须是run
    public function run(&$para){
        //是模板文件解析
        if(!file_exists_case($para)) $para   = $this->checkTplFile($para);
    }

    /**
     * 在本目录找不到模板时，自动到Dx目录去取公共模板。
     * 0.文件的绝对路径
     * 1.默认tpl路径
     * 2.按照Model名称的tpl路径
     * 3.项目的DxPublic目录，（需要重写公共的模板）
     * 4.DxInfo的模板路径
     * */
    public function checkTplFile($templateFile){
        if(file_exists($templateFile)){
            return $templateFile;
        }

        if(''==$templateFile) {
            // 如果模板文件名为空 按照默认规则定位
            $templateFile = C('TEMPLATE_NAME');
        }elseif(false === strpos($templateFile,C('TMPL_TEMPLATE_SUFFIX'))){
            // 解析规则为 模板主题:模块:操作 不支持 跨项目和跨分组调用
            $path   =  explode(':',$templateFile);
            $action = array_pop($path);
            $module = !empty($path)?array_pop($path):MODULE_NAME;
            if(!empty($path)) {// 设置模板主题
                $path = dirname(THEME_PATH).'/'.array_pop($path).'/';
            }else{
                $path = THEME_PATH;
            }
            $templateFile  = $path.$module.C('TMPL_FILE_DEPR').$action.C('TMPL_TEMPLATE_SUFFIX');
        }
        
        if(empty($action)) $action    = ACTION_NAME;
        if(file_exists($templateFile)){
            $tplFile    = $templateFile;
        }else if(file_exists(THEME_PATH.MODULE_NAME.'/'.$action.C('TMPL_TEMPLATE_SUFFIX'))){
            $tplFile    = THEME_PATH.MODULE_NAME.'/'.$action.C('TMPL_TEMPLATE_SUFFIX');
        }else if(file_exists(THEME_PATH.'Public/'.$action.C('TMPL_TEMPLATE_SUFFIX'))){
            $tplFile    = THEME_PATH.'Public/'.$action.C('TMPL_TEMPLATE_SUFFIX');
        }else{
            $tplFile    = sprintf("%s/DxTpl/%s%s%s",dirname(__FILE__),DX_THEME_PATH,$action,C('TMPL_TEMPLATE_SUFFIX'));
            if(!file_exists($tplFile)){
                $tplFile= sprintf("%s/DxTpl/%s%s",dirname(__FILE__),$action,C('TMPL_TEMPLATE_SUFFIX'));
            }
        }
        return $tplFile;
    }
}
