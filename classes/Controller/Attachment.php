<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Attachment extends Controller_Template {

	private $is_route;
	public function before()
	{
		//解决flash上传的cookie问题
		$session_id = Encrypt::instance()->decode(trim($_POST['PHPSESSIONID']));
		if (!empty($session_id)) session_id($session_id);

		parent::before();

		$aid = $this->request->param('do');
		empty($_GET['aid']) && !empty($aid) && $this->request->query('aid', $_GET['aid'] = $aid);
		
		$this->is_route = 'attachment' == Route::name($this->request->route());
		$this->model['attachment'] = Model::instance('attachment');
	}

	public function action_download($aid)
	{
		$aid = intval($aid);

		if (empty($aid))
			return $this->error_param();

		$data = $this->model['attachment']->get($aid);

		if (empty($data))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}

		$full_path = $this->model['attachment']->get_real_rpath($data['path']);
		$mime_type = File::mime_by_ext($data['ext']);
		$content_length = $data['size'];
		$last_modified = $data['timeline'];
		$etag = $data['hash'];
		$cache = TRUE;
		$this->response->x_send_file($full_path, $data['displayname'], compact('mime_type', 'etag', 'last_modified', 'content_length', 'cache'));

	}

	public function action_info($aid)
	{
		$aid = intval($aid);

		if (empty($aid))
			return $this->error_param();

		$data = $this->model['attachment']->get($aid);
		unset($data['path'], $data['afid'], $data['uid'], $data['basename']); //array_delete_selector($data, 'path,afid,uid,basename');
		if (empty($data))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}
		return $this->success('',TRUE,$data);
	}

	public function action_index($aid, $width = NULL, $height = NULL, $master = Image::AUTO, $quality = 100)
	{
		$aid = intval($aid);
		if (empty($aid))
			return $this->error_param();

		$data = $this->model['attachment']->get($aid);

		if (empty($data))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}

		if (in_array(strtolower($data['ext']), array('jpg','jpeg','gif','png','bmp')))
		{
			if (!empty($width) || !empty($height))
				return $this->action_resize($aid, $width, $height, $master, $quality);
			else
			{
				require_once Kohana::find_file('vendor', 'Mobile-Detect/Mobile_Detect');
				$mobile_detect = new Mobile_Detect;
	 			if ( $mobile_detect->isMobile() && !$mobile_detect->isTablet() )
					return $this->action_phone($aid);
				else
					return $this->action_preview($aid);
			}
		}
		else
		{
			return $this->action_download($aid);
		}
	}

	public function action_resize($aid, $width = NULL, $height = NULL, $master = Image::AUTO, $quality = 100)
	{
		$aid = intval($aid);
		if (empty($aid))
			return $this->error_param();

		$data = $this->model['attachment']->get($aid);

		if (empty($data))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}

		if (!in_array(strtolower($data['ext']), array('jpg','jpeg','gif','png','bmp')))
			return $this->failure('attachment.failure_resize');

		$new_path = Kohana::$cache_dir.DIRECTORY_SEPARATOR.str_replace('.','[dot]',$data['path']).';'.$width.'x'.$height.';'.$master.';'.$quality.'.'.$data['ext'];
		if (!file_exists($new_path))
		{
			$full_path = $this->model['attachment']->get_real_rpath($data['path']);
			$img = Image::factory($full_path, class_exists('Imagick') ? 'Imagick' : NULL);
			!is_dir($path = dirname($new_path)) && mkdir($path, 0777, TRUE);

			if ((!is_null($width) && $img->width > $width) || (!is_null($height) && $img->height > $height))
			{
				$img->resize($width, $height, $master);
				$img->save($new_path, $quality);
			}
			else
				symlink($full_path, $new_path);
		}
		$mime_type = File::mime_by_ext($data['ext']);
		$content_length = NULL;//$data['size'];
		$last_modified = $data['timeline'];
		$etag = $data['hash'];
		$cache = TRUE;
		$this->response->x_send_file($new_path, NULL, compact('mime_type', 'etag', 'last_modified', 'content_length', 'cache'));
	}

	public function action_phone($aid)
	{
		$aid = intval($aid);
		if (empty($aid))
			return $this->error_param();

		$data = $this->model['attachment']->get($aid);

		if (empty($data))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}

		if (in_array(strtolower($data['ext']), array('jpg','jpeg','gif','png','bmp')))
 			return $this->action_resize($aid, 640, 960);
		else
			return $this->action_preview($aid);
	}

	public function action_preview($aid)
	{
		$aid = intval($aid);
		if (empty($aid))
			return $this->error_param();

		$data = $this->model['attachment']->get($aid);

		if (empty($data))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}

		$full_path = $this->model['attachment']->get_real_rpath($data['path']);
		$mime_type = File::mime_by_ext($data['ext']);
		$content_length = $data['size'];
		$last_modified = $data['timeline'];
		$etag = $data['hash'];
		$cache = TRUE;
		$this->response->x_send_file($full_path, NULL, compact('mime_type', 'etag', 'last_modified', 'content_length', 'cache'));
	}

	public function action_redirect($aid)
	{
		$aid = intval($aid);
		if (empty($aid))
			return $this->error_param();

		$link_path = $this->model['attachment']->get_symlink_url($aid);

		if (empty($link_path))
		{
			$this->response->status(404);
			return $this->failure('attachment.failure_noexists');
		}

		$this->redirect($link_path);
	}

	public function action_swfupload_query()
	{
		$this->checkauth(); //检查是否登录
		$result = $this->model['attachment']->upload($this->user['uid'], 'Filedata');
		if (!is_array($result))
			return $this->failure_attachment($result);
		return $this->success('', FALSE, $result);
	}

	public function action_kindeditor_upload_query()
	{
		$data = array('error' => 0, 'url' => '');
		if (empty($this->user['uid'])) //检查是否登录
			$data = array('error' => 1, 'message' => Kohana::message('common', 'default.failure_unlogin'));
		else
		{
			$result = $this->model['attachment']->upload($this->user['uid'], 'Filedata');
			if (!is_array($result))
			{
				$_config = Kohana::$config->load('attachment');
				$msg = Kohana::message('common','attachment.'.$result.'.content');
				$msg = __($msg, array(':maxsize' => Text::bytes($_config['maxsize']),':ext' => implode(',', $_config['ext'])));
				$data = array('error' => 1, 'message' => $msg);
			} else
				$data['url'] = $this->model['attachment']->get_url($result['aid'], TRUE, $this->is_route ? $result['original_basename'] : NULL);
		}
		return $this->output($data);
	}

	public function action_ueditor_upload_query($action, $start = 0, $size = NULL)
	{
		$data = array();
		$_config = Kohana::$config->load('attachment');

		$page = !empty($size) ? ceil($start / $size) : 1;
		$pagesize = $size;
		switch ($action) {
			case 'config':
				$data = array(
					/* 上传图片配置项 */
					'imageActionName' => 'uploadimage', /* 执行上传图片的action名称 */
					'imageFieldName' => 'Filedata', /* 提交的图片表单名称 */
					'imageCompressEnable' => true, /* 是否压缩图片,默认是true */
					'imageCompressBorder' => 1600, /* 图片压缩最长边限制 */
					'imageUrlPrefix' => '',
					'imageInsertAlign' => 'none', /* 插入的图片浮动方式 */
					'imageAllowFiles' => array_map(function($v) {return '.'.$v;}, $_config['file_type']['image']),
					/* 涂鸦图片上传配置项 */
					'scrawlActionName' => 'uploadscrawl', /* 执行上传涂鸦的action名称 */
					'scrawlFieldName' => 'Filedata', /* 提交的图片表单名称 */
					'scrawlUrlPrefix' => '', /* 图片访问路径前缀 */
					'scrawlInsertAlign' => 'none',
					/* 截图工具上传 */
					'snapscreenActionName' => 'uploadimage', /* 执行上传截图的action名称 */
					'snapscreenUrlPrefix' => '', /* 图片访问路径前缀 */
					'snapscreenInsertAlign' => 'none', /* 插入的图片浮动方式 */
					/* 抓取远程图片配置 */
					'catcherLocalDomain' => array('127.0.0.1', 'localhost', 'img.baidu.com'),
					'catcherActionName' => 'catchimage', /* 执行抓取远程图片的action名称 */
					'catcherFieldName' => 'Filedata', /* 提交的图片列表表单名称 */
					'catcherUrlPrefix' => '', /* 图片访问路径前缀 */
					'catcherAllowFiles' => array_map(function($v) {return '.'.$v;}, $_config['file_type']['image']),
					/* 上传视频配置 */
					'videoActionName' => 'uploadvideo', /* 执行上传视频的action名称 */
					'videoFieldName' => 'Filedata', /* 提交的视频表单名称 */
					'videoUrlPrefix' => '', /* 视频访问路径前缀 */
					'videoAllowFiles' => array_map(function($v) {return '.'.$v;}, $_config['file_type']['video'] + $_config['file_type']['audio']),
					/* 上传文件配置 */
					'fileActionName' => 'uploadfile', /* controller里,执行上传视频的action名称 */
					'fileFieldName' => 'Filedata', /* 提交的文件表单名称 */
					'fileUrlPrefix' => '', /* 文件访问路径前缀 */
					'fileAllowFiles' => array_map(function($v) {return '.'.$v;}, $_config['ext']),
					/* 列出指定目录下的图片 */
					'imageManagerActionName' => 'listimage', /* 执行图片管理的action名称 */
					'imageManagerInsertAlign' => 'none', /* 插入的图片浮动方式 */
					'imageManagerUrlPrefix' => '',
					/* 列出指定目录下的文件 */
					'fileManagerActionName' => 'listfile', /* 执行文件管理的action名称 */
					'fileManagerUrlPrefix' => '',
				);
				break;
			 /* 上传图片 */
			case 'uploadimage':
			/* 上传视频 */
			case 'uploadvideo':
			/* 上传文件 */
			case 'uploadfile':
				$result = $this->model['attachment']->upload($this->user['uid'], 'Filedata');
				$data = !is_array($result) ? array('state' => $this->read_message($result)) : array(
					'state' => 'SUCCESS',
					'url' => $this->model['attachment']->get_url($result['aid'], TRUE, $this->is_route ? $result['original_basename'] : NULL),
					'title' => $result['src_basename'],
					'original' => $result['src_basename'],
					'type' => !empty($result['ext']) ? '.'.$result['ext'] : '',
					'size' => $result['size'],
				);
				break;
			/* 上传涂鸦 */
			case 'uploadscrawl':
				$file_path = tempnam('','');
				$fp = fopen($file_path,'wb+');
				fwrite($fp, base64_decode($_POST['Filedata']));
				fclose($fp);
				$result = $this->model['attachment']->save($this->user['uid'], $file_path, 'scrawl.png');
				$data = !is_array($result) ? array('state' => $this->read_message($result)) : array(
					'state' => 'SUCCESS',
					'url' => $this->model['attachment']->get_url($result['aid'], TRUE, $this->is_route ? $result['original_basename'] : NULL),
					'title' => $result['src_basename'],
					'original' => $result['src_basename'],
					'type' => !empty($result['ext']) ? '.'.$result['ext'] : '',
					'size' => $result['size'],
				);
				break;
			/* 抓取远程文件 */
			case 'catchimage':
				$url = isset($_POST['Filedata']) ? $_POST['Filedata'] : $_GET['Filedata'];
				$url = to_array($url);$list = array();
				foreach ($url as $value) {
					$result = $this->model['attachment']->download($this->user['uid'], $value);
					$list[] = !is_array($result) ? array('state' => $this->read_message($result), 'source' => $value) : array (
						'state' => 'SUCCESS',
						'url' => $this->model['attachment']->get_url($result['aid'], TRUE, $this->is_route ? $result['original_basename'] : NULL),
						'title' => $result['src_basename'],
						'original' => $result['src_basename'],
						'size' => $result['size'],
						'source' => $value,
					);
				}
				$data = array(
					'state'=> !empty($list) ? 'SUCCESS' : 'ERROR',
					'list'=> $list,
				);
				break;
			 /* 列出图片 */
			case 'listimage':
			/* 列出文件 */
			case 'listfile':
				$list = $this->model['attachment']->search(array('ext' => $_config['file_type']['image'], 'duplicate' => TRUE), array('a.timeline' => 'DESC'), $page, $pagesize);
				
				$data = array(
					'state' => 'SUCCESS',
					'list' => array_values(array_map(function($v) {return array('url' => $this->model['attachment']->get_url($v['aid'], TRUE, $this->is_route ? $v['original_basename'] : NULL));}, $list['data'])),
					'start' => $list['page'] * $list['pagesize'],
					'total' => $list['count'],
				);
				break;
			default:
				break;
		}
		return $this->output($data);
	}

	public function action_upload_avatar_query()
	{
		$this->checkauth(); //检查是否登录

		$input = file_get_contents('php://input');
		$data = explode('--------------------', $input);
		//@file_put_contents('./avatar_1.jpg', $data[0]);
		$file_path = tempnam('','');
		$fp = fopen($file_path,'wb+');
		fwrite($fp, $data[0]);
		fclose($fp);

		$attachment = $this->model['attachment']->save($this->user['uid'], $file_path, 'avatar_'.$this->user['uid'].'.jpg');
		$url = $this->model['attachment']->get_url($attachment['aid'], TRUE, $this->is_route ? $attachment['original_basename'] : NULL);
		return $this->success('', $url, array('aid' => $attachment['aid'], 'url' => $url));
	}

	private function read_message($message_field)
	{
		$_config = Kohana::$config->load('attachment');
		$msg = Kohana::message('common','attachment.'.$message_field.'.content');
		return __($msg, array(':maxsize' => Text::bytes($_config['maxsize']),':ext' => implode(',', $_config['ext'])));
	}
}