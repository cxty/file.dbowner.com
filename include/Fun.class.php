<?php
/**
 * 公共函数库
 * @author Cxty
 *
 */
class Fun {
	public $config;
	public function __construct($config = null) {
		$this->config = $config;
	}
	public function GetIP() {
		if (! empty ( $_SERVER ['HTTP_CLIENT_IP'] )) {
			$ip = $_SERVER ['HTTP_CLIENT_IP'];
		} else if (! empty ( $_SERVER ['HTTP_X_FORWARDED_FOR'] )) {
			$ip = $_SERVER ['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER ['REMOTE_ADDR'];
		}
		return $ip;
	}
	/**
	 * 当前URL
	 */
	public function GetThisURL() {
		$protocol = strpos ( strtolower ( $_SERVER ['SERVER_PROTOCOL'] ), 'https' ) === false ? 'http' : 'https';
		$host = $_SERVER ['HTTP_HOST'];
		$script = $_SERVER ['SCRIPT_NAME'];
		$params = $_SERVER ['REQUEST_URI'];
		$page = $_SERVER ['PHP_SELF'];
		return $protocol . '://' . $host . $script . $params;
	}
	
	/**
	 * 取Session值,若Session过期则取Cookie值
	 *
	 * @param unknown_type $key        	
	 * @param unknown_type $config        	
	 * @return Ambigous <NULL, unknown, boolean, string>
	 */
	public function GetSessionValue($key, $_config = null) {
		session_start ();
		if (! isset ( $_config )) {
			$config = $this->config;
		}
		
		$pwd = $config ['public_key'];
		$iv = $config ['public_iv'];
		$prefix = $config ['AUTH_SESSION_PREFIX'];
		
		$reValue = null;
		
		if ($_SESSION) {
			if (isset ( $_SESSION [$key] )) {
				$reValue = $_SESSION [$key];
			} else {
				if ($_COOKIE) {
					if ($this->cookie ( $key, '', array (
							'prefix' => $prefix 
					) )) {
						$reValue = $this->_addslashes ( $this->cookie ( $key ) );
						$des = new DES ( $pwd, $iv );
						$reValue = $des->decrypt ( $reValue );
					}
				}
			}
		}
		return $reValue;
	}
	/**
	 * 设置Session值
	 *
	 * @param unknown_type $key        	
	 * @param unknown_type $value        	
	 * @param unknown_type $exp        	
	 * @param unknown_type $config        	
	 */
	public function SetSessionValue($key, $value, $exp = 0, $_config = null) {
		session_start ();
		
		if (! isset ( $_config )) {
			$config = $this->config;
		}
		
		$pwd = $config ['public_key'];
		$iv = $config ['public_iv'];
		
		if (isset ( $_SESSION )) {
			
			$_SESSION [$key] = $value;
			
			if (isset ( $_COOKIE )) {
				$des = new DES ( $pwd, $iv );
				$value = $des->encrypt ( $value );
				$this->cookie ( $key, $value, array (
						'expire' => $exp 
				) );
			}
		}
	}
	/**
	 * 清除Session
	 */
	public function ClearSession() {
		foreach ( $_SESSION as $key => $val ) {
			unset ( $_SESSION [$key] );
		}
		$this->cookie ( null );
	}
	
	/**
	 * +----------------------------------------------------------
	 * Cookie 设置、获取、清除 (支持数组或对象直接设置) 2009-07-9
	 * +----------------------------------------------------------
	 * 1 获取cookie: cookie('name')
	 * 2 清空当前设置前缀的所有cookie: cookie(null)
	 * 3 删除指定前缀所有cookie: cookie(null,'think_') | 注：前缀将不区分大小写
	 * 4 设置cookie: cookie('name','value') | 指定保存时间: cookie('name','value',3600)
	 * 5 删除cookie: cookie('name',null)
	 * +----------------------------------------------------------
	 * $option 可用设置prefix,expire,path,domain
	 * 支持数组形式:cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
	 * 支持query形式字符串:cookie('name','value','prefix=tp_&expire=10000')
	 */
	public function cookie($name, $value = '', $option = null) {
		// 默认设置
		$config = array (
				'prefix' => '',
				'expire' => 0,
				'path' => '/',
				'domain' => '' 
		);
		// 参数设置(会覆盖黙认设置)
		if (! empty ( $option )) {
			if (is_numeric ( $option ))
				$option = array (
						'expire' => $option 
				);
			elseif (is_string ( $option ))
				parse_str ( $option, $option );
			$config = array_merge ( $config, array_change_key_case ( $option ) );
		}
		// 清除指定前缀的所有cookie
		if (is_null ( $name )) {
			if (empty ( $_COOKIE ))
				return;
				// 要删除的cookie前缀，不指定则删除config设置的指定前缀
			$prefix = empty ( $value ) ? $config ['prefix'] : $value;
			if (! empty ( $prefix )) 			// 如果前缀为空字符串将不作处理直接返回
			{
				foreach ( $_COOKIE as $key => $val ) {
					if (0 === stripos ( $key, $prefix )) {
						setcookie ( $key, '', time () - 3600, $config ['path'], $config ['domain'] );
						unset ( $_COOKIE [$key] );
					}
				}
			} else { // 参数为空 设置也为空 删除所有cookie
				foreach ( $_COOKIE as $key => $val ) {
					setcookie ( $key, '', time () - 3600, $config ['path'], $config ['domain'] );
					unset ( $_COOKIE [$key] );
				}
			}
			return;
		}
		$name = $config ['prefix'] . $name;
		if ('' === $value) {
			return isset ( $_COOKIE [$name] ) ? unserialize ( $_COOKIE [$name] ) : null; // 获取指定Cookie
		} else {
			if (is_null ( $value )) {
				setcookie ( $name, '', time () - 3600, $config ['path'], $config ['domain'] );
				unset ( $_COOKIE [$name] ); // 删除指定cookie
			} else {
				// 设置cookie
				$expire = ! empty ( $config ['expire'] ) ? time () + intval ( $config ['expire'] ) : 0;
				setcookie ( $name, serialize ( $value ), $expire, $config ['path'], $config ['domain'] );
				$_COOKIE [$name] = serialize ( $value );
			}
		}
	}
	/**
	 * addslashes() 别名函数,加强对数组类型(array)的数据处理
	 * 该函数并添加了对MSSQL 的转义字符异常的支持,但前提是SQL 的分界符为’ 即单引号
	 *
	 * @param
	 *        	string | array $string
	 * @param boolean $force
	 *        	是否强制转换转义字符
	 * @return string | array
	 */
	public function _addslashes($string, $force = 0) {
		global $db_type;
		if (! get_magic_quotes_gpc () || $force) {
			if (is_array ( $string )) {
				foreach ( $string as $key => $val ) {
					$string [$key] = $this->_addslashes ( $val, $force );
				}
			} else {
				$string = addslashes ( $string );
			}
		}
		return $string;
	}
	/**
	 * 数组搜索
	 *
	 * @param unknown_type $array        	
	 * @param unknown_type $v        	
	 * @return multitype:unknown
	 */
	public function ArraySearch($array, $v) {
		
		$data = array ();
		
		foreach ( $array as $key => $value ) {
			
			if (is_array ( $value )) {
				
				$result = $this->ArraySearch ( $value, $v );
				
				if (! empty ( $result )) {
					
					$data [$key] = $result;
				
				}
			
			} else {
				
				if ($value == $v) {
					
					$data [$key] = $v;
				
				}
			
			}
		
		}
		
		return $data;
	
	}
	/**
	 * unicode转中文
	 * 
	 * @param unknown_type $str        	
	 */
	public function unescape($str) {
		$str = str_replace ( "\\", "%", $str );
		$str = rawurldecode ( $str );
		preg_match_all ( "/(?:%u.{4})|.{4};|&#\d+;|.+/U", $str, $r );
		$ar = $r [0];
		// rint_r($ar);
		foreach ( $ar as $k => $v ) {
			if (substr ( $v, 0, 2 ) == "%u")
				$ar [$k] = iconv ( "UCS-2", "UTF-8", pack ( "H4", substr ( $v, - 4 ) ) );
			elseif (substr ( $v, 0, 3 ) == "")
				$ar [$k] = iconv ( "UCS-2", "UTF-8", pack ( "H4", substr ( $v, 3, - 1 ) ) );
			elseif (substr ( $v, 0, 2 ) == "&#") {
				echo substr ( $v, 2, - 1 ) . "";
				$ar [$k] = iconv ( "UCS-2", "UTF-8", pack ( "n", substr ( $v, 2, - 1 ) ) );
			}
		}
		return join ( "", $ar );
	}
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
}

?>