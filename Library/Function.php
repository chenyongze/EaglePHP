<?php

/**
 * EaglePHP框架系统公共函数库
 * 
 * @author maojianlw@139.com
 * @since 1.8 - 2012-05-31
 * @link www.eaglephp.com
 */

/**
 * 跳转至指定的路径
 * @param string $url 路径
 * @param int $time 暂停几秒
 * @param string $msg 页面显示的提示信息
 * @return void 直接退出
 */
function redirect($url, $time = 0, $msg = '') {
	if (!headers_sent()) {
		header($time > 0 ? "refresh:{$time};url={$url}" : "Location:{$url}");
	} else {
		$meta = "<meta http-equiv=refresh content={$time};URL={$url}>";
	}

	if ($time > 0) {
		$html = template(L('SYSTEM:auto.jump.tips', array($msg, $time)));
	}
	
	exit ($html . $meta);
}


/**
 * 提示信息模板
 */
function template($message, $title='Notice'){
	$message = nl2br($message);
	$copyright = getCfgVar('cfg_webname');
	$html = <<<EOT
				<!DOCTYPE html>
				<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					<title>{$title} - {$copyright}</title>
					<style type="text/css"> 
						body{font-size:10pt; padding:10pt; margin:0; color:#111; font-family:Arial,sans-serif,Helvetica,"宋体";}
						pre{margin:5px;padding:4px 10px;border:1px dotted #ff9797;background:#fffff2;-moz-border-radius:4px;-webkit-border-radius:4px;border-radius:4px;}
						h1{font-size:14pt; font-weight:bold; padding:0 0 10px 0; line-height:1.2em; margin:0; color:#911; _padding-left:0px;}
						.box{border:1px solid #ccc; padding:10px; background:#ffffd6; line-height:1.4em; border-radius:4px; word-wrap:break-word; width:800px; margin:0 auto;}
					</style>
				</head>
				<body>
					<div class="box">
					    <b>{$title}：</b>
					    <pre>{$message}</pre>
					</div>
				</body>
				</html>
EOT;
	return $html;
}


/**
 * 格式化输出，一般用于调试跟踪
 */
function dump($vars, $label = null, $return = false) {
	if (ini_get('html_errors')) {
		$content = "<pre>\n";
		if ($label !== null && $label !== '') {
			$content .= "<strong>{$label} :</strong>\n";
		}
		$content .= print_r($vars, true);
		$content .= "\n</pre>\n";
	} else {
		$content = "\n";
		if ($label !== null && $label !== '') {
			$content .= $label . " :\n";
		}
		$content .= print_r($vars, true) . "\n";
	}
	if ($return) {
		return $content;
	}

	echo $content;
	return null;
}


/**
 * 
 * 导入文件
 * @param string $path
 * @param bool or string $ext
 * @param string $baseUrl
 */
function import($path, $ext = true, $baseUrl = COM_DIR) {
	$file = $baseUrl.str_replace('.', '/', $path);
	$file .= (is_bool($ext) ? ($ext ? '.class.php' : '.php') : $ext);
	static $f_cache = array();
	$env_name = "IMPORT_{$baseUrl}_{$path}";
	if(!isset($f_cache[$env_name])){
		if(is_file($file)){
			$f_cache[$env_name] = require ($file);
		}else{
			return false;
		}
	}
	return $f_cache[$env_name];
}


/**
 * 获取模型层对象，在除了控制器类外的对象调用
 */
function M($className, $dbFlag = __DEFAULT_DATA_SOURCE__) {
	return Model::getModel($className, $dbFlag);
}


/**
 * 获取配置文件
 */
function C($file_name) {
    static $fileData  = array();
    if(!isset($fileData[$file_name])){
        $file = APP_CONFIG_DIR . $file_name . '.inc.php';
    	if(is_file($file)){
    		$fileData[$file_name] = require ($file);
    	}else{
    		return false;
    	}
    }
	return $fileData[$file_name];
}


/**
 * 文件数据读写(简单数据类型、数组、字符串等)
 * @param string $name
 * @param string $value
 * @param string $path
 */
function F($name, $value='', $path=DATA_DIR){
	static $f_cache = array();
	$fileName = $path.$name.'.php';
	if($value !== ''){
		if(is_null($value)) return File::del($fileName); // 如果传递的值置为null，将删除文件缓存。
		$dir = dirname($fileName);
		if(!is_dir($dir)) mk_dir($dir);
		return File::write($fileName, "<?php\nreturn ".var_export($value,true).";\n?>", File::WRITE);
	}elseif(isset($f_cache[$name])){
		$value = $f_cache[$name];
	}elseif(is_file($fileName)){
		$value = include $fileName;
		$f_cache[$name] = $value;
 	}else{
 		$value = false;
 	}
	return $value;	
}


/**
 * 缓存数据读取和设置
 * @param string $name
 * @param string $value
 * @param int $expire
 * @param string $type
 */
function H($name, $value='', $expire='', $type=''){
    static $_cache = array();
    $flag = "{$type}_{$name}";
    $cache = Cache::getInstance($type, array('expire'=>$expire));
    if(!empty($value)){
        if(is_null($value)){
             $result = $cache->remove($name); // 删除缓存  
             if($result){ unset($_cache[$flag]); }
             return $result;
        }
        $cache->set($name, $value, $expire);
        $_cache[$flag] = $value;
        return true;
    }
    if(isset($_cache[$flag])){
        return $_cache[$flag];
    }else{
        return $_cache[$flag] = $cache->get($name);
    }
}



/**
 * 获取客户端IP
 */
function get_client_ip() {
	if (getenv ('HTTP_CLIENT_IP') && strcasecmp ( getenv ('HTTP_CLIENT_IP'), 'unknown' ))
		$ip = getenv ( 'HTTP_CLIENT_IP' );
	else if (getenv ('HTTP_X_FORWARDED_FOR') && strcasecmp ( getenv ('HTTP_X_FORWARDED_FOR'), 'unknown'))
		$ip = getenv ('HTTP_X_FORWARDED_FOR');
	else if (getenv ('REMOTE_ADDR') && strcasecmp ( getenv ('REMOTE_ADDR'), 'unknown'))
		$ip = getenv ('REMOTE_ADDR');
	else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], 'unknown'))
		$ip = $_SERVER ['REMOTE_ADDR'];
	else
		$ip = 'unknown';
	return ($ip);
}



/**
 * 获取文件绝对路径
 */
function getFileAbsoluteUrl($src, $dir=''){
	$url = "http://{$_SERVER['HTTP_HOST']}".dirname(__SHARE__).str_replace('../', '/', $dir).$src;
	return $url;
}

/**
 * 获取上传文件地址
 */
function getUploadAddr()
{
    $absolutePath = realpath(PUB_DIR.'share/upload/');
    if($absolutePath === false){
    	$absolutePath = realpath(PUB_DIR.__UPLOAD__);
    	if($absolutePath === false)
    	{
        	 $arr = array_filter(explode('/',__UPLOAD__));
        	 $absolutePath = realpath(PUB_DIR.__DS__.$arr[2].__DS__.$arr[3]);
    	}
    }
	return $absolutePath.__DS__;
}


/**
 * 截取utf8字符串
 */
function utf8Substr($str,$from,$len) 
{ 
	return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s','$1',$str); 
} 


/**
 * 抛出异常
 */
function throw_exception($message, $code=0, $type='TraceException'){
	throw new $type($message, $code, true);
}


/**
 * 输出信息，程序终止退出
 */
function halt($data, $attach=null){
	$data = (is_array($data)) ? implode('<br/>', $data) : $data;
	if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
		ob_end_flush();
		$output = json_encode(array('statusCode'=>300, 'message'=>$data, 'attach'=>$attach));
	}else{
		$output = template($data, 'Error Info');	
	} 
	exit($output);
}


/**
 * 自动释放来自客户端的连接
 */
function abortConnect(){
    set_time_limit(0); 
    ignore_user_abort(true);     
    $size = ob_get_length();     
    header("Content-Length: $size"); 
    header('Connection: close');
    ob_end_flush(); 
    ob_flush(); 
    flush();    
    session::writeClose();
}



/**
 * 循环创建目录
 */
function mk_dir($dir, $mode = 0777)
{
    if(empty($dir)) return false;
	if (is_dir($dir)) return true; // || @mkdir($dir,$mode)
	if (!@mk_dir(dirname($dir),$mode)) return false;
	return @mkdir($dir,$mode);
}


/**
 * 递归删除目录
 */
function rm_dir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != '.' && $object != '..') { 
       	 $file = $dir.'/'.$object;
         if (filetype($file) == "dir"){
         	rm_dir($file);	
         }else{
         	unlink($file); 
         }
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
}


/**
 * 支持按字段对数组进行排序
 * @param array $list 要排序的数组
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list,$field, $sortby='asc') {
   if(is_array($list)){
       $refer = $resultSet = array();
       foreach ($list as $i => $data)
           $refer[$i] = &$data[$field];
       switch ($sortby) {
           case 'asc': // 正向排序
                asort($refer);
                break;
           case 'desc':// 逆向排序
                arsort($refer);
                break;
           case 'nat': // 自然排序
                natcasesort($refer);
                break;
       }
       foreach ( $refer as $key=> $val)
           $resultSet[] = &$list[$key];
       return $resultSet;
   }
   return false;
}



/**
 * 自动转换字符集 支持数组转换
 */
function auto_charset($fContents,$from='gbk',$to='utf-8'){
    $from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
    $to       =  strtoupper($to)=='UTF8'? 'utf-8':$to;
    if( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) ){
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if(is_string($fContents) ) {
        if(function_exists('mb_convert_encoding')){
            return mb_convert_encoding ($fContents, $to, $from);
        }elseif(function_exists('iconv')){
            return iconv($from,$to,$fContents);
        }else{
            return $fContents;
        }
    }
    elseif(is_array($fContents)){
        foreach ( $fContents as $key => $val ) {
            $_key =     auto_charset($key,$from,$to);
            $fContents[$_key] = auto_charset($val,$from,$to);
            if($key != $_key )
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else{
        return $fContents;
    }
}


/**
 * CURL请求
 * @param String $url 请求地址
 * @param Array $data 请求数据
 */
function curlRequest($url,$data='',$method='POST',$cookieFile='',$headers='',$connectTimeout = 10,$readTimeout = 5)
{
    $method = strtoupper($method);
    if(!function_exists('curl_init')) return socketRequest($url, $data, $method, $connectTimeout, $readTimeout);
    
	$option = array(
		CURLOPT_URL => $url,
		CURLOPT_HEADER =>0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_CONNECTTIMEOUT => $connectTimeout,
		CURLOPT_TIMEOUT => $readTimeout
	);
	
	if($headers)
	{
		$option[CURLOPT_HTTPHEADER] = $headers;
	}
	
	if($cookieFile)
	{
		$option[CURLOPT_COOKIEJAR] = $cookieFile;
		$option[CURLOPT_COOKIEFILE] = $cookieFile;
		//$option[CURLOPT_COOKIESESSION] = true;
		//$option[CURLOPT_COOKIE] = 'prov=42;city=1';
	}
	
	if($data && $method == 'POST')
	{
		$option[CURLOPT_POST] = 1;
		$option[CURLOPT_POSTFIELDS] = $data;
	}
	
	$ch = curl_init();
	curl_setopt_array($ch,$option);
	$response = curl_exec($ch);
	if(curl_errno($ch) > 0)
	{
		throw_exception("CURL ERROR:$url ".curl_error($ch));
	}
	curl_close($ch);
	return $response;
}


/**
 * http post
 */
function socketRequest($url, $post_string, $method='POST', $connectTimeout = 10, $readTimeout = 5)
{
    $method = strtoupper($method);
    if(is_array($post_string)) $post_string = http_build_query($post_string);
	$urlInfo = parse_url($url);
	$urlInfo["path"] = ($urlInfo["path"] == "" ? "/" : $urlInfo["path"]);
	$urlInfo["port"] = ($urlInfo["port"] == "" ? 80 : $urlInfo["port"]);
	$hostIp = gethostbyname($urlInfo["host"]);

	$urlInfo["request"] =  $urlInfo["path"]	. 
		(empty($urlInfo["query"]) ? "" : "?" . $urlInfo["query"]) . 
		(empty($urlInfo["fragment"]) ? "" : "#" . $urlInfo["fragment"]);

	$fsock = fsockopen($hostIp, $urlInfo["port"], $errno, $errstr, $connectTimeout);
	if (false == $fsock) {
		return false;
	}
	
	$request = $urlInfo["request"];
	if($method == 'GET' && $post_string) $request .= '?'.$post_string;
	    
	/* begin send data */
	$in  = "$method $request HTTP/1.0\r\n";
	$in .= "Accept: */*\r\n";
	$in .= "User-Agent: eaglpehp.com API PHP5 Client 1.1 (non-curl)\r\n";
	$in .= "Host: " . $urlInfo["host"] . "\r\n";
	$in .= "Content-type: application/x-www-form-urlencoded\r\n";
	$in .= "Content-Length: " . strlen($post_string) . "\r\n";
	$in .= "Connection: Close\r\n\r\n";
	$in .= $post_string . "\r\n\r\n";
    
	stream_set_timeout($fsock, $readTimeout);
	if (!fwrite($fsock, $in, strlen($in))) {
		fclose($fsock);
		return false;
	}
	unset($in);

	//process response
	$out = "";
	while ($buff = fgets($fsock, 2048)) {
		$out .= $buff;
	}
	//finish socket
	fclose($fsock);
	$pos = strpos($out, "\r\n\r\n");
	$head = substr($out, 0, $pos);		//http head
	$status = substr($head, 0, strpos($head, "\r\n"));		//http status line
	$body = substr($out, $pos + 4, strlen($out) - ($pos + 4));		//page body

	if (preg_match("/^HTTP\/\d\.\d\s([\d]+)\s.*$/", $status, $matches)) {
		if (intval($matches[1]) / 100 == 2) {//return http get body
			return $body;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * 把返回的数据集转换成Tree
 * @access public
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk='id',$pid = 'pid',$child = '_child',$root=0)
{
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 在数据列表中搜索
 * @access public
 * @param array $list 数据列表
 * @param mixed $condition 查询条件
 * 支持 array('name'=>$value) 或者 name=$value
 * @return array
 */
function list_search($list,$condition) {
    if(is_string($condition))
        parse_str($condition,$condition);
    // 返回的结果集合
    $resultSet = array();
    foreach ($list as $key=>$data){
        $find   =   false;
        foreach ($condition as $field=>$value){
            if(isset($data[$field])) {
                if(0 === strpos($value,'/')) {
                    $find   =   preg_match($value,$data[$field]);
                }elseif($data[$field]==$value){
                    $find = true;
                }
            }
        }
        if($find)
            $resultSet[]     =   &$list[$key];
    }
    return $resultSet;
}

/**
 * 检查文件或目录大小
 * @param string $indir
 */
function checkFileSize($path)
{
    static $total_size = 0;
    $dir = opendir($path);
    while($file = readdir($dir))
    {
        if(!preg_match('#^\.#', $file))
        {
            $file_path = $path.'/'.$file;
            if(is_dir($file_path)) checkFileSize($file_path);
            else $total_size += filesize($file_path);
        }
    }
    return $total_size;
}


/**
 * 单位自动转换函数
 * @param float $size
 */
function getFileSize($size)
{ 
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte
    
    if($size < $kb)
    { 
        return $size.' B';
    }
    else if($size < $mb)
    { 
        return round($size/$kb,2).' KB';
    }
    else if($size < $gb)
    { 
        return round($size/$mb,2).' MB';
    }
    else if($size < $tb)
    { 
        return round($size/$gb,2).' GB';
    }
    else
    { 
        return round($size/$tb,2).' TB';
    }
}

/**
 * 获取系统配置文件变量
 */
function getCfgVar($varname)
{
   static $config = null;
   if(!$config)
   {
        $config = import('Config.SysInfo', false, ROOT_DIR);
   }
   return $config[$varname];
}


/**
 * 获取某个星期的日期范围
 * @param string $gdate
 * @param int $first
 */
function getWeek($gdate = '', $first = 0)
{
    if (!$gdate) $gdate = date('Y-m-d');
    $w  = date('w', strtotime($gdate));
    $dn = $w ? $w - $first : 6;
    $st = date('Y-m-d', strtotime("$gdate-$dn days"));
    $en = date('Y-m-d', strtotime("$st +6 days"));
    return array($st, $en);
}

/**
 * 发送邮件函数
 * @param string $to 收件人邮箱，多个收件人用逗号分隔
 * @param string $subject 主题
 * @param string $message 消息
 * @param string $attach 附件，多个附件用逗号分隔
 * @param string $username 用户名
 * @param string $password 密码
 * @param string $host 主机
 * @param string $port 端口
 * @return bool
 */
function sendMail($to, $subject, $message, $attach='', $username='', $password='', $host='', $port=''){
     if(!$to || !$message) return false;
     $mailer = new Mailer();
     $mailer->IsSMTP();
     $mailer->Host = ($host ? $host : getCfgVar('cfg_smtp_server'));
     $mailer->Port = ($port ? $port : getCfgVar('cfg_smtp_port'));
     $mailer->SMTPAuth = getCfgVar('cfg_sendmail_bysmtp');
     $mailer->SMTPDebug = getCfgVar('cfg_debug_mode');
     $mailer->CharSet = 'utf-8';
     $mailer->Username = ($username ? $username : getCfgVar('cfg_smtp_usermail'));
     $mailer->Password = ($password ? $password : getCfgVar('cfg_smtp_password'));
     $to_arr = explode(',', $to);
     foreach($to_arr as $email){
         $mailer->AddAddress($email);
     }
     if(!empty($attach)){
         $attach_arr = explode(',', $attach);
         foreach ($attach_arr as $att){
              $mailer->AddAttachment($att);
         }
     }
     if($username){
         $name_arr = explode('@', $username);
         $name = $name_arr[0];
     }else{
         $name = getCfgVar('cfg_smtp_user');
     }
     $mailer->SetFrom($mailer->Username, $name);
     $mailer->Subject = $subject;
     $mailer->MsgHTML($message);
     return $mailer->Send();
}

/**
 * 执行系统命令
 * @param string $commond
 */
function execute($commond) {
	$result = null;
	if ($commond) {
		if(function_exists('system')) {
			@ob_start();
			@system($commond);
			$result = @ob_get_contents();
			@ob_end_clean();
		} elseif(function_exists('passthru')) {
			@ob_start();
			@passthru($commond);
			$result = @ob_get_contents();
			@ob_end_clean();
		} elseif(function_exists('shell_exec')) {
			$result = @shell_exec($commond);
		} elseif(function_exists('exec')) {
			@exec($commond, $result);
			$result = join("\n", $result);
		} elseif(@is_resource($fop = @popen($commond, "r"))) {
			while(!@feof($fop)) {
				$result .= @fread($fop, 1024); 
			}
			@pclose($fop);
		}
	}
	return $result;
}

/**
 * 获取语言文本
 * @param string $key
 */
function L($key, $params = array())
{
    return I18n::getMessage($key, $params);
}

