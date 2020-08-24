<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @example 三种情况
 *
 */
class Model_Group extends Model_Database {

	const GROUP_ADMIN = 99;
	const GROUP_VISITOR = 0;
	const GROUP_USER= 1;

	public function get($gid)
	{
		$_group = $this->get_list();
		return $_group[$gid];
	}

	public function get_list()
	{
		$_group = array();
		if (is_null($_group = $this->get_cache('group')))
		{
			$fields = $this->get_fields_list('auth_name');
			$_fields = array_flatten_selector($fields, '*/auth_name,value', FALSE);
			$_base = array_combine($_fields['auth_name'], $_fields['value']); //默认值
			
			$query = DB::select('*')->from('group');
			$_group = $query->execute()->as_array('gid'); //得到组信息
			$query = DB::select('*')->from('group_auth'); //得到权限信息
			$_auth = $query->execute()->as_array();
			foreach($_auth as $item)
			{
				if (array_key_exists($item['gid'], $_group)) 
				{
					if (array_key_exists($item['auth_name'], $fields)) //获取基本值
					switch ($fields[($item['auth_name'])]['type']) {
						case 'boolean':$item['value'] = boolval($item['value']);break;
						case 'number':$item['value'] = $item['value'] + 0;break;
						default:break;
					}
					$_group[$item['gid']][($item['auth_name'])] = $item['value']; //将权限信息添加到组信息中
				}
			}
			foreach($_group as $key => $item)
				$_group[$key] = $item + $_base; //合并基本数组

			$this->set_cache('group', $_group);
		}
		return $_group;
	}

	public function get_auth($uid)
	{
		if (!empty($uid))
		{
			$user = Model::instance('member')->get($uid);
			if (empty($user))
				$uid = 0;
			else
			{
				$_auth = $this->get($user['gid']);
				if (empty($_auth))
					$uid = 0;
				else
				{
					$_member_auth = $this->get_member_auth($uid);
					!empty($_member_auth) && $_auth = array_merge($_auth, $_member_auth);
					return $_auth;
				}
			}
		}
		if (empty($uid))
			return $this->get(0);
		return NULL;
	}

	public function search($fields, $order_by = array('gid', 'ASC'), $page = 1, $pagesize = 0)
	{
		$_fields = array('group_name' => '', 'gid' => array());
		$fields = to_array_selector($fields, 'gid');
		$fields = _extends($fields, $_fields);

		$query = DB::select()->from('group');

		!empty($fields['group_name']) && $query->and_where('group_name','LIKE','%'.$fields['group_name'].'%');
		!empty($fields['gid']) && $query->and_where('gid','IN',$fields['gid']);

		$result = $this->make_page($query, '*', '*', 'gid', $page, $pagesize);

		foreach ($result['data'] as $key => $value) {
			$result['data'][$key] = $this->get($key);
		}
		return $result;
	}

	public function update_all_auth($data, $keys = NULL)
	{
		//将数组组合成 GID => array
		$_data = array();
		foreach ($data as $key => $value) {
			foreach ($value as $gid => $v) {
				$_data[intval($gid)][$key] = $v;
			}
		}

		$_group = $this->get_list();
		foreach ($_group as $gid => $value)
			$this->update_auth($gid, $_data[$gid], $keys);

		Model_Log::log();
		return TRUE;
	}

	public function update_auth($gid, $data, $keys = NULL)
	{
		$fields = $this->get_fields_list('auth_name');
		$keys = !empty($keys) ? _array_selector_subkey($keys) : array_keys($fields);
		$_base = array_fill_keys($keys, NULL); //填充一个基础数组

		$_v = _extends($data, $_base);
		DB::delete('group_auth')->where('gid', '=', $gid)->and_where('auth_name','IN',$keys)->execute();
		foreach($keys as $key)
			DB::insert('group_auth',array('gid','auth_name','value'))->values(array($gid, $key, $_v[$key]))->execute();

		Model_Log::log();
		$this->delete_cache('group');
		return TRUE;
	}

	public function get_member_auth($uid)
	{
		$result = array();
		$hashkey = 'member_auth_'.$uid;
		if (is_null($result = $this->get_cache($hashkey)))
		{
			$query = DB::select('*')->from('group_member')->where('uid','=',$uid);
			$result = $query->execute()->as_array('auth_name','value');
			$this->push_cache($hashkey, $result); //不论值是否FALSE，一律保存
		}
		return $result;
	}

	public function update_memeber_auth($uid, $data, $keys = NULL)
	{
		$fields = $this->get_fields_list('auth_name');
		$keys = !empty($keys) ? _array_selector_subkey($keys) : array_keys($fields);
		$_base = array_fill_keys($keys, NULL); //填充一个基础数组

		$_v = _extends($data, $_base);
		DB::delete('group_member')->where('uid', '=', $uid)->and_where('auth_name','IN',$keys)->execute();
		foreach($keys as $key)
			DB::insert('group_member',array('uid','auth_name','value'))->values(array($uid, $key, $_v[$key]))->execute();

		Model_Log::log();
		return TRUE;
	}

	public function delete_member_auth($uid)
	{
		$uid = to_array($uid);
		DB::delete('group_member')->where('uid','IN',$uid)->execute();

		Model_Log::log();
		return TRUE;
	}

	public function add($data)
	{
		unset($data['gid']);
		
		$query = DB::insert('group',array_keys($data))->values(array_values($data))->execute();
		$gid = array_shift($query);

		$this->delete_cache('group');
		Model_Log::log(compact('gid'));
		return TRUE;
	}

	public function update($gid, $data)
	{
		unset($data['gid']);
		$query = DB::update('group')->set($data)->where('gid','=',$gid)->execute();

		$this->delete_cache('group');
		Model_Log::log();
		return TRUE;
	}

	public function delete($gid, $target_gid)
	{
		if (empty($gid) || empty($target_gid))
			return FALSE;

		$uids = DB::select('uid')->from('member')->where('gid','=',$gid)->execute()->as_array('uid','uid');
		DB::update('member')->set(array('gid' => $target_gid))->where('gid','=',$gid)->execute();
		DB::delete('group')->where('gid','=',$gid)->execute();
		DB::delete('group_auth')->where('gid','=',$gid)->execute();

		//清除member的缓存
		foreach ($uids as $uid) {
			Model::instance('member')->clear_cache($uid);
		}

		$this->delete_cache('group');
		Model_Log::log();
		return TRUE;
	}

	public function get_fields_list($as_array = 'id')
	{
		$result = array();
		$hashkey = 'group_fields_'.$as_array;
		if (is_null($result = $this->get_cache($hashkey)))
		{
			$query1 = DB::select(DB::expr('GROUP_CONCAT(CAST(`b`.`id` AS char(11)) )'))->from(array('group_fields','b'))->where('b.pid','=',DB::expr($this->_db->quote_column('a.id')));
			$query = DB::select('*',array(DB::expr('('.$query1.')'),'children'))->from(array('group_fields','a'));
			$result = $query->execute()->as_array($as_array);
			foreach ($result as $key => $value) 
			{
				switch ($value['type']) { //格式化默认值
					case 'boolean':$result[$key]['value'] = boolval($value['value']);break;
					case 'number':$result[$key]['value'] = $value['value'] + 0;break;
					default:break;
				}
				$result[$key]['children'] = empty($value['children']) ? array() : explode(',', $value['children']);
			}
			$this->set_cache($hashkey, $result);
		}
		return $result;
	}

	public function search_fields($fields, $order_by = array('id', 'ASC'), $page = 1, $pagesize = 0)
	{
		$_fields = array('auth_name' => '', 'text' => '','pid' => array(), 'id' => array());
		$fields = to_array_selector($fields, 'id');
		$fields = _extends($fields, $_fields);

		$query = DB::select()->from('group_fields');

		!empty($fields['auth_name']) && $query->and_where('auth_name','LIKE','%'.$fields['auth_name'].'%');
		!empty($fields['text']) && $query->and_where('text','LIKE','%'.$fields['text'].'%');
		!empty($fields['id']) && $query->and_where('id','IN',$fields['id']);
		!empty($fields['pid']) && $query->and_where('pid','IN',$fields['pid']);

		return $this->make_page($query, '*', '*', 'id', $page, $pagesize);
	}

	public function get_fields($id)
	{
		$result = $this->get_fields_list();
		return $result[$id];
	}

	public function get_fields_byname($auth_name)
	{
		$result = $this->get_fields_list('auth_name');
		return $result[$auth_name];
	}

	public function add_fields($uid, $data)
	{
		$data['auth_name'] = strtolower($data['auth_name']);

		$query = DB::insert('group_fields',array_keys($data))->values(array_values($data))->execute();
		$id = array_shift($query);

		$this->delete_cache('group_fields_id', 'group_fields_auth_name', 'group_fields_text', 'group');
		Model_Log::log(compact('id'));
		return $id;
	}

	public function update_fields($id, $data)
	{
		!empty($data['auth_name']) && $data['auth_name'] = strtolower($data['auth_name']);

		$query = DB::update('group_fields')->set($data)->where('id','=',$id)->execute();

		$this->delete_cache('group_fields_id', 'group_fields_auth_name', 'group_fields_text', 'group');
		Model_Log::log();
		return TRUE;
	}

	public function delete_fields($id)
	{
		$id = to_array($id);
		$query = DB::delete('group_fields')->where('id','IN',$id)->execute();

		$this->delete_cache('group_fields_id', 'group_fields_auth_name', 'group_fields_text', 'group');
		Model_Log::log();
		return TRUE;
	}
}
