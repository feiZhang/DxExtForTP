set names utf8;

-- 基础数据 -----------------
-- 区域信息表
DROP TABLE IF EXISTS `canton`;
CREATE TABLE IF NOT EXISTS `canton` (
  `canton_id` smallint(5) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '行政区名称',
  `parent_id` smallint(5) unsigned zerofill NOT NULL DEFAULT '00000' COMMENT '上级区域ID',
  `ordernum` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `layer` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '所在层',
  `fdn` varchar(32) NOT NULL DEFAULT '' COMMENT '标识区域关系的串，一般为上级区域的fdn当前id.',
  `canton_uniqueno` char(8) NOT NULL DEFAULT '' COMMENT '区域代码',
  `full_name` varchar(128) NOT NULL DEFAULT '' COMMENT '全称',
  `user_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1为可以删除，0为不能删除',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`canton_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='行政区域信息表';

-- 公共数据字典表
DROP TABLE IF EXISTS `sys_dic`;
CREATE TABLE IF NOT EXISTS `sys_dic` (
  `dic_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父类型的ID',
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '所示意思',
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '字典类型(唯一)',
  `memo` varchar(100) DEFAULT '' NOT NULL COMMENT '备注',
  `order` int(11) unsigned DEFAULT 0 NOT NULL COMMENT '排序，用于在页面上显示顺序',
  `other_info` varchar(100) DEFAULT '' NOT NULL COMMENT '其他信息，比如：房间数量',
  `show_type` tinyint(1) unsigned DEFAULT '1' NOT NULL COMMENT '是否显示在用户编辑页面，1显示 0不显示 2显示但不能编辑',
  `user_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1已删除0正常',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '创建时间',
  `sync_status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`dic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='字典表';

-- 系统全局参数表
DROP TABLE IF EXISTS `sys_setting`;
CREATE TABLE IF NOT EXISTS `sys_setting` (
  `set_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT '' COMMENT '参数名称',
  `val` varchar(255) DEFAULT '' COMMENT '参数值',
  `type` enum('sys','user') DEFAULT 'sys' COMMENT '参数类型（sys系统参数，user用户参数）,系统参数不在页面上显示，不允许用户修改',
  `memo` varchar(1000) DEFAULT '' COMMENT '备注',
  `order` int(11) DEFAULT 0 COMMENT '顺序值，',
  PRIMARY KEY (`set_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='系统设置表';

-- 系统菜单和功能点表，权限验证依赖
DROP TABLE IF EXISTS `menu`;
CREATE TABLE IF NOT EXISTS `menu` (
  `menu_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '上级菜单编号',
  `order_no` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '序号,等于:父亲的order_no+自己的显示order_no*power(32,6-order_level)',
  `order_level` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'order层次',
  `click_times` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被点击的次数',
  `fdn` varchar(31) NOT NULL DEFAULT '' COMMENT 'fdn值',
  `menu_name` varchar(45) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `module_name` varchar(45) NOT NULL DEFAULT '' COMMENT '模块名称',
  `action_name` varchar(31) NOT NULL DEFAULT '' COMMENT 'Action名称',
  `args` varchar(127) NOT NULL DEFAULT '' COMMENT '参数,某些菜单提供默认参数',
  `type` enum('menu','sub_menu','hide_sub_menu','action','hide_action') NOT NULL DEFAULT 'action' COMMENT '菜单类型：菜单、子菜单、对动态隐藏的子菜单、显示动作、后台动作',
  `is_desktop` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `desktop_url` varchar(31) NOT NULL COMMENT '桌面菜单URL',
  `other_info` varchar(127) NOT NULL COMMENT '附加信息',
  PRIMARY KEY (`menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='系统菜单权限表';

-- 系统角色表  控制用户的权限，menu_ids = menu.id+','; --
DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `role_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '角色名',
  `menu_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '菜单ID',
  `shortcut_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '快捷方式ids',
  `desk_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '桌面菜单ids',
  `level` int(11) NOT NULL DEFAULT '1' COMMENT '角色的级别等级，默认为1',
  PRIMARY KEY (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='角色信息表';

-- 部门表
DROP TABLE IF EXISTS `dept`;
CREATE TABLE IF NOT EXISTS `dept` (
  `dept_id` smallint(5) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '部门名',
  `parent_id` smallint(5) unsigned zerofill NOT NULL DEFAULT '00000' COMMENT '上级区域ID',
  `ordernum` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `layer` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '所在层',
  `fdn` varchar(32) NOT NULL DEFAULT '' COMMENT '标识区域关系的串，一般为上级区域的fdn当前id.',
  `full_name` varchar(128) NOT NULL DEFAULT '' COMMENT '全称',
  `user_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1为可以删除，0为不能删除',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`dept_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='部门信息表';

-- 用户表 为每个用户分配一个角色用于控制权限，role_id = role.id; -- 
DROP TABLE IF EXISTS `account`;
CREATE TABLE IF NOT EXISTS `account` (
  `account_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `canton_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '账号所属区域',
  `canton_fdn` varchar(45) NOT NULL DEFAULT '' COMMENT '账号所属区域fdn',
  `login_username` varchar(45) NOT NULL DEFAULT '' COMMENT '系统用户名',
  `login_pwd` varchar(200) NOT NULL DEFAULT '' COMMENT '登录密码',
  `true_name` varchar(45) NOT NULL DEFAULT '' COMMENT '用户本人实际姓名',
  `dept_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '部门ID',
  `dept_fdn` varchar(45) NOT NULL DEFAULT '' COMMENT '部门fdn',
  `tel` varchar(12) NOT NULL DEFAULT '' COMMENT '联系电话',
  `email` varchar(52) NOT NULL DEFAULT '' COMMENT 'Email',
  `address` varchar(45) NOT NULL DEFAULT '' COMMENT '本人地址',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  `shorcut_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '快捷操作id串',
  `menu_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '菜单ids',
  `desk_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '桌面id 串',
  `main_url` varchar(100) NOT NULL DEFAULT '' COMMENT '登陆后转向的url',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '根据实际情况确定:1=>正常,0=>未验证,2=>禁用',
  `user_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:删除',
  `session_id` varchar(100) NOT NULL DEFAULT '' COMMENT '',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '创建时间',
  `login_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '登录时间',
  `active_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '激活时间',
  `is_logout` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已经被要求退出',
  PRIMARY KEY (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='系统用户表';

-- 辅助功能表 --------------------------------------------------------
-- 系统数据变更表
DROP TABLE IF EXISTS `data_change_log`;
CREATE TABLE IF NOT EXISTS `data_change_log` (
  `dcl_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model_name` varchar(63) NOT NULL COMMENT '模型名称',
  `model_name_cn` varchar(63) NOT NULL COMMENT '模型中文名称',
  `module_name` varchar(63) NOT NULL COMMENT '模块名称',
  `action_name` varchar(63) NOT NULL COMMENT '操作名称',
  `event` varchar(8) NOT NULL COMMENT '事件：insert、update、delete',
  `options` varchar(1024) NOT NULL COMMENT '操作的选项',
  `options_ser` varchar(1024) NOT NULL COMMENT 'options的序列化值',
  `data` varchar(1024) NOT NULL COMMENT '操作的选项',
  `data_ser` varchar(1024) NOT NULL COMMENT 'options的序列化值',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `creater_user_name` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人',
  PRIMARY KEY (`dcl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='数据操作信息表';

-- 用户操作信息表
DROP TABLE IF EXISTS `operation_log`;
CREATE TABLE IF NOT EXISTS `operation_log` (
  `ol_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(20) NOT NULL DEFAULT '',
  `action` varchar(20) NOT NULL DEFAULT '',
  `action_name` varchar(40) NOT NULL DEFAULT '' COMMENT 'action中文名称,对应到menu表',
  `ip` varchar(40) NOT NULL DEFAULT '' COMMENT '本机IP_REMOTE_ADDR',
  `options` varchar(1000) NOT NULL DEFAULT '' COMMENT '参数',
  `over_pri` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否越权，越权为1，否则为0',
  `other_info` varchar(255) NOT NULL DEFAULT '',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `creater_user_name` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ol_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='数据操作信息表';

-- 系统数据全文索引表 和 消息表
DROP TABLE IF EXISTS `fulltext_search`;
CREATE TABLE `fulltext_search` (
  `fts_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键，主要为了方便更改消息状态',
  `data_id` int(10) unsigned NOT NULL COMMENT '业务数据主键',
  `object` varchar(45) NOT NULL DEFAULT '' COMMENT 'model的名称',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '要检索的数据',
  `object_title` varchar(45) NOT NULL DEFAULT '' COMMENT 'model的中文表示',
  `message_state` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '消息状态 0:非消息 1:未处理消息 2:已处理消息',
  PRIMARY KEY (`fts_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='全文搜索';


-- 业务数据结构 --------------------------------------------------------
-- 共享文件

DROP TABLE IF EXISTS `share_file`;
CREATE TABLE IF NOT EXISTS `share_file` (
  `sf_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT '' COMMENT '本次上传标题',
  `file_name` varchar(10240) NOT NULL DEFAULT '' COMMENT '原始文件名,用于检索',
  `notes` varchar(1000) DEFAULT '' COMMENT '上传描述',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布人',
  `creater_user_name` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人',
  `creater_canton_fdn` varchar(255) NOT NULL DEFAULT '' COMMENT '所属区域',
  `create_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` TIMESTAMP NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_public` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`sf_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='上传文件记录表';

-- 系统公告
DROP TABLE IF EXISTS `sys_message`;
CREATE TABLE `sys_message` (
  `sm_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` varchar(6000) NOT NULL DEFAULT '',
  `creater_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 职务
DROP TABLE IF EXISTS `duty`;
CREATE TABLE `duty` (
  `duty_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creater_user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`duty_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 菜单点击数量
DROP TABLE IF EXISTS `menu_click`;
CREATE TABLE `menu_click` (
  `menu_id` int unsigned NOT NULL,
  `click_times` bigint unsigned NOT NULL,
  `user_id` int unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='菜单点击数量';
