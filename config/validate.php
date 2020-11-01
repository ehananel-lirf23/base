<?php defined('SYSPATH') OR die('No direct access allowed.');
return array(
	/*
	'member' => array(
		'edit' => array(
			'username' => array(
				'verify' => '1,string,2,50',
				'text' => '用户名',
				'remote' => URL::site('admin/member/check_username_query',NULL,FALSE),
			),
			'password' => array(
				'verify' => '0,string,6,50',
				'text' => '密码',
			),
			'nickname' => array(
				'verify' => '1,[a-z\s\x{4e00}-\x{9fa5}\x{f900}-\x{fa2d}]*,2,50,a', //ansi
				'text' => '真实姓名',
			),
			'sex' => array(
				'verify' => '1,type',
				'type' => true,
				'text' => '性别',
			),
			'avatar_aid' => array(
				'verify' => '0,int',
				'text' => '头像',
			),
			'birthyear' => array(
				'verify' => '1,int,v1940,v'.date('Y'),
				'text' => '出生年份',
			),
			'birthmonth' => array(
				'verify' => '0,int,v1,v12',
				'text' => '出生年份',
			),
			'area' => array(
				'verify' => '1,int',
				'text' => '所在地区',
			),
			'phone' => array(
				'verify' => '0,[+]?[\d\-]*,6,30',
				'text' => '电话',
			),
			'email' => array(
				'verify' => '0,email,0,250',
				'text' => '电子邮箱',
			),
			'qq' => array(
				'verify' => '0,[\d]*,5,12',
				'text' => 'QQ',
			),
			'introduce' => array(
				'verify' => '0,text',
				'text' => '自我介绍',
			),
			'speciality' => array(
				'verify' => '0,type',
				'type' => true,
				'multi' => true,
				'text' => '特长',
			),
		),
		'search' => array(
			'username' => array(
				'verify' => '0,string',
				'text' => '用户名',
			),
			'nickname' => array(
				'verify' => '0,string',
				'text' => '姓名',
			),
			'gid' => array(
				'verify' => '0,int',
				'text' => '用户组',
			),
			'timeline' => array(
				'verify' => '0,timestamp',
				'text' => '注册时间',
				'multi' => true,
			),
			'lastlogin' => array(
				'verify' => '0,timestamp',
				'text' => '最后登录时间',
				'multi' => true,
			),
		),
	),
	'message' => array(
		'edit' => array(
			'subject' => array(
				'verify' => '0,string,0,250',
				'text' => '主题',
			),
			'message' => array(
				'verify' => '1,text',
				'text' => '内容',
			),
		),
	),
	'fields' => array(
		'edit' => array(
			'id' => array(
				'verify' => '1,int',
				'text' => 'ID',
			),
			'type' => array(
				'verify' => '1,string',
				'text' => '类型',
			),
			'text' => array(
				'verify' => '1,string,1,255',
				'text' => '名称',
			),
			'extra' => array(
				'verify' => '0,string,0,255',
				'text' => '扩展内容',
			),
		),
		'move' => array(
			'original_id' => array(
				'verify' => '1,int',
				'text' => '拖动ID',
			),
			'target_id' => array(
				'verify' => '1,int',
				'text' => '目标ID',
			),
			'move_type' => array(
				'verify' => '1,data',
				'text' => '移动方式',
				'data' => array('prev','next'),
			),
		),
		'enum' => array(
			'type' => array(
				'verify' => '1,[a-z_0-9]*,1',
				'text' => '类别',
				'multi' => TRUE,
			),
			'comment' => array(
				'verify' => '1,string,1',
				'text' => '类别',
				'multi' => TRUE,
			),

		),
	),
	'group' => array(
		'edit' => array(
			'group_name' => array(
				'verify' => '1,string',
				'text' => '组名称',
			),
			'description' => array(
				'verify' => '0,string',
				'text' => '介绍',
			),
		),
		'edit_auth' => array(
			'allow_view_admin' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许查看后台页面',
				'multi' => true,
			),
			'allow_view_member' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许浏览用户列表页面',
				'multi' => true,
			),
			'allow_view_group' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许浏览用户组页面',
				'multi' => true,
			),
			'allow_view_setting' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许查看系统配置页面',
				'multi' => true,
			),
			'allow_update_member_user' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许编辑用户的账号',
				'multi' => true,
			),
			'allow_update_member_admin' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许创建/编辑管理员的账号',
				'multi' => true,
			),
			'allow_update_group' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许编辑用户组',
				'multi' => true,
			),
			'allow_delete_member_user' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许删除用户账号',
				'multi' => true,
			),
			'allow_delete_member_admin' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许删除管理员账号',
				'multi' => true,
			),
			'allow_delete_group' => array(
				'verify' => '0,int,v0,v1',
				'text' => '允许删除用户组',
				'multi' => true,
			),
		),
		'edit_fields' => array(
			'auth_name' => array(
				'verify' => '1,/^allow_([a-z\\d_]*)$/u,6,250',
				'text' => '权限名称',
				'message' => '请以allow_开始，只能输入小写英文、数字、下划线',
			),
			'text' => array(
				'verify' => '1,string',
				'text' => '权限介绍',
			),
			'pid' => array(
				'verify' => '0,int',
				'text' => '父级权限',
			),
			'value' => array(
				'verify' => '0,string',
				'text' => '默认值',
			),
			'type' => array(
				'verify' => '1,data',
				'text' => '类型',
				'data' => array('boolean','number','string','text'),
			),
		),
		'delete' => array(
			'target_gid' => array(
				'verify' => '2,int',
				'text' => '转移的用户组',
				'message' => '删除用户组时，必须将用户转移其它用户组！',
			),
		),
	),
	*/
);