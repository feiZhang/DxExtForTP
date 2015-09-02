<?php
/**
 * 1.创建项目数据库，并使用create1_basic.sql初始化数据库
 * 2.运行本脚本初始化代码
 * 3.修改database.inc.php配置
 * 注意:
 * 1.项目虚拟目录不能和APP_NAME相同，TP会删除虚拟目录
 */
//修改insttall.php的定义，运行之
define('APP_PATH','/Users/pengL/job/yuanzong/yuanzong');
define('APP_NAME','yuanzong');

//默认值可以不修改
define('DXINFO_PATH',substr(__FILE__,0,-16));

//== 安装程序
if(!file_exists(APP_PATH)) mkdir(APP_PATH);
if(!file_exists(APP_PATH."/".APP_NAME)) mkdir(APP_PATH."/".APP_NAME);
if(!file_exists(APP_PATH."/www")) mkdir(APP_PATH."/www");
copy(DXINFO_PATH."/doc/install/.htaccess",APP_PATH."/www/.htaccess");
copy(DXINFO_PATH."/doc/install/database.inc.php",APP_PATH."/database.inc.php");

@mkdir(APP_PATH."/www/Public");
@mkdir(APP_PATH."/www/Public/css");
@mkdir(APP_PATH."/www/Public/image");
@mkdir(APP_PATH."/www/Public/js");
copy(DXINFO_PATH."/doc/install/default.css",APP_PATH."/www/Public/css/default.css");
copy(DXINFO_PATH."/doc/install/dataope_ext.js",APP_PATH."/www/Public/js/dataope_ext.js");

@mkdir(APP_PATH."/".APP_NAME."/Conf");
copy(DXINFO_PATH."/doc/install/debug.php",APP_PATH."/".APP_NAME."/Conf/debug.php");
copy(DXINFO_PATH."/doc/install/alias.php",APP_PATH."/".APP_NAME."/Conf/alias.php");
copy(DXINFO_PATH."/doc/install/config.php",APP_PATH."/".APP_NAME."/Conf/config.php");

@mkdir(APP_PATH."/".APP_NAME."/Lib");
cpDir(DXINFO_PATH."/doc/install/Action",APP_PATH."/".APP_NAME."/Lib/Action");
cpDir(DXINFO_PATH."/doc/install/Model",APP_PATH."/".APP_NAME."/Lib/Model");
cpDir(DXINFO_PATH."/doc/install/Tpl",APP_PATH."/".APP_NAME."/Tpl");
cpDir(DXINFO_PATH."/doc/install/Widget",APP_PATH."/".APP_NAME."/Lib/Widget");
cpDir(DXINFO_PATH."/DxWebRoot",APP_PATH."/www/DxWebRoot");

$index = file(DXINFO_PATH."/doc/install/index.php");
$index = implode("",$index);
$index = str_replace("DXINFO_DIR_PATH",DXINFO_PATH,$index);
$index = str_replace("JGGL",APP_NAME,$index);
file_put_contents(APP_PATH."/www/index.php",$index);

echo "finish";

function cpDir($fromDir,$toDir){
    @mkdir($toDir);
    $dir = opendir($fromDir);
    while ($dir_file = readdir($dir)){
        if($dir_file != "." && $dir_file !=".."){
            $file = $fromDir.'/'.$dir_file;
            if(is_dir($file)){
                cpDir($file,$toDir."/".$dir_file);
            }else{
                copy($file,$toDir."/".$dir_file);
            }
        }
    }
    closedir($dir);
}

