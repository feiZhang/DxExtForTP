<?php
if (! defined ( 'THINK_PATH' ))
    exit ();

$theProjectConfig = require (DXINFO_PATH . "/config.inc.php");
$theAppConfig = array (
	//项目应用配置
);

$theProjectDatabase = require ("./database.inc.php");

if (file_exists ( "./database.php" ))
    $theAppDatabase = require ("database.php");
else 
    $thieAppDatabase = array();

if (file_exists ( "./debug.inc.php" ))
    $theDebugConfig = require ("./debug.inc.php");
else
    $theDebugConfig = array ();

$endConfig = array_merge ( $theProjectConfig, $theProjectDatabase, $theAppDatabase, $theAppConfig, $theDebugConfig );
// var_dump($endConfig)
return $endConfig;
