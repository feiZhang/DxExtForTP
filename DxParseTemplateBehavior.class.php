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
        if(is_array($para)){
            //是模板内容解析
            if(empty($para["content"])) $para["content"]    = file_get_contents($para["file"]);
            $para["content"]    = $this->praseIncludeForDxInfo($para["content"]);
        }else{
            //是模板文件解析
            if(!file_exists_case($para)) $para   = $this->checkTplFile($para);
        }
    }

    /**
     * 在本目录找不到模板时，自动到Dx目录去取公共模板。
     * 0.文件的绝对路径
     * 1.默认tpl路径
     * 2.按照Model名称的tpl路径
     * 3.项目的DxPublic目录，（需要重写公共的模板）
     * 4.DxInfo的模板路径
     * */
    protected function checkTplFile($templateFile){
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
            $templateFile  =  $path.$module.C('TMPL_FILE_DEPR').$action.C('TMPL_TEMPLATE_SUFFIX');
        }
        
        if(empty($action)) $action    = ACTION_NAME;
        if(file_exists($templateFile)){
            $tplFile    = $templateFile;
        }else if(file_exists(THEME_PATH.MODULE_NAME.'/'.$action.C('TMPL_TEMPLATE_SUFFIX'))){
            $tplFile    = THEME_PATH.MODULE_NAME.'/'.$action.C('TMPL_TEMPLATE_SUFFIX');
        }else if(file_exists(THEME_PATH.'Public/'.$action.C('TMPL_TEMPLATE_SUFFIX'))){
            $tplFile    = THEME_PATH.'Public/'.$action.C('TMPL_TEMPLATE_SUFFIX');
        }else{
            $tplFile	= sprintf("%s/DxTpl/%s%s",dirname(__FILE__),$action,C('TMPL_TEMPLATE_SUFFIX'));
        }
        return $tplFile;
    }
    
    /**
     * 1.解析模板文件中include标签，支持 include DxInfo中的模板文件。
     * 2.替换DX_PUBLIC 模板路径
     */
    protected function praseIncludeForDxInfo($content){
        $content    = str_replace("__DXPUBLIC__", C("DX_PUBLIC"), $content);
        $find       = preg_match_all('/<include\s(.+?)\s*?\/>/is',$content,$matches);
        if($find) {
            for($i=0;$i<$find;$i++) {
                $xml        =   '<tpl><tag '.$matches[1][$i].' /></tpl>';
                $xml        =   simplexml_load_string($xml);
                if(!$xml) throw_exception(L('_XML_TAG_ERROR_'));
                $xml        =   (array)($xml->tag->attributes());
                $array      =   array_change_key_case($xml['@attributes']);
                $file       =   $array['file'];
                unset($array['file']);
                $includeContent   = file_get_contents($this->checkTplFile($file));
                $content    =   str_replace($matches[0][$i],$includeContent,$content);
            }
            return $this->praseIncludeForDxInfo($content);
        }
        return $content;
    }
}
