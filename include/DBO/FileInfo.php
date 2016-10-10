<?php
/**
 * 文件信息操作类
 * @author Cxty
 *
 */
class FileInfo {
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
			->table('tbFileInfo',false)
			->field('FileID,
						ServerID,
						fIndexCode,
						fName,
						fOldName,
						fType,
						fInfo,
						fAppendTime,
						fState,
						fReadCount')
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
	 * @param int $FileID
	 */
	public function Get($FileID)
	{
		try
		{
			$condition = array();
			$condition['FileID'] = $FileID;
			return $this->model
			->table('tbFileInfo',false)
			->field('FileID,
						ServerID,
						fIndexCode,
						fName,
						fOldName,
						fType,
						fInfo,
						fAppendTime,
						fState,
						fReadCount')
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
			->table('tbFileInfo',false)
			->field('FileID,
						ServerID,
						fIndexCode,
						fName,
						fOldName,
						fType,
						fInfo,
						fAppendTime,
						fState,
						fReadCount')
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
			$count=$this->model->table('tbFileInfo',false)->field('FileID')->where($condition)->count();
			$list=$this->model->table('tbFileInfo',false)
			->field('FileID,
						ServerID,
						fIndexCode,
						fName,
						fOldName,
						fType,
						fInfo,
						fAppendTime,
						fState,
						fReadCount')->where($condition)->order($order)->limit($limit)->select();
	
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
	public function Insert($ServerID,$fIndexCode,$fName,$fOldName,$fType,$fInfo,$fAppendTime,$fState,$fReadCount	)
	{
		try
		{
			$data = array();
			$data['ServerID']=$ServerID;
			$data['fIndexCode']=$fIndexCode;
			$data['fName']=$fName;
			$data['fOldName']=$fOldName;
			$data['fType']=$fType;
			$data['fInfo']=$fInfo;
			$data['fAppendTime']=$fAppendTime;
			$data['fState']=$fState;
			$data['fReadCount']=$fReadCount;
	
			return $this->model->table('tbFileInfo',false)->data($data)->insert();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
	
	/**
	 * 更新记录
	 */
	public function Update($FileID,$ServerID,$fIndexCode,$fName,$fOldName,$fType,$fInfo,$fState,$fReadCount	)
	{
		try
		{
			$condition = array();
			$data = array();
			$condition['FileID'] = $FileID;
	
			$data['ServerID']=$ServerID;
			$data['fIndexCode']=$fIndexCode;
			$data['fName']=$fName;
			$data['fOldName']=$fOldName;
			$data['fType']=$fType;
			$data['fInfo']=$fInfo;
			
			$data['fState']=$fState;
			$data['fReadCount']=$fReadCount;
	
			return $this->model->table('tbFileInfo',false)->data($data)->where($condition)->update();
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
			return $this->model->table('tbFileInfo',false)->where($condition)->delete();
		}
		catch(Exception  $e)
		{
			return null;
		}
	}
}

?>