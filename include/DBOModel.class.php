<?php
//模型类，加载了外部的数据库驱动类和缓存类
class DBOModel
{
    public $db = NULL; // 当前数据库操作对象
	public $cache=NULL;//缓存对象
	public $config =array();
	public $sql = '';//sql语句，主要用于输出构造成的sql语句
	public  $pre = '';//表前缀，主要用于在其他地方获取表前缀
    private $data =array();// 数据信息  
    private $options=array(); // 查询表达式参数	
	
    public function __construct($config=array())
    {

		  $this->config=$config;//参数配置	
		   		   
		  $this->options['field']='*';//默认查询字段
		  $this->pre= $this->config['DB_PREFIX'];//数据表前缀
    }
	public function __destruct(){
		if(is_object($this->db)){
			$this->db->__destruct();
		}
	}
	//连接数据库
	public function connect()
	{
		 //$this->db不是对象，则连接数据库
		 if(!is_object($this->db))
		 {
			  $db_type= $this->config['DB_TYPE'];
			  if(!file_exists(dirname(__FILE__).'/'.$db_type.'.class.php'))
			  {
					$this->error($db_type.'数据库类型没有驱动');
			  }
			  require_once(dirname(__FILE__).'/'.$db_type.'.class.php');//加载数据库类
			  
			  $this->db = new $db_type();//连接数据库
			  $this->db->connect($this->config['DB_HOST'].":".$this->config['DB_PORT'], $this->config['DB_USER'], $this->config['DB_PWD'], $this->config['DB_NAME'] , $this->config['DB_CHARSET'] , $this->config['DB_PCONNECT'] , $this->config['DB_PREFIX']) ;
			 
		 }
	}
	
	//设置表，$$ignore_prefix为true的时候，不加上默认的表前缀
	public function table($table,$ignore_prefix=false)
	{
		if($ignore_prefix)
		{
			 $this->options['table']='`'.$table.'`';
		}
		else
		{
			$this->options['table']='`'.$this->config['DB_PREFIX'].$table.'`';
		}
		return $this;
	}
	
	 //回调方法，连贯操作的实现
    public function __call($method,$args) 
	{
		$method=strtolower($method);
        if(in_array($method,array('field','data','where','group','having','order','limit','cache')))
		{
            $this->options[$method] =$args[0];//接收数据
			return $this;//返回对象，连贯查询
        }
		else
		{
			$this->error($method.'方法在DBOModel.class.php类中没有定义');
		}
    }
	//执行原生sql语句，如果sql是查询语句，返回二维数组
    public function query($sql)
    {
        if(empty($sql)) 
		{
		   return false;
        }
		$this->sql=$sql;
		//判断当前的sql是否是查询语句
		if(strpos(trim(strtolower($sql)),'select')===0)
		{
				$data=array();
				//读取缓存
				$data=$this->_readCache('query');
				if(!empty($data))
				{
					return $data;
				}
				//没有缓存，则查询数据库
				$this->connect();
				$query=$this->db->query($this->sql);		
				while($row=$this->db->fetch_array($query))
				{
					$data[]=$row;
				}
				$this->_writeCache($data,'query');//写入缓存
				return $data;
					
		}		
		else 
		{
			$this->connect();
			return $query=$this->db->query($this->sql);//不是查询条件，执行之后，直接返回
		}

    }
	
	//统计行数
	public function count()
	{
		$table=$this->options['table'];//当前表
		$field='count(*)';//查询的字段
		$where=$this->_parseCondition();//条件

		$this->sql="SELECT $field FROM $table $where";
		$data="";
		//读取缓存
		$data=$this->_readCache('count');
		if(!empty($data))
		{
			return $data;
		}	
		
		$this->connect();			
		$query=$this->db->query($this->sql);
        $data=$this->db->fetch_array($query);
		$this->_writeCache($data['count(*)'],'count');//写入缓存
		return $data['count(*)'];

	}
	//只查询一条信息，返回一维数组	
    public function find()
	{
		$table=$this->options['table'];//当前表
		$field=$this->options['field'];//查询的字段
		$this->options['limit']=1;//限制只查询一条数据
		$where=$this->_parseCondition();//条件
		$this->options['field']='*';//设置下一次查询时，字段的默认值
		
		$this->sql="SELECT $field FROM $table $where";
		$data="";
		//读取缓存
		$data=$this->_readCache('find');
		if(!empty($data))
		{
			return $data;
		}
		
		$this->connect();
		$query=$this->db->query($this->sql);
        $data=$this->db->fetch_array($query);
		$this->_writeCache($data,'find');//写入缓存
		return $data;
     }
	 
	//查询多条信息，返回数组
     public function select()
	{
		$table=$this->options['table'];//当前表
		$field=$this->options['field'];//查询的字段
		$where=$this->_parseCondition();//条件
		$this->options['field']='*';//设置下一次查询时，字段的默认值
		
		$this->sql="SELECT $field FROM $table $where";

		$data=array();
		//读取缓存
		$data=$this->_readCache('select');
		if(!empty($data))
		{	
			return $data;
		}
		//没有缓存，则查询数据库
		$this->connect();
		$query=$this->db->query($this->sql);		
		while($row=$this->db->fetch_array($query))
		{
			$data[]=$row;
		}
		$this->_writeCache($data,'select');//写入缓存
		return $data;
     }
	 
	 //插入数据
    public function insert() 
	{
		$this->connect();
		$table=$this->options['table'];//当前表
		$data=$this->_parseData('add');//要插入的数据
		
        $this->sql="INSERT INTO $table $data" ;
        
        $query = $this->db->query($this->sql);
		if($this->db->affected_rows())
		{
			 $id=$this->db->insert_id();
			 return empty($id)?$this->db->affected_rows():$id;
		}
        return false;
    }
	//替换数据
	 public function replace() 
	{
		$this->connect();
		$table=$this->options['table'];//当前表
		$data=$this->_parseData('add');//要插入的数据
		
        $this->sql="REPLACE INTO $table $data" ;
        $query = $this->db->query($this->sql);
		if($this->db->affected_rows())
		{
			return  $this->db->insert_id();
		}
        return false;
    }
	//修改更新
    public function update()
	{
		$this->connect();
		$table=$this->options['table'];//当前表
		$data=$this->_parseData('save');//要更新的数据
		$where=$this->_parseCondition();//更新条件
		//修改条件为空时，则返回false，避免不小心将整个表数据修改了
		if(empty($where))
		{
			return false;	
		}
        $this->sql="UPDATE $table SET $data $where" ;
	    $query = $this->db->query($this->sql);
		return $this->db->affected_rows();

    }
	//删除
    public function delete()
	{
		$this->connect();
		$table=$this->options['table'];//当前表
		$where=$this->_parseCondition();//条件
		//删除条件为空时，则返回false，避免数据不小心被全部删除
		if(empty($where))
		{
			return false;	
		}
		$this->sql="DELETE FROM $table $where";
        $query = $this->db->query($this->sql);
		return $this->db->affected_rows();
    }

	//返回sql语句
    public function getSql()
	{
        return $this->sql;
    }

	//删除数据库缓存
    public function clear()
    {
		if($this->config['DB_CACHE_ON'])
			return $this->cache->clear();
		return false;
    }
	//解析数据,添加数据时$type=add,更新数据时$type=save
   private function _parseData($type) 
  {
		if((!isset($this->options['data']))||(empty($this->options['data'])))
		{
			unset($this->options['data']);	//清空$this->options['data']数据
			return false;
		}
		//如果数据是字符串，直接返回
		if(is_string($this->options['data']))
		{
			$data=$this->options['data'];
			unset($this->options['data']);	//清空$this->options['data']数据
			return $data;
		}
		switch($type)
		{
			case 'add':
					$data=array();
					$data['key']="";
					$data['value']="";
					foreach($this->options['data'] as $key=>$value)
					{
							$data['key'].="`$key`,";
							$data['value'].="'$value',";
					}
					$data['key']=substr($data['key'], 0,-1);//去除后面的逗号
					$data['value']=substr($data['value'], 0,-1);//去除后面的逗号
					unset($this->options['data']);	//清空$this->options['data']数据
					return " (".$data['key'].") VALUES (".$data['value'].") ";
					break;
			case 'save':
					$data="";
					foreach($this->options['data'] as $key=>$value)
					{
							$data.="`$key`='$value',";
					}
					$data=substr($data, 0,-1);	//去除后面的逗号
					unset($this->options['data']);	//清空$this->options['data']数据
					return $data;
				break;
		default:
				unset($this->options['data']);	//清空$this->options['data']数据
				return false;
		}		
    }
	
	//解析sql查询条件
   private function _parseCondition() 
	{
		$condition="";
		//解析where()方法
		if(!empty($this->options['where']))
		{
			$condition=" WHERE ";
			if(is_string($this->options['where']))
			{
				$condition.=$this->options['where'];
			}
			else if(is_array($this->options['where']))
			{
					foreach($this->options['where'] as $key=>$value)
					{
						 $condition.=" `$key`='$value' AND ";
					}

					$condition=substr($condition, 0,-4);	
			}
			else
			{
				$condition="";
			}
			unset($this->options['where']);//清空$this->options['where']数据
		}
		
		if(!empty($this->options['group'])&&is_string($this->options['group']))
		{
			$condition.=" GROUP BY ".$this->options['group'];
			unset($this->options['group']);
		}
				
		if(!empty($this->options['having'])&&is_string($this->options['having']))
		{
			$condition.=" HAVING ".$this->options['having'];
			unset($this->options['having']);
		}
				
		if(!empty($this->options['order'])&&is_string($this->options['order']))
		{
			$condition.=" ORDER BY ".$this->options['order'];
			unset($this->options['order']);
		}
		if(!empty($this->options['limit'])&&(is_string($this->options['limit'])||is_numeric($this->options['limit'])))
		{
			$condition.=" LIMIT ".$this->options['limit'];
			unset($this->options['limit']);
		}
		
		if(empty($condition))
			return "";
        return $condition;
    }
	 //初始化缓存类，如果开启缓存，则加载缓存类并实例化
	public function initCache()
	{		
		if(is_object($this->cache))
		{
			return true;
		}
		else if($this->config['DB_CACHE_ON'])
		{
			require_once(dirname(__FILE__).'/DBOCache.class.php');
			$config['DATA_CACHE_PATH']=$this->config['DB_CACHE_PATH'];
			$config['DATA_CACHE_TIME']=$this->config['DB_CACHE_TIME'];
			$config['DATA_CACHE_CHECK']=$this->config['DB_CACHE_CHECK'];		
			$config['DATA_CACHE_FILE']=$this->config['DB_CACHE_FILE'];
			$config['DATA_CACHE_SIZE']=$this->config['DB_CACHE_SIZE'];
			$config['DATA_CACHE_FLOCK']=$this->config['DB_CACHE_FLOCK'];
			$this->cache=new DBOCache($config);
			return true;
		}
		else
		{
			return false;
		}
	}
	//读取缓存
	public  function _readCache($cache_prefix)
	{
		$expire=isset($this->options['cache'])?$this->options['cache']:$this->config['DB_CACHE_TIME'];
			//缓存时间为0，不读取缓存
		if($expire==0)
			return false;
		
		$data="";	
		//cp数据库缓存获取自定义扩展	
		if($this->config['DB_CACHE_ON']&&function_exists('db_cache_get_ext'))		
		{
			$data=db_cache_get_ext($cache_prefix.$this->sql);
		}
		
		if($this->initCache())
		{
			 $data=$this->cache->get($cache_prefix.$this->sql);
		}
		if(!empty($data))
		{
			unset($this->options['cache']);
			return $data;
		}
		else
		{
			return "";
		}

	}
	//写入缓存
	private function _writeCache($data,$cache_prefix)
	{	
		$expire=isset($this->options['cache'])?$this->options['cache']:$this->config['DB_CACHE_TIME'];
		unset($this->options['cache']);
			//缓存时间为0，不读取缓存
		if($expire==0)
			return false;
			
		//数据库缓存设置自定义扩展	
		if($this->config['DB_CACHE_ON']&&function_exists('db_cache_set_ext'))		
		{
			return $data=db_cache_set_ext($cache_prefix.$this->sql,$data,$expire);
		}
		
		if($this->initCache())
		{				
			return $this->cache->set($cache_prefix.$this->sql,$data,$expire);	
		}
		return false;	
	}
  
}
?>