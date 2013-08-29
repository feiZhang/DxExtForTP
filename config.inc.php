<?php
$DXINFO_PATH = DXINFO_PATH;

return array (
    'DISABLE_ACTION_AUTH_CHECK' => false,
    'DX_PUBLIC' => "/DxInfo/DxWebRoot",
    'LOGIN_URL' => "Public/login",	//登录页面
    
    // 设置公共模板路径,属于TP的配置内容,一般情况下，不需要覆盖修改的内容。
    'TMPL_ACTION_ERROR' => $DXINFO_PATH . "/DxTpl/success.html",
    'TMPL_ACTION_SUCCESS' => $DXINFO_PATH . "/DxTpl/success.html",
    'TOKEN_ON' => false, // 表单令牌关闭，感觉不到有什么用途，1.一个提交，多个model->create时，后面的令牌验证错误
    'TOKEN_NAME' => "DxToken",
    'TOKEN_RESET' => false, // 需要设置，否则ajax提交验证失败后，将导致系统重新生成令牌。
    'DEFAULT_MODULE' => 'Home',
    'APP_AUTOLOAD_PATH' => $DXINFO_PATH,
    'SESSION_AUTO_START' => true,
    
    'TMPL_ENGINE_TYPE' => "Dxthink",     //模板解析类。。TP自带的类对，tags支持非常弱，3.1.3就不支持tags，3.1.2的模板继承不支持tags，所以直接创建自己的模板类
    
    // url重写的支持
    'URL_MODEL' => 2,
    'URL_ROUTER_ON' => true,
    'URL_ROUTE_RULES' => array ( // 正则模式下，不能使用 /xx/xx/xx/xx传递GET参数，会强制引入分组概念，导致action对应不上
        "/(\w+)\/edit\/(\d+)/" => ":1/add?id=:2",
        "/(\w+)\/delete\/(\d+)/" => ":1/delete?id=:2" 
    ),
    
    'tags' => array (
        // TP3.1.3删除了此配置，所以放弃此版本
        "view_template" => array (
            "DxParseTemplate", // 自动定位模板文件
            'LocationTemplate', // 自动定位模板文件
            "_overlay" => true 
        ),
    ),
    
    'DEFAULT_THEME' => '',
    // 自己的数据节点，同步数据要从此节点获取数据
    'APP_DEBUG' => false, // 是否开启调试模式
    // 我的桌面默认宽度和高度,宽度和高度要不带单位，用于页面css显示 如 style="width:300px;height:206px"
    'MY_DESKTOP' => array ('width' => '300','height' => '206' ),
    
    'DP_POWER_FIELDS' => array (
        array ('field_name' => 'create_user_id','auto_type' => 1,'type' => 0,'session_field' => "login_user_id" ),
        array ('field_name' => 'create_user_name','auto_type' => 1,'type' => 0,'session_field' => 'truename' ),
        array ('field_name' => 'create_canton_fdn','auto_type' => 1,'type' => 1,'session_field' => 'cantonfdn' ),
        array ('field_name' => 'create_public','auto_type' => 0,'type' => 2,'session_field' => '' ) 
    ),
    'DP_NOT_CHECK_ACTION' => array ("Public" => 1,"DataSync" => 1 ), // 不进行数据权限控制的Action
    'DELETE_TAGS' => array ("delete_status" => "1" ),
    'NOT_AUTH_ACTION' => array ("Public" => 1,"Web" => 1 ), // 无需权限认证的Action
    'REQUIST_AUTH_ACTION' => array (), // 必须权限认证的Action
    'LOGIN_USER_NICK_NAME' => "name", // 用户昵称字段名
    'LOGIN_MD5' => false, // 是否md5加密密码
    'USER_AUTH_KEY' => 'login_user_id',
    // 文件上传的临时路径
    'TEMP_FILE_PATH' => RUNTIME_PATH . "TMP_IMG",
    'UPLOAD_BASE_PATH' => dirname(APP_PATH)."/userUploadFiles",
    //控制data_list的默认是否加载菜单
    'HAVE_HEADER_MENU' => true, 
);

