<?php

/**
 * 文件存储读取
 * @author Cxty
 *
 */
ini_set('display_errors', true);
error_reporting(E_ALL);
error_reporting ( 0 );

class index {
	
	public $ReDataFormat = 'json'; // 默认以json格式返回
	public $conn = null;
	public $mongo = null;
	public $fun = null;
	public $debug = false;
	private $_Cache;
	private $_config;
	
	public function __construct() {
		
		require_once dirname ( __FILE__ ) . '/conf/config.php';
		require_once dirname ( __FILE__ ) . '/include/DBOModel.class.php';
		require_once dirname ( __FILE__ ) . '/include/mongodb.class.php';
		require_once dirname ( __FILE__ ) . '/include/thumb.class.php';
		require_once dirname ( __FILE__ ) . '/include/Fun.class.php';
		require_once dirname ( __FILE__ ) . '/include/DES.class.php';
		
		require_once dirname ( __FILE__ ) . '/include/DBOCache.class.php';
		require_once dirname ( __FILE__ ) . '/include/cache/DBOMemcache.class.php';
		
		$this->fun = new Fun ();
		$conn_re = array (
				'state' => false 
		);
		
		$this->_config = $config;
		
		if (! empty ( $_GET ['act'] )) {
			
			if (! is_object ( $this->conn )) {
				
				$this->conn = new DBOModel ( $config );
				
				if (empty ( $this->conn )) {
					$conn_re = array (
							'state' => false 
					);
				} else {
					$conn_re = array (
							'state' => true 
					);
				}
			} else {
				$conn_re = array (
						'state' => true
				);
			}
			
			if ($conn_re ['state']) {
				switch ($_GET ['act']) {
					// 上传
					case 'up' :
						$this->Up ();
						break;
					// 读取
					case 'get' :
						$this->Get ();
						break;
				}
				
				if (! is_object ( $this->conn )) {
				    $this->conn->__destruct ();
				}
				if (! is_object ( $this->mongo )) {
				    $this->mongo->__destruct ();
				}
				
			} else {
				$this->ReCall(false,'DB Error!');
				/*
				echo json_encode ( array (
						'state' => false,
						'error' => 'DB Error!' 
				) );
				*/
			}
		} else {
			$this->ReCall(false,'No Request!');
			/*
			echo json_encode ( array (
					'state' => false,
					'error' => 'No Request!' 
			) );
			*/
		}
	}
	/**
	 * 销毁对象
	 */
	/*
	public function __destruct() {
		if (! is_object ( $this->conn )) {
			$this->conn->__destruct ();
		}
		if (! is_object ( $this->mongo )) {
			$this->mongo->__destruct ();
		}
	}
	*/
	/**
	 * 上传文件
	 * 调用方式
	 * http://file.dbowner.com:89/index.php?act=get&filecode=7e1765a0c12d8da180a42a1f5cfdcf6f&w=100
	 */
	public function Up() {
		try {
			
			if (! empty ( $_FILES )) {
				
				if ($_FILES ["uploadfile"] ["error"] > 0) {
					$this->ReCall ( false, $_FILES ["uploadfile"] ["error"] );
				} else {
					
					$files = $_FILES ['uploadfile']; // 表单对象
					if ($files) {
						if ($files ['error'] == 0) {
							
							// $filename = base64_decode($_GET ['filename']);//$this->fun->unescape($_GET ['filename']);
							$filename = isset($_GET ['filename'])?$_GET ['filename']:$_POST['filename'];
							$filetype = isset($_GET ['filetype'])?$_GET ['filetype']:$_POST['filetype'];
							
							$filetype = $filetype?$filetype:mime_content_type($files ['tmp_name']);
							
							//$filetype = $files ['type'];
							$size = $files ['size'];
							
							$md5 = empty ( $_GET ['filemd5'] ) ? md5_file ( $files ['tmp_name'] ) : $_GET ['filemd5'];
							
							
							
							$condition = array ();
							$condition ['fIndexCode'] = $md5;
							// 是否有重复文件
							$data = $this->conn->table ( 'tbFileInfo', false )->field ( 'FileID' )->where ( $condition )->find ();
							
							if (empty ( $data )) {
								// 取一空闲文件服务器并连接
								$data = $this->conn->table ( 'tbServerInfo', false )->query ( "select ServerID,sHost,sUser,sPwd,sDB from tbServerInfo where sState=0 and sReadOnly=0 and ServerID>=(SELECT floor(RAND() * (SELECT MAX(ServerID) FROM `tbServerInfo`))) ORDER BY ServerID LIMIT 1" );
								
								if (! empty ( $data )) {
									
									$serverid = $data [0] ['ServerID'];
									$dbhost = $data [0] ['sHost'];
									$dbuser = $data [0] ['sUser'];
									$dbpw = $data [0] ['sPwd'];
									$dbname = $data [0] ['sDB'];
									
									$mongo = new mongo_db ();
									
									if ($mongo->connect ( $dbhost, $dbuser, $dbpw, $dbname ) === false) {
										$this->ReCall ( false, 'File Server Error!' );
									} else {
										
										$grid = $mongo->getGridFS ( 'files' );
										
										$exists = $grid->findOne ( array (
												'md5' => $md5,
												'length' => $size 
										) ); // 查找是否有重复文件
										
										if ($exists == null) {
											self::log ( 'add file' );
											try {
												//$grid->storeBytes( join ( "", file ( $files ['tmp_name'] ) ),array (
												//		'md5' => $md5 
												//));
												
												if ($grid->storeFile ( $files ['tmp_name'], array (
														'md5' => $md5 
												), array (
														'safe' => true 
												) )) {
													self::log ( 'add file ok!' );
												} else {
													self::log ( 'no add file!' );
												}
												
											} catch ( MongoCursorException $e ) {
												$this->ReCall ( false, $e->getMessage () );
											}
											unlink($files ['tmp_name']);//删除临时文件
										}
										
										// 存储文件索引信息
										$data = array ();
										$data ['ServerID'] = $serverid;
										$data ['fIndexCode'] = $md5;
										$data ['fName'] = $filename;
										$data ['fOldName'] = $filename;
										$data ['fType'] = $filetype;
										$data ['fInfo'] = ($filetype == 'image/jpeg') ? json_encode ( exif_read_data ( $files ['tmp_name'] ) ) : '';
										$data ['fAppendTime'] = time ();
										$data ['fState'] = 0;
										$data ['fReadCount'] = 0;
										
										if (! $this->conn->table ( 'tbFileInfo', false )->data ( $data )->insert ()) {
											$this->ReCall ( false, 'Write DB Error!' );
										} else {
											// 返回文件索引码
											$this->ReCall ( true, 'OK!', array (
													'filecode' => $md5,
													'filename' => $filename 
											) );
										}
									
									}
								
								} else {
									$this->ReCall ( false, 'File Server Busy!' );
								}
							
							} else {
								
								// 已存在直接返回文件索引码
								$this->ReCall ( true, 'OK!', array (
										'filecode' => $md5,
										'filename' => $filename 
								) );
							}
						} else {
							$this->ReCall ( false, 'Up File Error:' . $files ['error'] );
						}
					} else {
						$this->ReCall ( false, 'Nothing File!' );
					}
				}
			} else {
				$this->ReCall ( false, 'Nothing FileStream!' );
			}
		} catch ( Exception $e ) {
			$this->ReCall ( false, $e->getMessage () );
		}
	}
	
	/**
	 * 读取文件
	 */
	public function Get() {
		
		if (isset ( $_GET ['filecode'] )) {
			
			$this->_Cache = new DBOCache($this->_config,'Memcache');
			
			$w = isset ( $_GET ['w'] ) ? ( int ) $_GET ['w'] : 64;
			$h = isset ( $_GET ['h'] ) ? ( int ) $_GET ['h'] : 64;
			$m = isset ( $_GET ['m'] ) ? preg_match('/^[a-f0-9]{6}$/i',$_GET ['m'])?$_GET ['m']:false : false;//是否自动填充背景，输出设定的高宽图
			
			$filecode = $this->fun->_addslashes ( $_GET ['filecode'] );
			
			if (! empty ( $filecode )) {
				
				$data = $this->GetFileSetInCache($filecode);
				
				if (! empty ( $data )) {
					
					$ServerID = $data ['ServerID'];
					$fReadCount = ( int ) $data ['fReadCount'];
					$fType = strtolower($data ['fType']);
					//$fName = base64_decode ( rawurldecode ( $data ['fName'] ) );
					$fName = $data ['fName'];
					// 读取计数
					$data = array ();
					$data ['fReadCount'] = $fReadCount + 1;
					$this->conn->table ( 'tbFileInfo', false )->data ( $data )->where ( $condition )->update ();
					
					$data = $this->GetServerInfoInCache($ServerID);
					/*
					$condition = array ();
					$condition ['ServerID'] = $ServerID;
					$data = $this->conn->table ( 'tbServerInfo', false )->field ( 'sHost,sUser,sPwd,sDB' )->where ( $condition )->find ();
					*/
					if (! empty ( $data )) {
						
						$dbhost = $data ['sHost'];
						$dbuser = $data ['sUser'];
						$dbpw = $data ['sPwd'];
						$dbname = $data ['sDB'];
						
						$mongo = new mongo_db ();
						
						if ($mongo->connect ( $dbhost, $dbuser, $dbpw, $dbname ) === false) {
							
							$this->ReCall ( false, 'File Server Error!' );
						} else {
							
							$grid = $mongo->getGridFS ( 'files' );
							
							$files = $grid->findOne ( array (
									'md5' => $filecode 
							) );
							
							if (! empty ( $files )) {
								$files_bytes = $files->getBytes ();
								
								ob_clean ();
								
								if(isset ( $_GET ['name'] )){
									$fName = $_GET ['name'];
									
									if ($fType == 'image/jpeg'){
									    $fName = $fName.'.jpg';
									}
									if($fType == 'image/png'){
									    $fName = $fName.'.png';
									}
									if($fType == 'image/gif'){
									    $fName = $fName.'.gif';
									}
									
									header ( "content-type:$fType" );
									
									
									$encoded_filename = urlencode($fName);
									$encoded_filename = str_replace("+", "%20", $encoded_filename);
									$ua = $_SERVER["HTTP_USER_AGENT"];
									/*
									$fNameType = end(explode('.', $fName));
									
									$fName = $encoded_filename.'.'.$fNameType;
									*/
									if (preg_match("/MSIE/", $ua)) {
										header('Content-Disposition: "attachment"; filename="' . $encoded_filename . '"');
									//} else if (preg_match("/Firefox/", $ua)) {
									//	header('Content-Disposition: "attachment"; filename*="utf8\'\'' . $fName . '"');
									} 
									else {
										header('Content-Disposition: "attachment"; filename="' . $fName . '"');
									}
									
									
									//header ( "Content-Disposition: attachment; filename=\"".$fName."\"" );
									echo $files_bytes;
								}else{
									if ($fType == 'image/jpeg' || $fType == 'image/png' || $fType == 'image/gif') {
								    //if($m){
								    //    Thumb::fixed($files_bytes, $w, $h, $fType);
								    //}else{
								       Thumb::maxWidth ( $files_bytes, $w, $fType );
								    //}
								} else {
									header ( "content-type:$fType" );
									
									
									$encoded_filename = urlencode($fName);
									$encoded_filename = str_replace("+", "%20", $encoded_filename);
									$ua = $_SERVER["HTTP_USER_AGENT"];
									/*
									$fNameType = end(explode('.', $fName));
									
									$fName = $encoded_filename.'.'.$fNameType;
									*/
									if (preg_match("/MSIE/", $ua)) {
										header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
									} else if (preg_match("/Firefox/", $ua)) {
										header('Content-Disposition: attachment; filename*="utf8\'\'' . $fName . '"');
									} else {
										header('Content-Disposition: attachment; filename="' . $fName . '"');
									}
									
									
									//header ( "Content-Disposition: attachment; filename=\"".$fName."\"" );
									echo $files_bytes;
								}
									
								}
								
								
							
							} else {
								$this->ReCall ( false, 'File Server, NO File!' );
							}
						}
					} else {
						$this->ReCall ( false, 'NO File Server !' );
					}
				
				} else {
					$this->ReCall ( false, 'NO File!' );
				}
			
			} else {
				$this->ReCall ( false, 'NO FileCode!' );
			}
		} else {
			$this->ReCall ( false, 'NO FileCode!' );
		}
	}
	
	//缓存MySQL查询,FileInfo
	public function GetFileSetInCache($filecode){
		$_list = Array();
		$_list = $this->_Cache->get('tbFileInfo'.$filecode);
		if($_list){
		
		}else{
			$condition = array ();
			$condition ['fIndexCode'] = $filecode;
			// 是否有重复文件
			$_list = $this->conn->table ( 'tbFileInfo', false )->field ( 'ServerID,fReadCount,fType,fName' )->where ( $condition )->find ();
			
			$this->_Cache->set('tbFileInfo'.$filecode,$_list);
		}
		return $_list;
	}
	
	//缓存MySQL查询,ServerInfo
	public function GetServerInfoInCache($ServerID){
		$_list = Array();
		$_list = $this->_Cache->get('tbServerInfo'.$ServerID);
		if($_list){
		
		}else{
			$condition = array ();
			$condition ['ServerID'] = $ServerID;
			$_list = $this->conn->table ( 'tbServerInfo', false )->field ( 'sHost,sUser,sPwd,sDB' )->where ( $condition )->find ();
				
			$this->_Cache->set('tbServerInfo'.$ServerID,$_list);
		}
		return $_list;
	}
	
	/**
	 * 返回值
	 *
	 * @param unknown_type $state        	
	 * @param unknown_type $msg        	
	 * @param unknown_type $data        	
	 */
	public function ReCall($state, $msg, $data = array()) {
		echo json_encode ( array (
				'state' => $state,
				'msg' => $msg,
				'data' => $data 
		) );
		exit;
	}
	// 输出记录
	public function log($message) {
		if ($this->debug) {
			$destination = dirname ( __FILE__ ) . '/log.log';
			$time = date ( 'Y-m-d H:i:s' );
			@error_log ( "{$time}| {$_SERVER['PHP_SELF']} |{$message}\r\n", 3, $destination );
		}
	}
}

$index = new index ();

unset($index);
?>
