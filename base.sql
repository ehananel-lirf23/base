SET NAMES 'UTF8';
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `{$DATABASE_NAME}` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `{$DATABASE_NAME}`;
-- Table structure for {$PROJECT_NAME}_fields
-- ----------------------------
DROP TABLE IF EXISTS `{$PROJECT_NAME}_fields`;
CREATE TABLE `{$PROJECT_NAME}_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('sex','language','speciality') NOT NULL,
  `text` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `extra` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of {$PROJECT_NAME}_fields
-- ----------------------------
INSERT INTO `{$PROJECT_NAME}_fields` VALUES ('1', 'sex', '男', '1', null);
INSERT INTO `{$PROJECT_NAME}_fields` VALUES ('2', 'sex', '女', '2', null);

-- --------------------------------------------------------

--
-- 表的结构 `{$PROJECT_NAME}_group`
--

DROP TABLE IF EXISTS `{$PROJECT_NAME}_group`;
CREATE TABLE IF NOT EXISTS `{$PROJECT_NAME}_group` (
  `gid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `{$PROJECT_NAME}_group`
--

INSERT INTO `{$PROJECT_NAME}_group` (`gid`, `group_name`, `description`) VALUES
(99, '管理员', NULL),
(0, '游客', NULL),
(1, '普通用户', NULL);
UPDATE `{$PROJECT_NAME}_group` SET `gid` = 0 WHERE `group_name` = '游客';
-- --------------------------------------------------------

--
-- 表的结构 `{$PROJECT_NAME}_group_auth`
--

DROP TABLE IF EXISTS `{$PROJECT_NAME}_group_auth`;
CREATE TABLE IF NOT EXISTS `{$PROJECT_NAME}_group_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gid` int(10) unsigned NOT NULL,
  `auth_name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gid` (`gid`,`auth_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户组权限表' AUTO_INCREMENT=1 ;

--
-- 转存表中的数据 `{$PROJECT_NAME}_group_auth`
--

INSERT INTO `{$PROJECT_NAME}_group_auth` (`gid`, `auth_name`, `value`) VALUES
(99, 'allow_delete_group', '1'),
(99, 'allow_delete_member_admin', '1'),
(99, 'allow_update_group', '1'),
(99, 'allow_delete_member_user', '1'),
(99, 'allow_update_member_admin', '1'),
(99, 'allow_update_member_user', '1'),
(99, 'allow_view_setting', '1'),
(99, 'allow_view_group', '1'),
(99, 'allow_view_member', '1'),
(99, 'allow_view_admin', '1'),
(0, 'allow_delete_group', NULL),
(0, 'allow_delete_member_admin', NULL),
(0, 'allow_delete_member_user', NULL),
(0, 'allow_update_group', NULL),
(0, 'allow_view_member', NULL),
(0, 'allow_view_admin', NULL),
(0, 'allow_update_member_user', NULL),
(0, 'allow_update_member_admin', NULL),
(0, 'allow_view_setting', NULL),
(0, 'allow_view_group', NULL),
(1, 'allow_delete_group', NULL),
(1, 'allow_delete_member_admin', NULL),
(1, 'allow_delete_member_user', NULL),
(1, 'allow_update_group', NULL),
(1, 'allow_update_member_admin', NULL),
(1, 'allow_update_member_user', NULL),
(1, 'allow_view_setting', NULL),
(1, 'allow_view_group', NULL),
(1, 'allow_view_member', NULL),
(1, 'allow_view_admin', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `{$PROJECT_NAME}_group_fields`
--

DROP TABLE IF EXISTS `{$PROJECT_NAME}_group_fields`;
CREATE TABLE IF NOT EXISTS `{$PROJECT_NAME}_group_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `auth_name` varchar(250) NOT NULL,
  `text` varchar(250) NOT NULL,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('boolean','number','string','text') NOT NULL DEFAULT 'boolean',
  `value` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `auth_name` (`auth_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- 转存表中的数据 `{$PROJECT_NAME}_group_fields`
--

INSERT INTO `{$PROJECT_NAME}_group_fields` (`id`, `auth_name`, `text`, `pid`, `type`, `value`) VALUES
(1, 'allow_view_admin', '允许查看后台页面', 0, 'boolean', NULL),
(2, 'allow_view_member', '允许浏览用户列表页面', 1, 'boolean', NULL),
(3, 'allow_view_group', '允许浏览用户组页面', 1, 'boolean', NULL),
(4, 'allow_view_setting', '允许查看系统配置页面', 1, 'boolean', NULL),
(5, 'allow_update_member_user', '允许编辑用户的账号', 2, 'boolean', NULL),
(6, 'allow_update_member_admin', '允许创建/编辑管理员的账号', 2, 'boolean', NULL),
(7, 'allow_update_group', '允许新建/编辑用户组', 3, 'boolean', NULL),
(8, 'allow_delete_member_user', '允许删除用户账号', 2, 'boolean', NULL),
(9, 'allow_delete_member_admin', '允许删除管理员账号', 2, 'boolean', NULL),
(10, 'allow_delete_group', '允许删除用户组', 3, 'boolean', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `{$PROJECT_NAME}_group_member`
--

DROP TABLE IF EXISTS `{$PROJECT_NAME}_group_member`;
CREATE TABLE IF NOT EXISTS `{$PROJECT_NAME}_group_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `auth_name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`,`auth_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户权限表' AUTO_INCREMENT=1 ;

-- ----------------------------
-- Table structure for {$PROJECT_NAME}_member
-- ----------------------------
DROP TABLE IF EXISTS `{$PROJECT_NAME}_member`;
CREATE TABLE `{$PROJECT_NAME}_member` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(50) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `gid` int(10) unsigned NOT NULL,
  `ip` int(11) NOT NULL,
  `create_uid` int(10) unsigned DEFAULT NULL,
  `timeline` int(10) unsigned NOT NULL,
  `lastlogin` int(10) unsigned NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`),
  KEY `timeline` (`timeline`),
  KEY `lastlogin` (`lastlogin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of {$PROJECT_NAME}_member
-- ----------------------------
INSERT INTO `{$PROJECT_NAME}_member` VALUES ('1', 'admin', '', '管理员', '99', '0', '0', unix_timestamp(now()), NULL);

-- ----------------------------
-- Table structure for {$PROJECT_NAME}_member_extra
-- ----------------------------
DROP TABLE IF EXISTS `{$PROJECT_NAME}_member_extra`;
CREATE TABLE `{$PROJECT_NAME}_member_extra` (
  `uid` int(10) unsigned NOT NULL,
  `score` int(10) unsigned NOT NULL DEFAULT '0',
  `used_score` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of {$PROJECT_NAME}_member_extra
-- ----------------------------
INSERT INTO `{$PROJECT_NAME}_member_extra` VALUES ('1', '0', '0');

-- ----------------------------
-- Table structure for {$PROJECT_NAME}_member_multi
-- ----------------------------
DROP TABLE IF EXISTS `{$PROJECT_NAME}_member_multi`;
CREATE TABLE `{$PROJECT_NAME}_member_multi` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('language','speciality') NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `value` int(11) NOT NULL,
  `extra` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `{$PROJECT_NAME}_attachment`;
CREATE TABLE `{$PROJECT_NAME}_attachment` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `afid` int(10) unsigned NOT NULL COMMENT '文件ID',
  `filename` varchar(255) NOT NULL COMMENT '显示的文件名',
  `ext` varchar(50) NOT NULL COMMENT '显示的扩展名',
  `src_basename` varchar(255) NOT NULL COMMENT '原始名称',
  `description` text COMMENT '附件描述',
  `uid` int(10) unsigned NOT NULL,
  `timeline` int(10) unsigned NOT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for galaxy_attachment_files
-- ----------------------------
DROP TABLE IF EXISTS `{$PROJECT_NAME}_attachment_files`;
CREATE TABLE `{$PROJECT_NAME}_attachment_files` (
  `afid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `basename` varchar(255) NOT NULL COMMENT '本地文件名',
  `path` varchar(255) NOT NULL COMMENT '本地文件路径',
  `hash` varchar(50) DEFAULT NULL COMMENT '文件的MD5',
  `size` bigint(20) NOT NULL DEFAULT '0' COMMENT '文件大小',
  `timeline` int(10) unsigned DEFAULT NULL COMMENT '生成时间',
  PRIMARY KEY (`afid`),
  UNIQUE KEY `hash_2` (`hash`,`size`),
  KEY `hash` (`hash`) USING BTREE,
  KEY `size` (`size`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `store_log`
--

DROP TABLE IF EXISTS `{$PROJECT_NAME}_log`;
CREATE TABLE IF NOT EXISTS `{$PROJECT_NAME}_log` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `timeline` int(10) unsigned NOT NULL,
  `ip` int(11) NOT NULL,
  `operation` varchar(255) NOT NULL,
  `request` text,
  `data` text,
  PRIMARY KEY (`lid`),
  KEY `operation` (`operation`),
  KEY `uid` (`uid`),
  KEY `timeline` (`timeline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;