<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @param VERIFY
 * -----------------------------------------------------
 * (*) 注意：表达式中需要逗号,则用&comma;代替
 * (*) 正则表达式记得在结尾加上*来表示无长度限制,如username，尽量不在正则表达式中做总长度的限制
 * (*) @string是不会保留前后空格的trim('string')，@text 则会保留前后空格
 * @required,@expression 表示无限制长度
 * @required,@expression,@length 表示只能指定长度
 * @required,@expression,@length_min,@length_max 表示长度在某一范围内
 * @required,@expression,@length_min,@length_max,@ansi 采用ANSI双字节的模式计算长度,一般用于汉字，一个汉字长度为2，如：realname
 * @required,@string @length,@.... TRIM之后的字符串
 * @required,@text @length,@.... 字符串(前后可包含空格或回车)
 * @required,@email @length,@.... Email
 * @required,@date @length,@.... 日期，一律按照中国标准：2013-1-2 / 2013-01-02
 * @required,@time @length,@.... 时间，同上，9:1:5 / 09:01:05
 * @required,@datetime @length,@....  日期时间，同上 2013-1-2 9:1:5 / 2013-01-02 09:01:05
 * @required,@url @length,@....  网址，以 http:// https:// 打头
 * @required,@int/@float/timestamp/timetick,@length_min,... 指定长度范围的数字
 * @required,@int/@float/timestamp/timetick,@value_min,.... 表示其值必须为某范围,比如 0,int,v12,v24 表示其值为整数,并且在12~24以内，如：birthyear
 * @required,@type,@length,... 取数据库Fields中的数据进行校验
 * @required,@data,@length,... 在参数中的数组，如part
 * 
 * @param TYPE
 * -----------------------------------------------------
 * TYPE => true的情况，则表示此字段在Fields数据库中有选项，配合VERIFY使用，如：[verify=>'1,type',type=>'sex']表示必填，为type类型，程序会校验Model_Fields中sex的值
 * TYPE => 'xxx'的情况，则表示取[xxx]名称的值做校验，如servearea取area的值做校验
 *
 * @param MULTI
 * -----------------------------------------------------
 * 多选的情况(MULTI => true)，如果有@length @length_min @length_max的配置 则表示需要限制的checkbox个数
 *
 * @param GREATER
 * -----------------------------------------------------
 * 必须大于某字段，比如dateend > datestart
 *
 * @param TEXT
 * -----------------------------------------------------
 * 此字段的名字
 *
 * @param DATA
 * -----------------------------------------------------
 * 当VERIFY表达式为data时，此参数为其效验值，比如：[verify=>'1,data',data=>['monday','sunday']]
 *
 * @param REMOTE
 * -----------------------------------------------------
 * 比如可以检查用户名是否存在，为网址，如username
 * 
 */
return array(
	/*
	'member' => array(
		'' => array(
			'username' => array(
				'verify' => '1,[a-z0-9\x{4e00}-\x{9fa5}\x{f900}-\x{fa2d}]*,2,50,a', //ansi
				'text' => '用户名',
				'remote' => URL::site('admin/member/check_username_query',NULL,FALSE),
			),
			'realname' => array(
				'verify' => '1,[a-z\s\x{4e00}-\x{9fa5}\x{f900}-\x{fa2d}]*,2,50,a', //ansi
				'text' => '真实姓名',
			),
		),
		'_extra' => array(
			'sex' => array(
				'verify' => '1,type',
				'type' => true,
				'text' => '性别',
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
				'verify' => '1,type',
				'type' => true,
				'text' => '所在地区',
			),
			'phone' => array(
				'verify' => '0,[+]?[\d\\-]*,6,30',
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

		),
		'_multi' => array(
			'speciality' => array(
				'verify' => '0,type',
				'type' => true,
				'multi' => true,
				'text' => '特长',
			),
		),
	),
	'message' => array(
		'' => array(
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
	'comment' => array(
		'' => array(
			'content' => array(
				'verify' => '0,text',
				'text' => '评论内容'
			),
			'score' => array(
				'verify' => '0,int,0,5',
				'text' => '打分'
			),
			'reply' => array(
				'verify' => '0,text',
				'text' => '回复评论'
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