<?php
session_name('JGGL');
define('APP_NAME', 'JGGL');
define('APP_DEBUG', true);
define('DXINFO_PATH','DXINFO_DIR_PATH');
define('DX_PUBLIC','DXINFO_URL');
require_once './FirePHPCore-0.3.2/lib/FirePHPCore/fb.php';
define('THINK_PATH', './ThinkPHP/ThinkPHP312/');
error_reporting(E_ALL);
ini_set("display_errors","On");

if(ini_get("magic_quotes_gpc")=="1"){
    die("please set php.php magic_quotes_gpc=off\n");
}
define('APP_PATH', './'.APP_NAME.'/');
//设置临时路径
define('RUNTIME_PATH', '/tmp/'.APP_NAME."/");

//加载框架入口函数
require(THINK_PATH."ThinkPHP.php");

