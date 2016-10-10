<?php
/**
 * 服务器信息操作类
 * @author Cxty
 *
 */
class ServerInfo {
	public $model; //数据库模型对象
	public $config; //全局配置
	
	public function __construct($_model,$_config) {
		if(!isset($this->model)){
			$this->model=$_model;
		}
		if(!isset($this->config)){
			$this->config=$_config;
		}
	}
	/**
	 * 验证是否存在
	 * @param unknown_type $sHost
	 */
	public function Exist($sHost)
	{
		try
		{
			$condition = array();
			$condition['sHost'] = $sHost;
			return $this->model
			->table('tbServerInfo',false)
			->field('ServerID,
						sHost,
						sUser,
						sPwd,
						sDB,
						sAppendTime,
						sState,
						sReadOnly')
					->where($condition)
					->find();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
	/**
	 * 取单个记录
	 * @param int $ServerID
	 */
	public function Get($ServerID)
	{
		try
		{
			$condition = array();
			$condition['ServerID'] = $ServerID;
			return $this->model
			->table('tbServerInfo',false)
			->field('ServerID,
						sHost,
						sUser,
						sPwd,
						sDB,
						sAppendTime,
						sState,
						sReadOnly')
					->where($condition)
					->find();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
	
	/**
	 * 取多个记录
	 * @param string/array $condition
	 */
	public function GetList($condition)
	{
		try
		{
			return $this->model
			->table('tbServerInfo',false)
			->field('ServerID,
						sHost,
						sUser,
						sPwd,
						sDB,
						sAppendTime,
						sState,
						sReadOnly')
					->where($condition)
					->select();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
	
	/**
	 * 分页查询
	 * @param string/array $condition
	 * @param string $order
	 * @param int $pagesize
	 * @param int $page
	 */
	public function GetListForPage($condition,$order,$pagesize,$page)
	{
		try
		{
			$limit_start=($page-1)*$pagesize;
			$limit=$limit_start.','.$pagesize;
	
			//获取行数
			$count=$this->model->table('tbServerInfo',false)->field('ServerID')->where($condition)->count();
			$list=$this->model->table('tbServerInfo',false)
			->field('ServerID,
						sHost,
						sUser,
						sPwd,
						sDB,
						sAppendTime,
						sState,
						sReadOnly')->where($condition)->order($order)->limit($limit)->select();
	
			return array('count'=>$count,'list'=>$list);
		}
		catch(Exception  $e)
		{
			return array('count'=>0,'list'=>null);
		}
	}
	
	/**
	 * 添加一条记录
	 */
	public function Insert($sHost,$sUser,$sPwd,$sDB,$sAppendTime,$sState,$sReadOnly	)
	{
		try
		{
			$data = array();
			$data['sHost']=$sHost;
			$data['sUser']=$sUser;
			$data['sPwd']=$sPwd;
			$data['sDB']=$sDB;
			$data['sAppendTime']=time();
			$data['sState']=$sState;
			$data['sReadOnly']=$sReadOnly;
	
			return $this->model->table('tbServerInfo',false)->data($data)->insert();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
	
	/**
	 * 更新记录
	 */
	public function Update($ServerID,$sHost,$sUser,$sPwd,$sDB,$sAppendTime,$sState,$sReadOnly)
	{
		try
		{
			$condition = array();
			$data = array();
			$condition['ServerID'] = $ServerID;
	
			$data['sHost']=$sHost;
			$data['sUser']=$sUser;
			$data['sPwd']=$sPwd;
			$data['sDB']=$sDB;
			//$data['sAppendTime']=$sAppendTime;
			$data['sState']=$sState;
			$data['sReadOnly']=$sReadOnly;
	
			return $this->model->table('tbServerInfo',false)->data($data)->where($condition)->update();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
	
	/**
	 * 删除记录
	 * @param string/array $condition
	 */
	public function Delete($condition)
	{
		try
		{
			return $this->model->table('tbServerInfo',false)->where($condition)->delete();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
}

?>