<?php
//网站全局配置
$config['ver']									='1.0';
$config['public_key']							='1qaz2wsx3edc';//公共加密密钥
$config['public_iv']							='1q2w3e4r';//公共加密向量
//网站全局配置结束

//数据库配置
$config['DB_TYPE']					='mysql';							//数据库类型
$config['DB_HOST']					='192.168.0.198';					//数据库主机
$config['DB_USER']					='dbowner_file_db';							//数据库用户名
$config['DB_PWD']					='40ed60aa8';							//数据库密码
$config['DB_PORT']					=3306;							//数据库端口，mysql默认是3306
$config['DB_NAME']					='dbowner_file_db';				//数据库名
$config['DB_CHARSET']				='utf8';						//数据库编码
$config['DB_PREFIX']				='';						//数据库前缀
$config['DB_PCONNECT']				=false;						//true表示使用永久连接，false表示不适用永久连接，一般不使用永久连接

$config['DB_CACHE_ON']				=false;						//是否开启数据库缓存，true开启，false不开启
$config['DB_CACHE_PATH']			='./cache/db_cache/';		//数据库查询内容缓存目录，地址相对于入口文件
$config['DB_CACHE_TIME']			=600;						//缓存时间,0不缓存，-1永久缓存
$config['DB_CACHE_CHECK']			=true;						//是否对缓存进行校验
$config['DB_CACHE_FILE']			='cachedata';				//缓存的数据文件名
$config['DB_CACHE_SIZE']			='15M';						//预设的缓存大小，最小为10M，最大为1G
$config['DB_CACHE_FLOCK']			=true;						//是否存在文件锁，设置为false，将模拟文件锁

//MemCache配置
$config['MEM_SERVER']							= array( array('192.168.0.189', 11211),  array('192.168.0.189', 11212) );
$config['MEM_GROUP']							= 'filedb';
$config['SAE_MEM_GROUP']						= 'filedb';

//SOAP服务端配置
$config['SOAP_SERVER_USER']='soap_server';				//SOAP客户端登录用户
$config['SOAP_SERVER_PWD']='soap_pwd';					//SOAP客户端登录密码
$config['SOAP_SERVER_IV']='12345678';							//SOAP数据加密向量
$config['SOAP_SERVER_CLIENTIP']=array('127.0.0.1','192.168.0.2','192.168.0.100','192.168.0.111','192.168.0.201','192.168.0.112','192.168.0.253');			//允许访问的客户端IP


?>