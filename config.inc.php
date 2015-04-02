<?php
return array (
    //--需要配置变动的
    'DX_PUBLIC' => DX_PUBLIC,//DxInfo的Web目录地址,目前wpickdate插件不支持跨域引用
    'APP_DEBUG' => false, // 是否开启调试模式
    'UPLOAD_BASE_PATH' => dirname(APP_PATH)."/userUploadFiles",
    'HAVE_HEADER_MENU' => true,
    'DB_BACK_PATH' => '/tmp/',
    'INDEX_IFRAME' => true,         //是否使用iframe进行首页显示
    'VERIFY_CODE' => true,
    'NOT_OPERATION_LOG' => array('Account-online','Public-verify','Home-main'),

    //控制data_list的默认是否加载菜单
    'NO_SAVE_DATA_CHANGE' => array('DataChangeLog','Menu','OperationLog'),   //不尽兴data_change记录的Model
    'USER_AUTH_KEY' => 'login_user_id',
    'LOGIN_USER_NICK_NAME' => "true_name", // 用户昵称字段名
    'DP_NOT_CHECK_ACTION' => array ("Public" => 1,"DataSync" => 1 ), // 不进行数据权限控制的Action
    'DP_POWER_FIELDS' => array (
        array ('field_name' => 'creater_user_id','auto_type' => 1,'type' => 0,'session_field' => "login_user_id" ),
        array ('field_name' => 'creater_user_name','auto_type' => 1,'type' => 0,'session_field' => 'true_name' ),
        array ('field_name' => 'creater_canton_fdn','auto_type' => 1,'type' => 1,'session_field' => 'cantonfdn' ),
        array ('field_name' => 'creater_public','auto_type' => 0,'type' => 2,'session_field' => '' ) 
    ),
    'NOT_AUTH_ACTION' => array ("Public" => 1,"Web" => 1 ), // 无需权限认证的Action
    'REQUIST_AUTH_ACTION' => array (), // 必须权限认证的Action
    'LOGIN_MD5' => true, // 是否md5加密密码
    'ROOT_CANTON_FDN' => "03520.",

    //--偶尔需要变动的
    'DELETE_TAGS' => array ("delete_status" => "1" ),
    'DISABLE_ACTION_AUTH_CHECK' => false,       //关闭登录验证
    'DISABLE_ACTION_OPERATE_CHECK' => false,    //关闭操作权限验证
    'LOGIN_URL' => "Public/login",//登录页面
    'URL_MODEL' => 2,
    'DEFAULT_THEME' => '',
    // 我的桌面默认宽度和高度,宽度和高度要不带单位，用于页面css显示 如 style="width:300px;height:206px"
    'MY_DESKTOP' => array ('width' => '300','height' => '206' ),
    // 文件上传的临时路径
    'TEMP_FILE_PATH' => RUNTIME_PATH . "TMP_IMG",
    'CUT_PHOTO_DEFAULT_IMG' => 'touxiang_default_heibai.jpg', //photo_default.png
    'UPLOAD_IMG_FILETYPE' => '.gif、.jpeg、.jpg、.png',    //通常文件上传的扩展名

    //--几乎不进行改动的配置项
    // 设置公共模板路径,属于TP的配置内容,一般情况下，不需要覆盖修改的内容。
    'TMPL_ACTION_ERROR' => DXINFO_PATH . "/DxTpl/success.html",
    'TMPL_ACTION_SUCCESS' => DXINFO_PATH . "/DxTpl/success.html",
    'TOKEN_ON' => false, // 表单令牌关闭，感觉不到有什么用途，1.一个提交，多个model->create时，后面的令牌验证错误
    'TOKEN_NAME' => "DxToken",
    'TOKEN_RESET' => false, // 需要设置，否则ajax提交验证失败后，将导致系统重新生成令牌。
    'DEFAULT_MODULE' => 'Home',
    'APP_AUTOLOAD_PATH' => DXINFO_PATH,
    'SESSION_AUTO_START' => true,
    'TMPL_ENGINE_TYPE' => "Dxthink",     //模板解析类。。TP自带的类对，tags支持非常弱，3.1.3就不支持tags，3.1.2的模板继承不支持tags，所以直接创建自己的模板类
    'TMPL_STRIP_SPACE' => false,        //这个查询按钮没有间隔

    // url重写的支持
    'URL_ROUTER_ON' => true,
    'URL_ROUTE_RULES' => array ( // 正则模式下，不能使用 /xx/xx/xx/xx传递GET参数，会强制引入分组概念，导致action对应不上
        "/(\w+)\/edit\/(\d+)/" => ":1/add?id=:2",
        "/(\w+)\/delete\/(\d+)/" => ":1/delete?id=:2",
        "/Public\/showSysMsg\/(\d+)/" => "Public/showSysMsg?id=:1",
    ),

    'tags' => array (
        // TP3.1.3删除了此配置，所以放弃此版本
        "view_template" => array (
            "DxParseTemplate", // 自动定位模板文件
            'LocationTemplate', // 自动定位模板文件
            "_overlay" => true
        ),
    ),

    'LOG_RECORD' => true, // 进行日志记录
    'LOG_EXCEPTION_RECORD' => true, // 是否记录异常信息日志
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR,WARN,INFO,DEBUG,SQL', // 允许记录的日志级别
);

