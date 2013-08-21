<?php
defined('THINK_PATH') or exit();
//根据ThinkPHP模板类，制作自己的模板类，支持 1.DXPUBLIC包含。 2.对DxTpl模板的搜索支持

class TemplateDxthink extends ThinkTemplate {
    /**
     * 1.解析模板文件中include标签，支持 include DxInfo中的模板文件。
     * 2.替换DX_PUBLIC 模板路径
     * 注意：避免自己引用自己
     */
    protected function parseInclude($content) {
        // 解析继承
        $content    =   $this->parseExtend($content);
        // 解析布局
        $content    =   $this->parseLayout($content);
        
        $content    = str_replace("__DXPUBLIC__", C("DX_PUBLIC"), $content);
        $find       = preg_match_all('/<include\s(.+?)\s*?\/>/is',$content,$matches);
        if($find) {
            $behavior   = new DxParseTemplateBehavior();
            for($i=0;$i<$find;$i++) {
                $xml        =   '<tpl><tag '.$matches[1][$i].' /></tpl>';
                $xml        =   simplexml_load_string($xml);
                if(!$xml) throw_exception(L('_XML_TAG_ERROR_'));
                $xml        =   (array)($xml->tag->attributes());
                $array      =   array_change_key_case($xml['@attributes']);
                $file       =   $array['file'];
                unset($array['file']);
                $includeContent   = file_get_contents($behavior->checkTplFile($file));
                $content    =   str_replace($matches[0][$i],$includeContent,$content);
            }
            return $this->parseInclude($content);
        }
        return $content;
    }
}
