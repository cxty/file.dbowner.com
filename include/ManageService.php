<?php
/**
 * 远程控制台调用接口操作类
 * @author Cxty
 *
 */
require_once dirname ( __FILE__ ) . '/Fun.class.php';
require_once dirname ( __FILE__ ) . '/Service.class.php';

class ManageService extends Service {
	public $conn = null;
	public $fun = null;
	public $user = '';
	public $pwd = '';
	public $iv = '';
	public $authorized = false;
	public $ClientIP = '';
	public $config;
	static $global; // 静态变量，用来实现单例模式
	
	public function __construct() {
		
		// 参数配置
		if (! isset ( self::$global ['config'] )) {
			global $config;
			self::$global ['config'] = $config;
		}
		$this->config = self::$global ['config']; // 配置
		                                          // 数据库模型初始化
		if (! isset ( self::$global ['conn'] )) {
			self::$global ['conn'] = new DBOModel ( $this->config ); // 实例化数据库模型类
		}
		$this->conn = self::$global ['conn']; // 数据库模型对象
		
		$this->fun = new Fun ();
		$conn_re = array (
				'state' => false 
		);
		
		$this->user = $this->config ['SOAP_SERVER_USER'];
		$this->pwd = $this->config ['SOAP_SERVER_PWD'];
		$this->iv = $this->config ['SOAP_SERVER_IV'];
		
		$this->ClientIP = $this->fun->GetIP ();
		
		if (! in_array ( $this->ClientIP, $this->config ['SOAP_SERVER_CLIENTIP'] )) {
			$this->authorized = false;
			return $this->Unauthorized_IP;
		}
	}
	/**
	 * 接口鉴权
	 *
	 * @param array $a        	
	 * @throws SoapFault
	 */
	public function Auth($a) {
		if ($a->user === $this->user) {
			$this->authorized = true;
			return $this->_return ( true, 'OK', null );
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 负责data加密
	 *
	 * @see Service::_return()
	 */
	public function _return($state, $msg, $data) {
		if (isset ( $data )) {
			return parent::_return ( $state, $msg, $this->_encrypt ( json_encode ( array (
					'data' => $data 
			) ), $this->pwd, $this->iv ) );
		} else {
			return parent::_return ( $state, $msg, $data );
		}
	}
	/**
	 * 负责解密data,还原客户端传来的参数
	 *
	 * @param unknown_type $data        	
	 */
	public function _value($data) {
		if (isset ( $data )) {
			return json_decode ( trim ( $this->_decrypt ( $data, $this->pwd, $this->iv ) ) );
		} else {
			return $data;
		}
	}
	// 服务器信息开始
	/**
	 * 取指定服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function GetServer($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$Server = $this->RequireClass ( 'ServerInfo', $this->conn, $this->config );
				
				if (isset ( $Server )) {
					$re = $Server->Get ( $this->_addslashes ( $data->ServerID ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 测试文件服务器
	 *
	 * @param unknown_type $d        	
	 */
	public function CheckFileServer($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				require_once dirname ( __FILE__ ) . '/mongodb.class.php';
				
				$sHost = $data->sHost;
				$sUser = $data->sUser;
				$sPwd = $data->sPwd;
				$sDB = $data->sDB;
				try {
					$link = new mongo ( 'mongodb://' . $sHost, array (
							'connect' => true 
					) );
					if (isset ( $link )) {
						$d = $link->connected;
						$link->close ();
					} else {
						$d = false;
					}
				} catch ( Exception $e ) {
					$d = false;
				}
				
				if ($d != true) {
					return $this->_return ( false, 'FileServer Connection failed!', $re );
				} else {
					return $this->_return ( true, 'OK', $re );
				}
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 文件服务器列表
	 *
	 * @param unknown_type $d        	
	 */
	public function ServerList($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$Server = $this->RequireClass ( 'ServerInfo', $this->conn, $this->config );
				
				if (isset ( $Server )) {
					$re = $Server->GetList ( $this->_addslashes ( $data->condition ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 分页返回服务器列表
	 *
	 * @param unknown_type $d        	
	 */
	public function GetServerListForPage($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$Server = $this->RequireClass ( 'ServerInfo', $this->conn, $this->config );
				
				if (isset ( $Server )) {
					$re = $Server->GetListForPage ( $this->_addslashes ( $data->condition ), $this->_addslashes ( $data->order ), $this->_addslashes ( $data->pagesize ), $this->_addslashes ( $data->page ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 增加服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function InsertServer($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$Server = $this->RequireClass ( 'ServerInfo', $this->conn, $this->config );
				
				if (isset ( $Server )) {
					$re = $Server->Insert ( $this->_addslashes ( $data->sHost ), $this->_addslashes ( $data->sUser ), $this->_addslashes ( $data->sPwd ), $this->_addslashes ( $data->sDB ), $this->_addslashes ( $data->sAppendTime ), $this->_addslashes ( $data->sState ), $this->_addslashes ( $data->sReadOnly ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 修改服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function UpdateServer($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$Server = $this->RequireClass ( 'ServerInfo', $this->conn, $this->config );
				
				if (isset ( $Server )) {
					$re = $Server->Update ( $this->_addslashes ( $data->ServerID ), $this->_addslashes ( $data->sHost ), $this->_addslashes ( $data->sUser ), $this->_addslashes ( $data->sPwd ), $this->_addslashes ( $data->sDB ), $this->_addslashes ( $data->sAppendTime ), $this->_addslashes ( $data->sState ), $this->_addslashes ( $data->sReadOnly ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 删除服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function DeleteServer($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$Server = $this->RequireClass ( 'ServerInfo', $this->conn, $this->config );
				
				if (isset ( $Server )) {
					$re = $Server->Delete ( $this->_addslashes ( $data->condition ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	// 服务器信息结束
	
	// 文件列表开始
	/**
	 * 取指定服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function GetFile($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$File = $this->RequireClass ( 'FileInfo', $this->conn, $this->config );
				
				if (isset ( $File )) {
					$re = $File->Get ( $this->_addslashes ( $data->FileID ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 文件服务器列表
	 *
	 * @param unknown_type $d        	
	 */
	public function FileList($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$File = $this->RequireClass ( 'FileInfo', $this->conn, $this->config );
				
				if (isset ( $File )) {
					$re = $File->GetList ( $this->_addslashes ( $data->condition ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 分页返回服务器列表
	 *
	 * @param unknown_type $d        	
	 */
	public function GetFileListForPage($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$File = $this->RequireClass ( 'FileInfo', $this->conn, $this->config );
				
				if (isset ( $File )) {
					$re = $File->GetListForPage ( $this->_addslashes ( $data->condition ), $this->_addslashes ( $data->order ), $this->_addslashes ( $data->pagesize ), $this->_addslashes ( $data->page ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 增加服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function InsertFile($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$File = $this->RequireClass ( 'FileInfo', $this->conn, $this->config );
				
				if (isset ( $File )) {
					$re = $File->Insert ( $this->_addslashes ( $data->ServerID ), $this->_addslashes ( $data->fIndexCode ), $this->_addslashes ( $data->fName ), $this->_addslashes ( $data->fOldName ), $this->_addslashes ( $data->fType ), $this->_addslashes ( $data->fInfo ), $this->_addslashes ( $data->fAppendTime ), $this->_addslashes ( $data->fState ), $this->_addslashes ( $data->fReadCount ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 修改服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function UpdateFile($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$File = $this->RequireClass ( 'FileInfo', $this->conn, $this->config );
				
				if (isset ( $File )) {
					$re = $File->Update ( $this->_addslashes ( $data->FileID ), $this->_addslashes ( $data->ServerID ), $this->_addslashes ( $data->fIndexCode ), $this->_addslashes ( $data->fName ), $this->_addslashes ( $data->fOldName ), $this->_addslashes ( $data->fType ), $this->_addslashes ( $data->fInfo ), $this->_addslashes ( $data->fState ), $this->_addslashes ( $data->fReadCount ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	/**
	 * 删除服务器信息
	 *
	 * @param unknown_type $d        	
	 */
	public function DeleteFile($d) {
		if ($this->authorized) {
			$re = null;
			if (isset ( $d )) {
				
				$data = $this->_value ( json_decode ( $d->data )->data );
				
				$File = $this->RequireClass ( 'FileInfo', $this->conn, $this->config );
				
				if (isset ( $File )) {
					$re = $File->Delete ( $this->_addslashes ( $data->condition ) );
				}
				
				return $this->_return ( true, 'OK', $re );
			} else {
				
				return $this->_return ( false, 'Data Error', $re );
			}
		
		} else {
			return $this->Unauthorized_User;
		}
	}
	
	// 文件列表结束

}

?>