<?php
/**
 * 文件服务器远程管理类,webservice
 * @author Cxty
 *
 */
require_once dirname ( __FILE__ ) . '/conf/config.php';
require_once dirname ( __FILE__ ) . '/include/mysql.class.php';
require_once dirname ( __FILE__ ) . '/include/mongodb.class.php';
require_once dirname ( __FILE__ ) . '/include/thumb.class.php';
require_once dirname ( __FILE__ ) . '/include/Fun.class.php';
require_once dirname ( __FILE__ ) . '/include/ManageService.php';
require_once dirname ( __FILE__ ) . '/include/DBOModel.class.php';
require_once dirname ( __FILE__ ) . '/include/DES.class.php';

class soap {
	public $conn = null;
	public $fun = null;
	public $conn_re = array();
	public $config; //全局配置
	static $global; //静态变量，用来实现单例模式
	
	public function __construct() {
		
		//参数配置
		if(!isset(self::$global['config'])){
			global $config;
			self::$global['config']=$config;
		}
		$this->config=self::$global['config'];//配置
		//数据库模型初始化
		if (! isset ( self::$global ['conn'] )) {
			self::$global ['conn'] = new DBOModel ( $this->config ); //实例化数据库模型类
		}
		$this->conn = self::$global ['conn']; //数据库模型对象
		
		$this->fun = new Fun ();
		$this->conn_re = array (
				'state' => false 
		);
		
	}
	
	public function manage()
	{
		ini_set("soap.wsdl_cache_enabled", "0");
		$server=new SoapServer(dirname ( __FILE__ ).'/api/ManageService.wsdl',array('soap_version' => SOAP_1_2));
		//$ManageService = new ManageService($this->config,$this->conn);
		$server->setClass("ManageService");
		$server->handle();
	}
}

$soap = new soap();
$soap->manage();
?>