<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @example 三种情况
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
				$t = $query->execute()->as_array();
				foreach ($t as $key => $value) {
					$user[ ($value['type']) ][] = $value['value'];
				}
				
			}
			$this->set_cache($uid,$user);
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
		$_fields = array( 'keywords' => '', 'nickname' => '', 'username' => '', 'uid' => array(), 'gid' => array(), 'timeline' => array('min' => NULL, 'max' => NULL, ), 'lastlogin' => array('min' => NULL, 'max' => NULL, ), );
		$fields = to_array_selector($fields,'uid,gid,area');
		$fields = _extends($fields, $_fields);

		$query = DB::select()->from(array('member','a'))->join(array('member_extra','b'),'LEFT')->on('a.uid','=','b.uid');

		!empty($fields['keywords']) && $query->and_where_open()->and_where('a.nickname','LIKE','%'.$fields['keywords'].'%')->or_where('a.username','LIKE','%'.$fields['keywords'].'%')->where_close();
		!empty($fields['nickname']) && $query->and_where('a.nickname','LIKE','%'.$fields['nickname'].'%');
		!empty($fields['username']) && $query->and_where('a.username','LIKE','%'.$fields['username'].'%');
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
	/**
	 * 新增一个用户
	 *
	 * @param int $create_uid 创建者UID
	 * @param array $data     新建的用户数据
	 * @return  int 新增用户的UID
	 */
	public function add($uid, $data)
	{
		$request = Request::initial();
		$_data = array_merge($data, array('timeline' => time(), 'ip' => ip2long($request::$client_ip)));

		empty($_data['gid']) && $_data['gid'] = Model_Group::GROUP_USER;
		$query = DB::insert('member', array_keys($_data))->values(array_values($_data))->execute();
		$uid = array_shift($query);

		$_extra = array( 'uid' => $uid, );
		//DB::insert('member_extra',array_keys($_extra))->values(array_values($_extra))->execute();

		Model_Log::log(compact('uid'));
		return $uid;
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
	/*
		$user = $this->get($uid);

		unset($data['uid'],$data['timeline'],$data['ip']);
		$_data = Validate::instance()->data_chunk($data,'member');
		//print_r($_data);

		if (empty($user))
		{
			$request = Request::initial();
			$_data[''] = array_merge($_data[''], array('timeline' => time(),'ip' => ip2long($request::$client_ip),'uid' => $uid));
			$_data['_extra']['uid'] = $uid;

			DB::insert('member', array_keys($_data['']))->values(array_values($_data['']))->execute();
			if (!empty($_data['_extra']))
				DB::insert('member_extra',array_keys($_data['_extra']))->values(array_values($_data['_extra']))->execute();
		}
		else
		{
			if (!empty($_data['']))
				DB::update('member')->set($_data[''])->where('uid','=',$uid)->execute();

			if (!empty($_data['_extra']) && DB::select('uid')->from('member_extra')->where('uid','=',$uid)->execute()->count() > 0)
			{
				DB::update('member_extra')->set($_data['_extra'])->where('uid','=',$uid)->execute();
			}
			elseif (!empty($_data['_extra']))
			{
				$_data['_extra']['uid'] = $uid;
				DB::insert('member_extra',array_keys($_data['_extra']))->values(array_values($_data['_extra']))->execute();
			}
		}
		//操作多选
		if (!empty($_data['_multi']))
		{
			$query = DB::insert('member_multi',array('uid','type','value','extra'));
			foreach ($_data['_multi'] as $type => $values) {
				DB::delete('member_multi')->where('uid','=',$uid)->and_where('type','=',$type)->execute(); //先清除历史数据
				foreach ($values as $v) {
					$query->values(array($uid,$type,$v,0));
				}
			}
			if (strpos((string)$query,') VALUES (') !== FALSE)
				$query->execute();
		}
		$this->delete_cache($uid);
		if (!empty($user)) //add的LOG由add来控制
			Model_Log::log();
		return $uid;
	*/
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
		$this->_db->query(Database::SELECT, $query . ' FOR UPDATE');
		DB::update('member_extra')->set($data)->where('uid','=',$uid)->execute();
		$this->_db->commit();

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