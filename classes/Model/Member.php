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
		{
			$users = array_keyfilter_selector($users,'*/'.$field);
		}

		return $users;
	}

	public function get_byusername($username)
	{
		$query = DB::select('uid')->from('member')->where('username','=',$username);
		$uid = $query->execute()->get('uid');
		return empty($uid) ? FALSE : $this->get($uid);
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

		$result = $this->make_page($query,'a.*', 'a.uid', 'uid', $page, $pagesize);
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

		Model_Log::log(compact('uid'));
		return $uid;
	}

	public function add_extra($uid, $data)
	{
		return $this->update_extra($uid, $data);
	}

	public function add_multi($uid, $keys, $data)
	{
		return $this->update_multi($uid, $keys, $data);
	}
	
	/**
	 * 修改用户数据，无法修改用户积分、货币
	 *
	 * @param  int $uid  需要修改的用户UID
	 * @param  array $data 需要修改的用户数据
	 * @return bool       是否成功修改
	 */
	public function update($uid, $data)
	{
		unset($data['uid'], $data['create_uid'], $data['timeline'], $data['ip']);

		DB::update('member')->set($data)->where('uid','=',$uid)->execute();

		$this->delete_cache($uid);
		Model_Log::log();
		return $uid;
	}
	
	public function update_extra($uid, $data)
	{
		$this->_db->begin();
		$query = DB::select('*')->from('member_extra')->where('uid','=',$uid);
		$line = $this->_db->query(Database::SELECT, $query . ' FOR UPDATE');
		if (!empty($line))
			DB::update('member_extra')->set($data)->where('uid','=',$uid)->execute();
		else
			DB::insert('member_extra', array_keys($data))->values(array_values($data))->execute();
		$this->_db->commit();

		$this->delete_cache($uid);
		Model_Log::log();
		return TRUE;
	}

	public function update_multi($uid, $keys, $data)
	{
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
		DB::delete('member')->where('uid','=',$uid)->execute();
		DB::delete('member_extra')->where('uid','=',$uid)->execute();
		DB::delete('member_multi')->where('uid','=',$uid)->execute();
		//删除其他库的用户数据
		//Model::instance('message')->delete_byuid($uid);

		$this->delete_cache($uid);
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
		$this->delete_cache($uid);
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

		Model_Log::log(compact('uid'));

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