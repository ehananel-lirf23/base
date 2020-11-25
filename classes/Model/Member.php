<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @example 用户库
 *
 */

class Model_Member extends Model_Database {

	public function get($uid)
	{
		$user = array();
		if (empty($uid)) return $user;
		if (is_null($user = $this->get_cache($uid)))
		{
			$query = DB::select('b.*','a.*')->from(array('member','a'))->join(array('member_extra','b'),'LEFT')->on('a.uid','=','b.uid')->where('a.uid','=',$uid);
			$user = $query->execute()->current();
			if (!empty($user))
			{
				$query = DB::select('*')->from(array('member_multi','a'))->where('a.uid','=',$uid);//->and_where('a.type','IN',$this->multi);
				$multi = $query->execute()->as_array();
				foreach ($multi as $key => $value)
					$user[ ($value['type']) ][] = $value['value'];
			}
			$this->set_cache($uid, $user);
		}
		empty($user) && $user = array();
		//将字段的数字转化为有意义的值
		$user = Model::instance('Fields')->fields_to_text($user);
		return $user;
	}
	
	/**
	 * 根据uid数组获取用户
	 *
	 * @param  array $uid_array uid的数组
	 * @param  string $field     筛选字段
	 * @return array            返回用户数组
	 */
	public function get_users($uid_array, $field = '*')
	{
		$result = $this->get_list(array('uid' => $uid_array));
		$users = $result['data'];
		//筛选字段
		if ($field != '*' && !empty($users))
			$users = array_keyfilter_selector($users,'*/'.$field);

		return $users;
	}

	public function get_byusername($username)
	{
		$uid = 0;$hashkey = 'username-'.$username;
		if (is_null($uid = $this->get_cache($hashkey)))
		{
			$query = DB::select('uid')->from('member')->where('username','=',$username);
			$uid = $query->execute()->get('uid');
			!empty($uid) && $this->set_cache($hashkey, $uid);
		}
		return empty($uid) ? FALSE : $this->get($uid);
	}

	/**
	 * 根据OPENID查询用户资料
	 * @param  string  $username     OPENID
	 * @param  string  $access_token 如果是通过OAuth2授权，则需要传递此参数
	 * @param  boolean $cache        是否缓存该资料
	 * @return array                 返回对应资料
	 */
	public function get_wechat($username, $access_token = NULL, $cache = TRUE) {
		if (empty($username))
			return FALSE;

		$result = array();
		$hashkey = 'wechat_' . $username;
		if (!$cache || is_null($result = $this->get_cache($hashkey))) {
			$result = empty($access_token) ? Model_Wechat::instance()->getUserInfo($username) : Model_Wechat::instance()->getOauthUserinfo($access_token, $username);;
			if (isset($result['nickname'])) {
				$aid = Model::instance('attachment')->download(0, $result['headimgurl'], 'wechat-avatar-'.$username, 'jpg');
				$result['avatar_aid'] = $aid['aid'];
				$this->set_cache($hashkey, $result, Date::DAY);
			}
		}
		return $result;
	}

	/**
	 * 更新微信资料(如果没有则添加用户资料)
	 * 
	 * @param  string $username      	OPENID
	 * @param  string $access_token     如果是通过OAuth2授权，则需要传递此参数
	 * @param  integer $update_expire 	多久更新一次?
	 * @return integer                  返回UID
	 */
	public function update_from_wechat($username, $access_token = NULL, $gid = NULL, $update_expire = Date::DAY)
	{
		if (empty($username))
			return FALSE;

		$user = $this->get_byusername($username);
		is_null($gid) && $gid = Model_Group::GROUP_USER;
		$uid = !empty($user) ? $user['uid'] : $this->add(0, array('username' => $username ,'password' => Auth::instance()->hash_password($username, $username.$username[3]), 'nickname' => '', 'gid' => $gid));

		$hashkey = 'update_wechat_'.$uid;
		$last = $this->get_cache($hashkey);
		if (is_null($last) || time() - $last > $update_expire)
		{
			$wechat = $this->get_wechat($username, $access_token);
			if (isset($wechat['nickname']))
			{
				$this->update($uid, array(
					'nickname' => $wechat['nickname'], 
					'sex' => $wechat['sex'],
					'avatar_aid' => $wechat['avatar_aid'],
				));
				$this->set_cache($hashkey, time());
			}
		}
		return $uid;
	}

	public function get_list($fields, $order_by = array('timeline' => 'DESC'), $page = 1,$pagesize = 0)
	{
		return $this->search($fields, $order_by, $page, $pagesize);;
	}

	public function search($fields, $order_by = array('timeline' => 'DESC'), $page = 1, $pagesize = 0)
	{
		$_fields = array( 'keywords' => '', 'nickname' => '', 'username' => '', 'username_list' => array(), 'uid' => array(), 'gid' => array(), 'timeline' => array('min' => NULL, 'max' => NULL, ), 'lastlogin' => array('min' => NULL, 'max' => NULL, ), );
		$fields = to_array_selector($fields,'uid,gid,username_list');
		$fields = _extends($fields, $_fields);

		$query = DB::select()->from(array('member','a'))->join(array('member_extra','b'),'LEFT')->on('a.uid','=','b.uid');

		!empty($fields['keywords']) && $query->and_where_open()->and_where('a.nickname','LIKE','%'.$fields['keywords'].'%')->or_where('a.username','LIKE','%'.$fields['keywords'].'%')->where_close();
		!empty($fields['nickname']) && $query->and_where('a.nickname','LIKE','%'.$fields['nickname'].'%');
		!empty($fields['username']) && $query->and_where('a.username','LIKE','%'.$fields['username'].'%');
		!empty($fields['username_list']) && $query->and_where('a.username','IN',$fields['username_list']);
		!empty($fields['uid']) && $query->and_where('a.uid','IN',$fields['uid']);
		!empty($fields['gid']) && $query->and_where('a.gid','IN',$fields['gid']);
		!empty($fields['timeline']['min']) && $query->and_where('a.timeline','>=',$fields['timeline']['min']);
		!empty($fields['timeline']['max']) && $query->and_where('a.timeline','<=',$fields['timeline']['max'] + 86400);
		!empty($fields['lastlogin']['min']) && $query->and_where('a.lastlogin','>=',$fields['lastlogin']['min']);
		!empty($fields['lastlogin']['max']) && $query->and_where('a.lastlogin','<=',$fields['lastlogin']['max'] + 86400);
		foreach ($order_by as $key => $value)
			$query->order_by($key, $value);

		$result = $this->make_page($query,'a.*,b.*', 'a.uid', 'uid', $page, $pagesize);
		$result = Model::instance('Fields')->fields_to_text($result);
		return $result;
	}

	public function search_multi($fields, $order_by = array('a.timeline' => 'DESC'), $page = 1, $pagesize = 0)
	{
		$_fields = array( 'username' => '', 'uid' => array(), 'type' => array(), 'value' => array(), 'timeline' => array('min' => NULL, 'max' => NULL, ), 'extra' => '', );
		$fields = to_array_selector($fields,'uid,type,value');
		$fields = _extends($fields, $_fields);

		$query = DB::select()->from(array('member','a'))->join(array('member_multi','b'),'INNER')->on('a.uid','=','b.uid');

		!empty($fields['username']) && $query->and_where('a.username','LIKE','%'.$fields['username'].'%');
		!empty($fields['extra']) && $query->and_where('b.extra','LIKE','%'.$fields['extra'].'%');
		!empty($fields['uid']) && $query->and_where('b.uid','IN',$fields['uid']);
		!empty($fields['type']) && $query->and_where('b.type','IN',$fields['type']);
		!empty($fields['value']) && $query->and_where('b.value','IN',$fields['value']);
		!empty($fields['timeline']['min']) && $query->and_where('a.timeline','>=',$fields['timeline']['min']);
		!empty($fields['timeline']['max']) && $query->and_where('a.timeline','<=',$fields['timeline']['max'] + 86400);
		foreach ($order_by as $key => $value)
			$query->order_by($key, $value);

		$result = $this->make_page($query,'b.*', 'id', NULL, $page, $pagesize);
		$result = Model::instance('Fields')->fields_to_text($result);
		return $result;
	}
	
	/**
	 * 新增一个用户
	 *
	 * @param int $create_uid 创建者UID
	 * @param array $data     新建的用户数据
	 * @return  int 新增用户的UID
	 */
	public function add($create_uid, $data)
	{
		$request = Request::initial();
		$_data = array_merge($data, array('timeline' => time(), 'ip' => ip2long($request::$client_ip), 'create_uid' => $create_uid));

		empty($_data['gid']) && $_data['gid'] = Model_Group::GROUP_USER;
		$query = DB::insert('member', array_keys($_data))->values(array_values($_data))->execute();
		$uid = array_shift($query);

		$this->add_extra($uid, array('score' => 0, 'used_score' => 0));

		Model_Log::log(compact('uid'));
		return $uid;
	}

	/**
	 * 添加Extra表数据
	 * @param integer $uid  UID
	 * @param array $data 用户字段数据
	 */
	public function add_extra($uid, $data)
	{
		return $this->update_extra($uid, $data);
	}

	/**
	 * 添加Multi表数据
	 * 
	 * @param integer $uid  UID
	 * @param mixed $keys 需要添加的字段集，为避免误删其它资料，此处必须填写正确的字段集
	 * @param array $data 需要添加的数据
	 * @return boolean 是否添加成功
	 */
	public function add_multi($uid, $keys, $data)
	{
		return $this->update_multi($uid, $keys, $data);
	}
	
	/**
	 * 修改用户数据，无法修改用户积分、货币
	 *
	 * @param  int $uid  需要修改的用户UID
	 * @param  array $data 需要修改的用户数据
	 * @return boolean       是否成功修改
	 */
	public function update($uid, $data)
	{
		unset($data['uid'], $data['create_uid'], $data['timeline'], $data['ip']);

		DB::update('member')->set($data)->where('uid','=',$uid)->execute();

		$this->delete_cache($uid);
		Model_Log::log();
		return $uid;
	}
	/**
	 * 修改 Extra表中的字段
	 * 如果不在，则自动添加一行
	 *
	 * $addition为TRUE时，实际上执行的是：UPDATE `member_extra` SET `score` = `score` + $data['score'] WHERE `uid` = $uid
	 * 
	 * @param  integer $uid  UID
	 * @param  array $data 需要修改的数据
	 * @param boolean $addition 是否是相加操作
	 * @return boolean     返回是否修改成功
	 */
	public function update_extra($uid, $data, $addition = FALSE)
	{
		if (empty($uid)) return FALSE;
		DB::begin();
		$line = DB::select('*')->from('member_extra')->where('uid','=',$uid)->for_update()->execute()->current();
		if (!empty($line))
		{
			$_data = array();
			if ($addition) //相加操作
			{
				unset($line['uid']);
				foreach($data as $key => $value)
					array_key_exists($key, $line) && $_data[$key] = $line[$key] + ($value + 0);
			}
			else
				$_data = &$data;
			!empty($_data) && DB::update('member_extra')->set($_data)->where('uid','=',$uid)->execute();
		}
		else
		{
			$data['uid'] = $uid;
			DB::insert('member_extra', array_keys($data))->values(array_values($data))->execute();
		}
		DB::commit();

		$this->delete_cache($uid);
		Model_Log::log();
		return TRUE;
	}

	/**
	 * 修改Multi表中的数据
	 * @param  integer $uid UID
	 * @param  mixed $keys 需要修改的字段集，为避免误删其它资料，此处必须填写正确的字段集
	 * @param  array $data 需要修改的数据
	 * @return boolean     是否修改成功
	 */
	public function update_multi($uid, $keys, $data)
	{
		if (empty($uid)) return FALSE;
		$query = DB::insert('member_multi',array('uid','type','value','extra'));
		$_keys = _array_selector_subkey($keys);
		foreach ($_keys as $type) {
			DB::delete('member_multi')->where('uid','=',$uid)->and_where('type','=',$type)->execute(); //先清除历史数据
			if (!empty($data[$type]))
				foreach ($data[$type] as $v)
					$query->values(array($uid, $type, $v, 0));
		}
		if (strpos((string)$query,') VALUES (') !== FALSE)
			$query->execute();

		$this->delete_cache($uid);
		Model_Log::log();
		return TRUE;
	}

	/**
	 * 删除用户
	 *
	 * @param  int $uid 需要删除的用户的UID
	 * @return bool      是否成功删除
	 */
	public function delete($uid)
	{
		$data = $this->get($uid);
		DB::delete('member')->where('uid','=',$uid)->execute();
		DB::delete('member_extra')->where('uid','=',$uid)->execute();
		DB::delete('member_multi')->where('uid','=',$uid)->execute();
		//删除其他库的用户数据
		//Model::instance('message')->delete_byuid($uid);

		$this->delete_cache($uid, 'username-'.$data['username']);
		Model_Log::log();
		return TRUE;
	}
	
	/**
	 * 同上delete函数
	 *
	 * @param  int $uid 需要删除的用户的UID
	 * @return bool     是否成功删除
	 */
	public function delete_byuid($uid)
	{
		return $this->delete($uid);
	}

	public function clear_cache($uid)
	{
		$uid = to_array($uid);
		foreach ($uid as $v)
			$this->delete_cache($v);
	}

	/**
	 * 用户名/密码检查函数
	 *
	 * @param  string $username 用户名
	 * @param  string $password 密码
	 * @return int           用户的UID
	 */
	public function check($username, $password)
	{

		$query = DB::select('uid')->from('member')->where('username','=',$username)->and_where('password','=',$password);
		$user = $query->execute()->current();

		$uid = !empty($user) ? $user['uid'] : 0;

		!empty($uid) && DB::update('member')->set(array('lastlogin' => time()))->where('uid','=',$uid)->execute();

		//Model_Log::log(compact('uid'));

		return intval($uid);
	}

	/**
	 * 获取用户是否在线
	 *
	 * @param  int  $uid      用户的UID
	 * @param  int  $expire   掉线时间阈值
	 * @param  boolean $reonline 当为TRUE时,则重新让用户上线,如果只是想查询用户的状态,则传FALSE
	 * @return bool           得到用户是否在线
	 */
	public function online($uid, $expire, $reonline = TRUE)
	{
		if (empty($uid)) return FALSE;

		$last = $this->get_cache('online_'.$uid);
		if (is_null($last) || time() - $last > $expire)
		{
			if ($reonline)
				$this->set_cache('online_'.$uid, time());
			return FALSE;
		}
		else
			return TRUE;
	}
}