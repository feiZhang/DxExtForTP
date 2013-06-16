<?php
//DXIPCC 核心配置文件
if (!defined('THINK_PATH')) exit();
return array(
    'DISABLE_ACTION_AUTH_CHECK'    => false,
    'DX_INFO_PATH'          => dirname(__FILE__),
    'DX_PUBLIC'             => "/DxInfo/DxWebRoot",
    
    //设置公共模板路径,属于TP的配置内容,一般情况下，不需要覆盖修改的内容。
    'TMPL_ACTION_ERROR'		=> dirname(__FILE__)."/DxTpl/success.html",
    'TMPL_ACTION_SUCCESS'	=> dirname(__FILE__)."/DxTpl/success.html",
    'TOKEN_ON'				    => true, //表单令牌开启
    'TOKEN_NAME'          => "DxToken",
    'TOKEN_RESET'         => false,       //需要设置，否则ajax提交验证失败后，将导致系统重新生成令牌。
    'DEFAULT_MODULE'		  => 'Home',
    'APP_AUTOLOAD_PATH'		=> "Com.DxInfo",
    'SESSION_AUTO_START'	=> true,
    
    //url重写的支持
    'URL_MODEL'             => 2,
    'URL_ROUTER_ON' 		=> true,
    'URL_ROUTE_RULES'		=> array(			//正则模式下，不能使用 /xx/xx/xx/xx传递GET参数，会强制引入分组概念，导致action对应不上
            "/(\w+)\/edit\/(\d+)/"=>":1/add?id=:2",
            "/(\w+)\/delete\/(\d+)/"=>":1/delete?id=:2",
    ),
    
    'tags' =>  array(
        "view_template"     => array(
            "DxParseTemplate", // 自动定位模板文件
            'LocationTemplate', // 自动定位模板文件
            "_overlay"          => true,
        ),
        'view_parse'        =>  array(
            'DxParseTemplate', // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
            'ParseTemplate', // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
            "_overlay"          => true,
        ),
    ),
    
    
        
    'DEFAULT_THEME'			=> '',

    //自己的数据节点，同步数据要从此节点获取数据
    'APP_DEBUG'				=> true,	// 是否开启调试模式
    //我的桌面默认宽度和高度,宽度和高度要不带单位，用于页面css显示 如 style="width:300px;height:206px"
    'MY_DESKTOP'=>array('width'=>'300','height'=>'206'),

    'DP_POWER_FIELDS'	=> array(
            array('field_name'=>'create_userid','auto_type'=>1,'type'=>0,'session_field'=>"_id"),
            array('field_name'=>'create_username','auto_type'=>1,'type'=>0,'session_field'=>'_truename'),
            array('field_name'=>'create_dept_fdn','auto_type'=>1,'type'=>1,'session_field'=>'_cantonfdn'),
            array('field_name'=>'create_public','auto_type'=>0,'type'=>2,'session_field'=>''),
    ),
    'DP_NOT_CHECK_ACTION'	=> array("Public"=>1,"DataSync"=>1),	//不进行数据权限控制的Action
    'DELETE_TAGS'		=> array("delete_status"=>"1"),

    'NOT_AUTH_ACTION'		=> array("Public"=>1,"Web"=>1),		//无需权限认证的Action
    'REQUIST_AUTH_ACTION'	=> array(),					//必须权限认证的Action
    'LOGIN_USER_NICK_NAME'	=> "true_name",		//用户昵称字段名
    'LOGIN_MD5'				=> false,		//是否md5加密密码
    'USER_AUTH_KEY'         => 'login_user_id',
    //文件上传的临时路径
    'TEMP_FILE_PATH'        => RUNTIME_PATH."TMP_IMG",
);
?>
