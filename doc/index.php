<?php
ini_set("display_errors","On");
session_name('zhibao');
//error_reporting(E_ALL);
define('THINK_PATH', '../ThinkPHP/ThinkPHP312/');
define('APP_NAME', 'JGGL');
define('APP_PATH', './JGGL/');
define('APP_DEBUG', true);

//设置临时路径
define('RUNTIME_PATH', '/tmp/'.APP_NAME."/");

//加载框架入口函数
define('DXINFO_PATH','/job/DxInfo');
require_once '../ThinkPHP/FirePHPCore-0.3.2/lib/FirePHPCore/fb.php';
require(THINK_PATH."ThinkPHP.php");
// $App = new App(); 
// $App->run();
?>