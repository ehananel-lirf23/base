<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @example 三种情况
 *
 */
class Model_Message extends Model_Database {

	const SYSTEM = 'system';
	const INBOX = 'inbox';
	const OUTBOX = 'outbox';
	const SAVEBOX = 'savebox';


	public function get_new_list($uid, $page = 1, $pagesize = 0)
	{

		$result = array();
		$hashkey = 'new_list_'.$uid;
		$count = $user_count = 0;
		if (is_null($result = $this->get_cache($hashkey)))
		{
			$query = null;$data = array();$data_last = array();

			$query = DB::select()->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('b.uid','=',DB::expr($uid))->where('a.touid','=',$uid)->and_where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where('a.fromuid','!=',0)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'=',0);
			
			$query->select('a.pmid','a.subject','a.message','a.fromuid','a.touid','a.timeline')->order_by('a.timeline','DESC');
			$t = $query->execute();
			$data = $t->as_array();
			foreach ($data as $key => $value) {
				if (array_key_exists($value['fromuid'],$data_last)) //同用户只取第一条
					continue;
				$data_last[($value['fromuid'])] = $value;
			}
			//echo $query;
			$result = compact('data','data_last');
			$this->set_cache($hashkey,$result);
		}
		extract($result);
		$count = count($data);
		$user_count = count($data_last);
		$pagecount = $count > 0 ? ($pagesize > 0 ? ceil($count / $pagesize) : 1) : 1;
		$page > $pagecount && $page = $pagecount;$page < 1 && $page = 1;

		$data_last = $result['data_last'];
		$data = $pagesize > 0 ? array_slice($result['data'], ($page - 1) * $pagesize,$pagesize) : $result['data'];
		//var_dump($user_count);
		return compact('count','user_count','page','pagesize','pagecount','data','data_last');	
	}

	public function get_new_system_list($uid, $page = 1, $pagesize = 0)
	{
		$user = Model::instance('member')->get($uid);
		//获取在注册时间之后的系统消息
		$query = DB::select()->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('b.uid','=',DB::expr($uid))->where('a.touid','IN',array($uid,0))->and_where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where('a.fromuid','=',0)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'=',0)->and_where('a.timeline','>=',$user['timeline'])->order_by('a.timeline','DESC');
		
		return $this->make_page(
			$query,
			array('a.pmid','a.subject','a.message','a.fromuid','a.touid','a.timeline'),
			'a.pmid',
			'pmid',
			$page,$pagesize);
	}

	public function get_message_list($uid, $type, $page = 1, $pagesize = 0)
	{
		$query = null;
		switch ($type) {

			case Model_Message::SYSTEM://系统短信 touid == 0 / uid && fromuid == 0 系统消息时间大于注册时间
				$user = Model::instance('member')->get($uid);
				$query = DB::select()->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('b.uid','=',DB::expr($uid))->where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where('a.touid','IN',array($uid,0))->and_where('a.fromuid','=',0)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'!=',2)->and_where('a.timeline','>=',$user['timeline'])->order_by('a.timeline','DESC')->order_by('b.status','ASC');
				break;
			case Model_Message::SAVEBOX://草稿箱 fromuid == uid && folder == 'savebox'
				$query = DB::select()->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('b.uid','=',DB::expr($uid))->where('a.folder','=','savebox')->and_where('a.isdelete','!=',1)->and_where('a.fromuid','=',$uid)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'!=',2)->order_by('a.timeline','DESC')->order_by('b.status','ASC');
				break;
			case Model_Message::OUTBOX: //我给别人发送的短信 fromuid == uid
				$query = DB::select()->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('b.uid','=',DB::expr($uid))->where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where('a.fromuid','=',$uid)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'!=',2)->order_by('a.timeline','DESC')->order_by('b.status','ASC');
				break;
			case Model_Message::INBOX://别人给我发送的短信 touid == uid && fromuid != 0
			default:
				$query = DB::select()->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('b.uid','=',DB::expr($uid))->where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where('a.touid','=',$uid)->and_where('a.fromuid','!=',0)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'!=',2)->order_by('a.timeline','DESC')->order_by('b.status','ASC');
				break;
		}
		//echo $query;

		return $this->make_page(
			$query,
			array('a.pmid','a.subject','a.message','a.touid','a.fromuid',array(DB::expr('IFNULL(`b`.`status`,0)'),'status'),'a.timeline'),
			'a.pmid',
			'pmid',
			$page,$pagesize);
	}

	public function get_session_list($uid, $page = 1, $pagesize = 0)
	{
		//only inbox
		$result = array();$count = 0;
		$hashkey = 'session_list_'.$uid;
		if (is_null($result = $this->get_cache($hashkey)))
		{

			$data = array();
			//查询别人给我发的，并且我没有删除，并计算未读数量
			$query1 = DB::select('a.pmid','a.message','a.timeline',array('a.fromuid','uid'),DB::expr(' \'inbox\' AS `type`'),'b.status',DB::expr('CASE IFNULL(`b`.`status`,0) WHEN 0 THEN 1 END AS `unread_count`' ))->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('a.touid','=','b.uid')->where('a.touid','=',$uid)->and_where('a.fromuid','!=',0)->and_where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'!=',2);
			//查询我给别人发的,以及我没有删除，不计算未读数量，状态默认为已读
			$query2 = DB::select('c.pmid','c.message','c.timeline',array('c.touid','uid'),DB::expr(' \'outbox\' AS `type`'),DB::expr('1 AS `status`'),/*'d.status',*/DB::expr('0 AS `unread_count`' ))->from(array('message','c'))->join(array('message_status','d'),'LEFT')->on('c.pmid','=','d.pmid')->on('c.fromuid','=','d.uid')->where('c.fromuid','=',$uid)->where('c.folder','=','inbox')->and_where('c.touid','!=',0)->and_where('c.isdelete','!=',1)->and_where(DB::expr('IFNULL(`d`.`status`,0)'),'!=',2);
			//先在Union里面按照timeline排序,以免在group by后取出的数据不正确
			$query = DB::select()->from(array(DB::expr('((' . $query1 . ') UNION ALL (' . $query2 . ') ORDER BY `timeline` DESC)'),'g'))->group_by('g.uid');
			//取出Group By uid后的第一条记录 Mysql Only
			$query->select('g.pmid','g.message','g.timeline','g.uid', 'g.type', array(DB::expr('IFNULL(`g`.`status`,0)'),'status'),array(DB::expr('SUM(`g`.`unread_count`)'),'unread_count'))->order_by(DB::expr('MAX(`g`.`timeline`)'),'DESC');
		
			$result = $query->execute()->as_array();

			$this->set_cache($hashkey,$result);
		}
	
		return $this->make_page_bydata($result,$page,$pagesize);
	
	}

	public function get_session($uid, $session_uid, $page = 1, $pagesize = 0) 
	{
		//only inbox
		$result = array();
		$hashkey = 'session_'.$uid.'_'.$session_uid;
		if (is_null($result = $this->get_cache($hashkey)))
		{
			//查询$fromuid给我发的，并且我没有删除
			$query1 = DB::select('a.pmid','a.message','a.timeline','a.touid','a.fromuid',DB::expr(' \'inbox\' AS `type`'),'b.status')->from(array('message','a'))->join(array('message_status','b'),'LEFT')->on('a.pmid','=','b.pmid')->on('a.touid','=','b.uid')->where('a.fromuid','=',$session_uid)->and_where('a.touid','=',$uid)->and_where('a.folder','=','inbox')->and_where('a.isdelete','!=',1)->and_where(DB::expr('IFNULL(`b`.`status`,0)'),'!=',2);
			//查询我给$fromuid发的,以及我没有删除
			$query2 = DB::select('c.pmid','c.message','c.timeline','c.touid','c.fromuid',DB::expr(' \'outbox\' AS `type`'),'d.status')->from(array('message','c'))->join(array('message_status','d'),'LEFT')->on('c.pmid','=','d.pmid')->on('c.fromuid','=','d.uid')->where('c.touid','=',$session_uid)->and_where('c.fromuid','=',$uid)->and_where('c.folder','=','inbox')->and_where('c.isdelete','!=',1)->and_where(DB::expr('IFNULL(`d`.`status`,0)'),'!=',2);

			$query = DB::select('g.pmid','g.message','g.timeline','g.touid','g.fromuid','g.type',array(DB::expr('IFNULL(`g`.`status`,0)'),'status'))->from(array(DB::expr('(' . $query1 . ' UNION ALL ' . $query2 . ')'),'g'))->order_by('g.timeline','DESC');

			$result = $query->execute()->as_array('pmid');
//echo $query;
			$this->set_cache($hashkey,$result);
		}

		return $this->make_page_bydata($result,$page,$pagesize);
	}

	public function get($pmid, $uid = 0)
	{
		$result = array();
		$hashkey = $pmid;
		if (is_null($result = $this->get_cache($hashkey)))
		{
			$query = DB::select('a.pmid','a.subject','a.message','a.timeline','a.touid','a.fromuid','a.folder')->from(array('message','a'))->where('a.pmid','=',$pmid)->and_where('a.isdelete','!=',1);
			$result = $query->execute()->current();
			
			if (!empty($result))  //存在
			{
				$result += array('to_status' => 0,'from_status' => 0);
				//接收者状态
				if (!empty($result['touid'])) //如果是系统消息，则无法查找其状态
				{
					$query = DB::select('status')->from('message_status')->where('pmid','=',$pmid)->and_where('uid','=',$result['touid']);
					$status = $query->execute()->get('status');
					$result['to_status'] = intval($status);
				}
				//发送者情况
				if (!empty($result['fromuid'])) //如果发送者是系统，也无法查找其状态
				{
					$query = DB::select('status')->from('message_status')->where('pmid','=',$pmid)->and_where('uid','=',$result['fromuid']);
					$status = $query->execute()->get('status');
					$result['from_status'] = intval($status);
				}
				
			}
			
			$this->set_cache($hashkey,$result);
		}
		if (!empty($result) && $uid > 0)  //存在
		{
			//接收者状态
			$query = DB::select('status')->from('message_status')->where('pmid','=',$pmid)->and_where('uid','=', $uid);
			$result['to_status'] = intval($query->execute()->get('status'));
		}
		//echo $result;
		return $result;
	}

	public function add($subject, $message, $touid = 0, $fromuid = 0)
	{
		$query = DB::insert('message',array('subject','message','timeline','touid','fromuid','folder'))->values(array($subject,$message,time(),$touid,$fromuid,'inbox'));
		$t = $query->execute();
		$pmid = array_shift($t);

		$this->delete_cache('session_list_'.$touid, 'session_'.$touid.'_'.$fromuid, 'session_'.$fromuid.'_'.$touid, 'new_list_'.$touid);

		Model_Log::log(compact('pmid'));
		return $pmid;
	}

	public function save($subject, $message, $touid = 0, $fromuid = 0)
	{
		$query = DB::insert('message',array('subject','message','timeline','touid','fromuid','folder'))->values(array($subject,$message,time(),$touid,$fromuid,'savebox'));
		$t = $query->execute();
		$pmid = array_shift($t);
		Model_Log::log(compact('pmid'));
		return $pmid;
	}

	public function update($pmid, $subject, $message)
	{
		$msg = $this->get($pmid);
		if (empty($msg))
			return FALSE;

		DB::update('message')->set(compact('subject','message'))->where('pmid','=',$pmid)->execute();

		$this->delete_cache('session_list_'.$msg['touid'], 'session_'.$msg['touid'].'_'.$msg['fromuid'], 'session_'.$msg['fromuid'].'_'.$msg['touid'], 'new_list_'.$msg['touid'], $pmid);

		Model_Log::log();
		return TRUE;
	}

	public function send_saved($pmid)
	{
		$msg = $this->get($pmid);
		if (empty($msg) || empty($msg['touid']))
			return FALSE;

		$query = DB::update('message')->set(array('folder' => 'inbox'))->where('pmid','=',$pmid);
		$query->execute();

		$this->delete_cache('session_list_'.$msg['touid'], 'session_'.$msg['touid'].'_'.$msg['fromuid'], 'session_'.$msg['fromuid'].'_'.$msg['touid'], 'new_list_'.$msg['touid'], $pmid);
		Model_Log::log();
		return TRUE;
	}


	public function read($pmid, $uid) 
	{
		$msg = $this->get($pmid);
		if(!$this->is_receiver($pmid, $uid)) //不为接收者，无法标记
			return FALSE;

		$query = DB::select('status')->from('message_status')->where('pmid','=',$pmid)->and_where('uid','=',$uid);
		$status = $query->execute()->current();

		if (empty($msg) || !empty($status))
		{
			$query = DB::update('message_status').set(array('status' => $status['status'] != 2 ? 1 : 2))->where('pmid','=',$pmid)->and_where('uid','=',$uid); //已读标记,如果已经删除,则仍然保持删除状态
		}
		else
		{
			$query = DB::insert('message_status',array('pmid','uid','status'))->values(array($pmid,$uid,1));
		}
		$query->execute();

		$this->delete_cache('session_list_'.$uid, 'session_'.$msg['touid'].'_'.$msg['fromuid'], 'session_'.$msg['fromuid'].'_'.$msg['touid'], 'new_list_'.$uid, $pmid);

		Model_Log::log();
		return TRUE;
	}

	public function unread($pmid, $uid)
	{
		$msg = $this->get($pmid);
		if(empty($msg) || !$this->is_receiver($pmid,$uid)) //不为接收者，无法标记
			return FALSE;

		$query = DB::select('status')->from('message_status')->where('pmid','=',$pmid)->and_where('uid','=',$uid);
		$status = $query->execute()->current();

		if (!empty($status))
		{
			$query = DB::update('message_status').set(array('status' => $status['status'] != 2 ? 0 : 2))->where('pmid','=',$pmid)->and_where('uid','=',$uid); //已读标记,如果已经删除,则仍然保持删除状态
		}
		else
		{
			$query = DB::insert('message_status',array('pmid','uid','status'))->values(array($pmid, $uid, 0));
		}
		$query->execute();

		$this->delete_cache('session_list_'.$uid, 'session_'.$msg['touid'].'_'.$msg['fromuid'], 'session_'.$msg['fromuid'].'_'.$msg['touid'], 'new_list_'.$uid, $pmid);

		Model_Log::log();
		return TRUE;
	}

	public function delete($pmid, $uid, $eraser = FALSE)
	{
		$msg = $this->get($pmid);
		//用户级别删除
		if (empty($msg)) //没有该私信,不允许标记
			return FALSE;
		elseif (empty($msg['touid'])); //系统消息
		elseif (!empty($msg['touid']) && $uid == $msg['touid']); //是接收者
		elseif (!empty($msg['fromuid']) && $uid == $msg['fromuid']); //是发送者
		else //其它情况，不允许删除
			return FALSE;

		$query = DB::select('status')->from('message_status')->where('pmid','=',$pmid)->and_where('uid','=',$uid);
		$status = $query->execute()->current();

		if (!empty($status))
		{
			$query = DB::update('message_status').set(array('status' => 2))->where('pmid','=',$pmid)->and_where('uid','=',$uid); //删除标记
		}
		else
		{
			$query = DB::insert('message_status',array('pmid','uid','status'))->values(array($pmid,$uid,2));
		}
		$query->execute();

		$this->delete_cache('session_list_'.$uid, 'session_'.$msg['touid'].'_'.$msg['fromuid'], 'session_'.$msg['fromuid'].'_'.$msg['touid'], 'new_list_'.$uid, $pmid);

		Model_Log::log();
		return TRUE;
	}

	public function delete_system($pmid)
	{

		$msg = $this->get($pmid);
		if (empty($msg)) //没有该私信,不允许标记
			return FALSE;

		$query = DB::update('message')->set(array('isdelete' => 1))->where('pmid','=',$pmid);
		$query->execute();

		$this->delete_cache('session_list_'.$msg['touid'],'session_list_'.$msg['fromuid'], 'session_'.$msg['touid'].'_'.$msg['fromuid'], 'session_'.$msg['fromuid'].'_'.$msg['touid'], 'new_list_'.$msg['touid'], $pmid);

		Model_Log::log();
		return TRUE; 

		
	}

	public function delete_session($uid,$session_uid)
	{
		$session = $this->get_session($uid,$session_uid);

		foreach ($session['data'] as $value)
		{
			$this->delete($value['pmid'],$uid);
		}
		return TRUE;
	}

	public function delete_byuid($uid)
	{
		$delete_ids = DB::select('touid','fromuid','pmid')->from('message')->where('touid','=',$uid)->or_where('fromuid','=',$uid)->execute()->as_array('pmid');

		DB::delete('message')->where('touid','=',$uid)->execute();
		DB::delete('message')->where('fromuid','=',$uid)->execute();
		DB::delete('message_status')->where('uid','=',$uid)->execute();

		foreach ($delete_ids as $v) 
			$this->delete_cache('session_list_'.$v['touid'],'session_list_'.$v['fromuid'], 'session_'.$v['touid'].'_'.$v['fromuid'], 'session_'.$v['fromuid'].'_'.$v['touid'], 'new_list_'.$v['touid'], $v['pmid']);

		Model_Log::log(compact('sid'));
		return TRUE;
	}

	public function is_receiver($pmid, $uid)
	{
		$result = $this->get($pmid);
		return isset($result['touid']) && (empty($result['touid']) || $uid == $result['touid']); //在有值的情况下，为空或为uid 都表示是接受者
	}

	public function add_system($touid,$template_name,$data)
	{
		$line = Kohana::message('message','templates.'.$template_name);
		if (empty($line) || empty($touid))
			return FALSE;

		$_data = array_flatten($data,'/',':');
		
		$_data[':send_time'] = date('Y-m-d H:i:s');

		$subject = __($line['subject'],$_data);
		$content = __($line['content'],$_data);
		$id = $this->add($subject,$content,$touid,0);
		return $id;
	}
}	

?>