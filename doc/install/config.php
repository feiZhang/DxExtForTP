<?php
if (! defined ( 'THINK_PATH' ))
    exit ();

$theProjectConfig = require (DXINFO_PATH . "/config.inc.php");
$theProjectDatabase = require ("../database.inc.php");

if (file_exists ( "../database.php" ))
    $theAppDatabase = require ("database.php");
else
    $theAppDatabase = array ();

$theAppConfig = array (
    // DxInfo配置
    'DX_PUBLIC' => "/DxInfo/DxWebRoot" 
    // 项目应用配置
);

$endConfig = array_merge ( $theProjectConfig, $theProjectDatabase, $theAppDatabase, $theAppConfig );
// var_dump($endConfig)
return $endConfig;

