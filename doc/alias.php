<?php
defined('THINK_PATH') or exit();

//遍历DxInfo目录，将class加入到别名列表中，以能够自动加载
$dx_alias_class     = array();
$handle = opendir(DXINFO_PATH);
if($handle) {
    while(false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $filename = DXINFO_PATH . "/"  . $file;
            if(is_file($filename) && substr($file,-10)==".class.php") {
                $dx_alias_class[substr($file,0,-10)]  = $filename;
            }
        }
    }
    closedir($handle);
}

$handle = opendir(DXINFO_PATH."/DxBasicAction");
if($handle) {
    while(false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $filename = DXINFO_PATH . "/DxBasicAction/"  . $file;
            if(is_file($filename) && substr($file,-10)==".class.php") {
                $dx_alias_class[substr($file,0,-10)]  = $filename;
            }
        }
    }
    closedir($handle);
}
/*
 * TP生成runtime.php缓存文件后，将不在引用此文件，而是将这些配置项的最终值写到缓存文件中，所以需要将alias内容，要写入缓存中
 * */
return $dx_alias_class;

