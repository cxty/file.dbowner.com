<?php
//功能：数据缓存类

//ini_set('display_errors', true);
//error_reporting(E_ALL);

class DBOCache{

protected  $cache = NULL;
	
	private $_config;
	private $_des;

    public function __construct( $config = array(), $type = 'FileCache' ,$des = false) {
		$cacheDriver = 'DBO' . $type;
		require_once(dirname(__FILE__) . '/cache/' . $cacheDriver . '.class.php');
		$this->_config = $config;
		$this->_des = $des;
		$this->cache = new $cacheDriver( $this->_config );
		
    }

	//读取缓存
    public function get($key) {
    	$key = md5($key);
    	$value = $this->cache->get($key);
    	if($value && $this->_des)
    	{
    		$value = $this->Json2Data(trim($this->_decrypt($value,$this->_config['public_key'],$this->_config['public_iv'])));
    	}
		return $value;
    }
	
	//设置缓存
    public function set($key, $value, $expire = 1800) {
    	$key = md5($key);
    	
    	$value = $this->_des ? $this->_encrypt($this->Data2Json($value),$this->_config['public_key'],$this->_config['public_iv']):$value;

		return $this->cache->set($key, $value, $expire);
    }
	
	//自增1
	public function inc($key, $value = 1) {
		$key = md5($key);
		$value = $this->_des ? $this->_encrypt($this->Data2Json($value),$this->_config['public_key'],$this->_config['public_iv']):$value;
		
		return $this->cache->inc($key, $value);    
	}
	
	//自减1
	public function des($key, $value = 1) {
		$key = md5($key);
		$value = $this->_des ? $this->_encrypt($this->Data2Json($value),$this->_config['public_key'],$this->_config['public_iv']):$value;
		
		return $this->cache->des($key, $value);    
	}
	
	//删除
	public function del($key) {
		$key = md5($key);
		
		return $this->cache->del($key);
	}
	
	//清空缓存
    public function clear() {
		return $this->cache->clear();    
	}

	//转换为json
	public function Data2Json($data){
		//echo gettype($data);
		//$_type = is_array($data)?'Array':is_object($data)?'Object':is_string($data)?'String':'';
		return json_encode(array('data'=>$data,'type'=>gettype($data)));
	}
	
	//转换为data
	public function Json2Data($json){
		$_JSON = json_decode($json);
		
		if($_JSON->type=='array')
		{
			return json_decode($json,true)->data;
		}else{
			return $_JSON->data;
		}
		
	}
	/**
	 * 加密
	 * @param string $encrypt
	 * @param string $key
	 * @param string $iv
	 */
	public function _encrypt($encrypt,$key="",$iv="")
	{
		$des = new DES($key,$iv);
		
		return $des->encrypt($encrypt);
	}
	/**
	 * 解密
	 * @param string $decrypt
	 * @param string $key
	 * @param string $iv
	 */
	public function _decrypt($decrypt,$key="",$iv="") {
		$des = new DES($key,$iv);
		return $des->decrypt($decrypt);
	}
}
?>
