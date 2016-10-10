<?php
/**
 * 服务根
 * @author Cxty
 *
 */

class Service {
	public $Unauthorized_User; // 非法用户访问
	public $Unauthorized_IP; // 非法IP访问
	public $DataFormatError; // 数据格式错误
	
	public function __construct() {
		$this->Unauthorized_User = $this->_return ( false, 'Unauthorized User', null );
		$this->Unauthorized_IP = $this->_return ( false, 'Unauthorized IP', null );
		$this->DataFormatError = $this->_return ( false, 'Data Format Error', null );
		
		define ( 'DBO_DBClass_PATH', dirname ( __FILE__ ) ); // 当前文件所在的目录
		require_once(DBO_DBClass_PATH.'/DES.class.php');
		
	}
	
	/**
	 * 加载数据处理类,并返回类对象
	 * 
	 * @param string $ClassName        	
	 * @param
	 *        	$model
	 * @param
	 *        	$config
	 * @return Object new ClassName
	 */
	public function RequireClass($ClassName, $model, $config) {

		if (is_file ( dirname ( __FILE__ ) . '/DBO/' . $ClassName . '.php' ))
			require_once (dirname ( __FILE__ ) . '/DBO/' . $ClassName . '.php');

		return new $ClassName ( $model, $config );
	}
	
	/**
	 * 整理返回值
	 * 
	 * @param bool $state        	
	 * @param string $msg        	
	 * @param string $data        	
	 */
	public function _return($state, $msg, $data) {
		return array (
				'return' => json_encode ( array (
						'state' => $state,
						'msg' => $msg,
						'data' => $data 
				) ) 
		);
	}
	/**
	 * 加密
	 * 
	 * @param string $encrypt        	
	 * @param string $key        	
	 * @param string $iv        	
	 */
	public function _encrypt($encrypt, $key = "", $iv = "") {
		$des = new DES ( $key, $iv );
		return $des->encrypt ( $encrypt );
	}
	/**
	 * 解密
	 * 
	 * @param string $decrypt        	
	 * @param string $key        	
	 * @param string $iv        	
	 */
	public function _decrypt($decrypt, $key = "", $iv = "") {
		$des = new DES ( $key, $iv );
		return $des->decrypt ( $decrypt );
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
		$fun = new Fun ( null );
		return $fun->_addslashes ( $string, $force );
	}
}

?>