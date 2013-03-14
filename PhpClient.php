<?php
/**
 * php Curl类。模拟浏览器类访问
 * @author Terry <digihero@gmail.com>
 */
class PhpClient {
	const VERSION = '0.1.1';
	// http methon
	const HEAD = 'HEAD';
	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	// 请求参数
	private $_ch = null;
	private $_cookie_file;
	private $_error;
	private $_auth_user;
	private $_auth_pass;
	private $_postdata;
	private $_url;
	
	// 使用代理服务器
	private $_use_proxy = false;
	private $_proxy_host;
	private $_proxy_port;
	private $_proxy_type = 'HTTP'; // or SOCKS5
	private $_proxy_auth = 'BASIC'; // or NTLM
	private $_proxy_user;
	private $_proxy_pass;

	// 其它参数
	
	public $referer; // 设定referer
	public $connecttimeout = 30; // 连接超时
	public $timeout = 30; // 超时时间
	public $debug = false; // debug模式
	public $followlocation = true; // 是否跟踪跳转
	public $cookiefile; // 用于记录和使用cookie的文件
	public static $boundary = ''; // boundary of multipart
	
	// 结果参数
	private $_header; // response header
	private $_body; // response body
	private $_http_code;
	private $_curl_info;

	// 是否模拟浏览器
	public $analog_browser = false;
	
	// 自定义浏览器、蜘蛛的useragent
	private $_user_agent;
	
	private $_browser_header = array(
		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
		"Cache_control: max-age=0",
		"Accept_language: zh-CN,zh;q=0.8",
		'Accept_charset: utf-8,GBK;q=0.7,*;q=0.3',
		'Connection: keep-alive',
	);
	
	/**
	 * HTTP CURL POST 方法
	 * @param string $url		要请求的地址
	 * @param array $parameters	参数数组
	 * @param boolean $is_ajax	是否是ajax请求
	 * @param boolean $multi	是否是多重提交
	 * @return Ambigous <boolean, string>
	 */
	public function post($url, $parameters = array(), $is_ajax = false, $multi = false)
	{
		return $this->doRequest(self::POST, $url, $parameters, $is_ajax, $multi);
	}
	
	/**
	 * HTTP CURL Get 方法
	 * @param string $url		要请求的地址
	 * @param array $parameters	参数数组
	 * @param boolean $is_ajax	是否是ajax请求
	 * @return Ambigous <boolean, string>
	 */
	public function get($url, $parameters = array(), $is_ajax = false)
	{
		return $this->doRequest(self::GET, $url, $parameters, $is_ajax);
	}
	
	/**
	 * HTTP CURL Put 方法
	 * @param string $url		要请求的地址
	 * @param array $parameters	参数数组
	 * @param boolean $is_ajax	是否是ajax请求
	 * @return Ambigous <boolean, string>
	 */
	public function put($url, $parameters = array(), $is_ajax = false)
	{
		return $this->doRequest(self::PUT, $url, $parameters, $is_ajax);
	}
	
	/**
	 * HTTP CURL Delete 方法
	 * @param string $url		要请求的地址
	 * @param array $parameters	参数数组
	 * @param boolean $is_ajax	是否是ajax请求
	 * @return Ambigous <boolean, string>
	 */
	public function delete($url, $parameters = array(), $is_ajax = false)
	{
		return $this->doRequest(self::DELETE, $url, $parameters, $is_ajax);
	}
	
	/**
	 * 核心执行方法
	 *
	 * @param string $url
	 * @param string $cookie_file
	 * @param array	 $postdate
	 */
	private function doRequest($method, $url, $parameters = array(), $is_ajax = false, $multi = false)
	{
		$this->_url = $url;
		if (!$this->_ch) $this->_ch = curl_init();

		##########################  批量设置公共属性  ########################
		$options = array(
			CURLOPT_RETURNTRANSFER => true, 	// 抑制直接输出页面
			CURLOPT_HEADER => true, // return headers
			CURLOPT_FOLLOWLOCATION => $this->followlocation ? true : false, // follow redirects
			CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
			CURLOPT_CONNECTTIMEOUT => $this->connecttimeout,
			CURLOPT_TIMEOUT => $this->timeout, // timeout on response
			CURLOPT_ENCODING => "", // handle all encodings
			CURLOPT_USERAGENT => $this->getUserAgent(), // who am i
			CURLOPT_AUTOREFERER => true, // set referer on redirect
			CURLOPT_SSL_VERIFYHOST => 0, // don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false, // don't verify ssl
			CURLOPT_VERBOSE => 1,
			//CURLOPT_HEADERFUNCTION => array($this,'getHeader'),
			CURLOPT_FRESH_CONNECT=>true, // 新连接，防止从cache中读取
			CURLINFO_HEADER_OUT=>true,
		);
		
		// 如果使用代理服务器,则设定代理服务器信息
		if ($this->_use_proxy){
			$options[CURLOPT_PROXYTYPE] = $this->_proxy_type == 'HTTP' ? CURLPROXY_HTTP : CURLPROXY_SOCKS5;
			$options[CURLOPT_PROXY] = $this->_proxy_host;
			$options[CURLOPT_PROXYPORT] = $this->_proxy_port;
			if ($this->_proxy_user){
				$options[CURLOPT_PROXYAUTH] = $this->_proxy_auth == 'BASIC' ? CURLAUTH_BASIC : CURLAUTH_NTLM;
				$options[CURLOPT_PROXYUSERPWD] = "[{$this->_proxy_user}]:[{$this->_proxy_pass}]";
			}
		}
		// header define
		$header = array();
		// ajax
		if ($is_ajax){
			$header[] = 'X-Requested-With: XMLHttpRequest';
		}
		
		// use cookie file
		if ($this->cookiefile){
			$options[CURLOPT_COOKIEJAR] = $options[CURLOPT_COOKIEFILE] = $this->cookiefile;
		}
		// referer 如果设置了值，一次性使用
		if ($this->referer){
			$header[] = 'Referer: '.$this->referer;
			$this->referer = '';
		}
		
		// 不同方法针对数据的处理
		$payload = '';
		switch ($method) {
			case self::GET:
				if (!empty($parameters))
					$url = $url . '?' . http_build_query($parameters);
				break;
			default:
				$header = array();
				if (!$multi && (is_array($parameters) || is_object($parameters)) ) {
					$payload = http_build_query($parameters);
				} else {
					$payload = self::build_http_query_multi($parameters);
					$header[] = "Content-Type: multipart/form-data; boundary=" . $this->boundary;
				}
				if ($method == self::POST)
				{
					$options[CURLOPT_POST] = true;
					if (!empty($payload))
						$options[CURLOPT_POSTFIELDS] = $this->_postdata = $payload;
				}
				elseif ($method == self::DELETE || $method == self::PUT)
				{
					$options[CURLOPT_CUSTOMREQUEST] = $method;
					if (!empty($payload))
						$url .= '?'.$payload;
				}
		}
		
		// 模拟浏览器
		if ($this->analog_browser)
		{
			$header = array_merge($this->_browser_header,$header);
		}

		// 设置头部信息
		$options[CURLOPT_HTTPHEADER] = $header;
		// 设置要访问的url
		$options[CURLOPT_URL] = $url;
		//print_r($options);exit;
		curl_setopt_array($this->_ch, $options);

		$response = curl_exec( $this->_ch );
		$this->_curl_info = curl_getinfo($this->_ch);
		$this->_http_code = $this->_curl_info['http_code'];
		
		$errno = curl_errno( $this->_ch );
		if ($errno > 0) {
			$this->_error = curl_error($this->_ch);
			return false;
		}
		
		$header_size = curl_getinfo( $this->_ch, CURLINFO_HEADER_SIZE );
		$this->_header = substr( $response, 0, $header_size );
		$this->_body = substr( $response, $header_size );
		$this->curl_close();
		return $this->_body;
	}
	
	/**
	 * 多重内容提交。用于图片上传等操作
	 * @param array $params
	 * @return string
	 */
	private static function build_http_query_multi($params) {
		if (!$params) return '';
	
		uksort($params, 'strcmp');
	
		$pairs = array();
	
		self::$boundary = $boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';
		$multipartbody = '';
	
		foreach ($params as $parameter => $value) {
	
			if( in_array($parameter, array('pic', 'image')) && $value{0} == '@' ) {
				$url = ltrim( $value, '@' );
				$content = file_get_contents( $url );
				$array = explode( '?', basename( $url ) );
				$filename = $array[0];
	
				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
				$multipartbody .= "Content-Type: image/unknown\r\n\r\n";
				$multipartbody .= $content. "\r\n";
			} else {
				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
				$multipartbody .= $value."\r\n";
			}
	
		}
	
		$multipartbody .= $endMPboundary;
		return $multipartbody;
	}
	
	private function getUserAgent()
	{
		if ($this->_user_agent !== null)
			return $this->_user_agent;
		else
		{
			// 模拟浏览器
			if ($this->analog_browser == true)
			{
				if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT']))
					$this->_user_agent = $_SERVER['HTTP_USER_AGENT'];
				else
					$this->_user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.99 Safari/537.22';
			}
			else
			{
				$user_agent = 'PhpClient ' . self::VERSION . ' (cURL/';
				$curl = curl_version();
				if (isset($curl['version']))
					$user_agent .= $curl['version'];
				else
					$user_agent .= '?.?.?';
				$user_agent .= ' PHP/'. PHP_VERSION . ' (' . PHP_OS . ')';
				$user_agent .= ')';
				$this->_user_agent = $user_agent;
			}
			return $this->_user_agent;
		}
	}
	
	/**
	 * 设置请求的UserAgent
	 * @param string $agent
	 */
	public function setUserAgent($agent)
	{
		if (is_string($agent) && !empty($agent))
			return $this->_user_agent = $agent;
	}

	private function curl_close()
	{
		curl_close($this->_ch);
		$this->_ch = null;
	}

	/**
	 * 设置用于记录和随请求一起提交的存储cookie的文件，绝对路径
	 * @param string $file
	 */
	public function setCookieFile($file)
	{
		$this->_cookie_file = $file;
	}

	/**
	 * 设置代理服务器
	 *
	 * @param string $proxy_host	代理地址
	 * @param string $proxy_port	代理端口
	 * @param string $proxy_type	代理类型 默认'http'
	 * @param string $proxy_auth	代理认证方式，默认'BASIC'
	 * @param string $proxy_user	代理用户
	 * @param string $proxy_pass	代理密码
	 */
	public function setProxy($proxy_host,$proxy_port, $proxy_type = 'HTTP', $proxy_auth = 'BASIC', $proxy_user = '', $proxy_pass = '')
	{
		$this->_proxy_host = $proxy_host;
		$this->_proxy_port = $proxy_port;
		$this->_proxy_type = $proxy_type;
		$this->_proxy_auth = $proxy_auth;
		$this->_proxy_user = $proxy_user;
		$this->_proxy_pass = $proxy_pass;
		$this->_use_proxy = true;
	}

	/**
	 * 获取结果body
	 *
	 */
	public function getBody(){
		return $this->_body;
	}

	/**
	 * 获取结果header
	 *
	 */
	public function getHeader(){
		return $this->_header;
	}

	/**
	 * 获取存储cookie的文件名
	 *
	 */
	public function getCookieFile(){
		return $this->_cookie_file;
	}

	public function getError(){
		if ($this->debug && $this->_error) {
			return '<div style="border: 1px solid red; padding: 0.5em; margin: 0.5em;"><strong>PHPClient Debug:</strong> <pre>'.$this->_error.'</pre></div>';
		}else{
			throw new Exception($this->_error);
			//error_log(date('Y-m-d H:i:s',time()).':'.$this->_url.'|'.$this->_error."\n", 3, dirname(__FILE__)."/../log/curl_error.log");
		}
	}
	
	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter();
		else
			throw new Exception('Property "'.get_class($this).'.'.$name.'" is not defined.');
	}
	
	public function __set($name, $value)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
			return $this->$setter($value);
		else
			throw new Exception('Property "'.get_class($this).'.'.$name.'" is not defined.');
	}
}