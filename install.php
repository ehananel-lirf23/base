<?php
// Config
$_config = array(
	'{$PROJECT_NAME}' => '', //留空将会自动生成
	'{$DATABASE_HOST}' => '127.0.0.1',
	'{$DATABASE_USER}' => 'root',
	'{$DATABASE_PWD}' => '',
	'{$DATABASE_NAME}' => '', //留空将会自动生成
	'{$PROJECT_HASH}' => '', //留空将会自动生成
);


$files = array(
	'.htaccess',
	'index.php',
	'bootstrap.php',
	'base.sql',
	'config/auth.php',
	'config/encrypt.php',
	'config/database.php',
	'config/publish.php',
);


// Context
function anystring2utf8($str)
{
	$encode = mb_detect_encoding($str,"ASCII,UNICODE,UTF-8,GBK,CP936,EUC-CN,BIG-5,EUC-TW");
	return removeBOM(!in_array($encode, array('UTF-8','ASCII')) ? iconv($encode,'UTF-8//IGNORE',$str) : $str); //移除BOM的UTF-8
}
function guid()
{
	if (function_exists('com_create_guid'))
	{
		return com_create_guid();
	}
	else
	{
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = chr(123)// "{"
				.substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12)
				.chr(125);// "}"
		return $uuid;
	}
}
function removeBOM($str)
{
	return substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf) ? substr($str, 3) : $str;
}
/**
 * 去掉路径中多余的..或/
 * @example
 * Will convert /path/to/test/.././..//..///..///../one/two/../three/filename
 * to ../../one/three/filename
 * 
 * @param  string $path 输入路径
 * @return string       输出格式化之后的路径
 */
function normalize_path($path, $separator = DIRECTORY_SEPARATOR)
{
	$parts = array();// Array to build a new path from the good parts
	$path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
	$path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
	$segments = explode('/', $path);// Collect path segments
	$test = '';// Initialize testing variable
	foreach($segments as $segment)
	{
		if($segment != '.')
		{
			$test = array_pop($parts);
			if(is_null($test))
				$parts[] = $segment;
			else if($segment == '..')
			{
				if($test == '..')
					$parts[] = $test;

				if($test == '..' || $test == '')
					$parts[] = $segment;
			}
			else
			{
				$parts[] = $test;
				$parts[] = $segment;
			}
		}
	}
	return implode($separator, $parts);
}
$action = !empty($_GET['action']) ? $_GET['action'] : '';
if ($action == 'deleteme')
{
	unlink(__FILE__);
	exit('Delete me success');
}
else
{
	$dirname = basename(__DIR__);

	empty($_config['{$PROJECT_NAME}']) && $_config['{$PROJECT_NAME}'] = $dirname;
	empty($_config['{$DATABASE_NAME}']) && $_config['{$DATABASE_NAME}'] = $_config['{$PROJECT_NAME}'];
	empty($_config['{$PROJECT_HASH}']) && $_config['{$PROJECT_HASH}'] = guid();
	empty($_config['{$PROJECT_ID}']) && $_config['{$PROJECT_ID}'] = 'm'.rand(1000000,9999999);

	ob_end_flush();//关闭缓存 
	echo str_repeat("　",256),'<br />'; //ie下 需要先发送256个字节 


	if (!is_dir(__DIR__.'./cache/.kohana_cache') || !is_writable(__DIR__.'./cache/.kohana_cache'))
		echo '<code>',$dirname,'/cache/.kohana_cache</code> directory is not writable.','<br />';
	if (!is_dir(__DIR__.'./cache/smarty_compiled') || !is_writable(__DIR__.'./cache/smarty_compiled'))
		echo '<code>',$dirname,'/cache/smarty_compiled</code> directory is not writable.','<br />';
	if (!is_dir(__DIR__.'./cache/qrcode') || !is_writable(__DIR__.'./cache/qrcode'))
		echo '<code>',$dirname,'/cache/qrcode</code> directory is not writable.','<br />';
	if (!is_writable(__DIR__.'./cache'))
		echo '<code>',$dirname,'/cache</code> directory is not writable.','<br />';
	if (!is_dir(__DIR__.'./logs') || !is_writable(__DIR__.'./logs'))
		echo '<code>',$dirname,'/logs</code> directory is not writable.','<br />';
	if (!is_dir(__DIR__.'./attachments') || !is_writable(__DIR__.'./attachments'))
		echo '<code>',$dirname,'/attachments</code> directory is not writable.','<br />';

	if ( ! @preg_match('/^.$/u', 'ñ'))
		echo '<code>PCRE</code> has not been compiled with UTF-8 support.','<br />';
	if ( ! @preg_match('/^\pL$/u', 'ñ'))
		echo '<code>PCRE</code> has not been compiled with Unicode property support.','<br />';
	if (! function_exists('spl_autoload_register'))
		echo '<code>SPL</code> is either not loaded or not compiled in.','<br />';
	if (! class_exists('ReflectionClass'))
		echo '<code>reflection</code> is either not loaded or not compiled in.','<br />';
	if (! function_exists('filter_list'))
		echo '<code>filter</code> is either not loaded or not compiled in.','<br />';
	if (! extension_loaded('iconv'))
		echo '<code>iconv</code> extension is not loaded.','<br />';
	if (extension_loaded('mbstring') && (ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING))
		echo '<code>mbstring</code> extension is overloading PHP\'s native string functions.','<br />';
	if ( ! function_exists('ctype_digit'))
		echo '<code>ctype</code> extension is not enabled.','<br />';
	if (!isset($_SERVER['REQUEST_URI']) && !isset($_SERVER['PHP_SELF']) && !isset($_SERVER['PATH_INFO']))
		echo 'Neither <code>$_SERVER[\'REQUEST_URI\']</code>, <code>$_SERVER[\'PHP_SELF\']</code>, or <code>$_SERVER[\'PATH_INFO\']</code> is available.','<br />';

	if (! extension_loaded('http'))
		echo '<code>http</code> extension is not loaded.','<br />';
	if (! extension_loaded('curl'))
		echo '<code>curl</code> extension is not loaded.','<br />';
	if (! extension_loaded('mcrypt'))
		echo '<code>mcrypt</code> extension is not loaded.','<br />';
	if (! function_exists('gd_info'))
		echo '<code>GD</code> extension is not loaded.','<br />';
	if (! function_exists('mysql_connect'))
		echo '<code>mysql</code> extension is not loaded.','<br />';
	if (! class_exists('PDO'))
		echo '<code>PDO</code> extension is not loaded.','<br />';

	echo '--------------------------------------','<br />';
	foreach($files as $filename) {
		$content = file_get_contents($filename);
		$content = strtr($content,$_config);
		file_put_contents($filename, $content);
		echo 'Initialize file [',$filename,'] success...','<br />';
		flush(); 
	}
	echo '--------------------------------------','<br />';
	$target_path = normalize_path(MODPATH.'/../static');
	$link_path = normalize_path(APPPATH . '/static/common');
	@unlink($link_path);@rmdir($link_path);
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && version_compare(php_uname('r'), '6.0', '<')) { //Windows Vista以下
		echo exec('"'.APPPATH.'/junction.exe" -d "'.$link_path.'"'),'<br />';
		exec('"'.APPPATH.'/junction.exe" "'.$link_path.'" "'.$target_path.'"', $out);
		echo '<pre>',implode('<br />', $out),'</pre>';
	} else {
		symlink($target_path, $link_path);
	}
	echo 'symlink created!';

	echo '--------------------------------------','<br />';
	echo 'Execute SQL to DATABASE...';
	$link = new mysqli($_config['{$DATABASE_HOST}'],$_config['{$DATABASE_USER}'],$_config['{$DATABASE_PWD}']);
	$link->connect_errno && die('Could not connect: ' . $link->connect_error);
	$sql = anystring2utf8(removeBOM(file_get_contents('base.sql')));
	$link->multi_query($sql);
	$link->errno &&  die('SQL error:'.$link->error);
	echo 'success','<br />';
	$link->close();
	echo '--------------------------------------','<br />';
	
	echo 'All Done....';
	echo 'Please <a href="install.php?action=deleteme">Delete Me</a>';
}
