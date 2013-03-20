<?php
/**
 * MC:message center
 * */
class DxDataSync{
	const SYNC_INSERT	= 1;	//新增的数据
	const SYNC_UPDATE	= 2;	//更新的数据
	const SYNC_DELETE	= 3;	//删除的数据
	const SYNC_NORMAL	= 4;	//已同步的数据
	
	const SYNC_IGNORE	= 5;	//忽略的数据，不进行同步
	const SYNC_LOCK		= 6;	//锁定数据，不允许被同步覆盖掉
	const SYNC_REWRITE	= 7;	//数据被覆盖了
	const SYNC_ERROR	= 8;	//数据异常，一个未知的同步状况
	const SYNC_REAL_DEL	= 9;	//数据完全删除
	
	const SYNC_TYPE_DATA	= 1;
	const SYNC_TYPE_RESULT	= 2;
	
	private $myNodeName		= "";	//本节点名称
	private $syncConfig		= array();	//同步配置信息
	private $syncSetInfo	= "";		//本节点同步模块设置
	private	$modelToStanderName	= array();	//本地model名称与标准model名称的对应关系，缓存
	public $messageHost		= "127.0.0.1";
	public $messagePort		= 1218;
	public $messageAuth		= "dxinfo";
	
	public function __construct($config,$set,$myNodeName){
		$this->myNodeName	= $myNodeName;
		$this->syncConfig	= $config;
		$this->syncSetInfo	= $set;
		$this->modelToStanderName	= array();
		foreach($config as $key=>$val){
			$myNode	= $val[$this->myNodeName];
			$this->modelToStanderName[$myNode["modelName"]]	= $key;
		}
	}
	
	protected function getMessageEngine(){
		$sqs	= new httpsqs($this->messageHost,$this->messagePort,$this->messageAuth);
		return $sqs;
	}
	
	public function putDataToMC(){
		foreach($this->syncSetInfo as $key=>$val){
			$this->putModelInfoToMC($key);
		}
	}
		
	/**
	 * @return 
	 * @para	$dataNode		发送数据到那个节点
	 * @para	$modelName		要发送哪个model的数据
	 * @para	$dataId			要发送的数据 a.数组	 b.字段id值，为空则发送所有model的数据
	 * **/
	protected function putModelInfoToMC($standerModelName,$data=0,$data_type=self::SYNC_TYPE_DATA){
		$modelName	= $this->getMyModelNameFromStanderModel($standerModelName);
		$m	= D($modelName);
		$m->skipDataPowerCheck	= true;
		if(empty($m)) return false;

		$where		= $this->getSyncWhere($standerModelName);
		$myFields	= $this->getModelFields($standerModelName,$this->myNodeName);
		if(is_array($data)){
			//同步传递的数据
		}else if($data==0){
			//同步Model的所有未同步数据
			$where["sync_status"]	= array("in",self::SYNC_INSERT.",".self::SYNC_UPDATE.",".self::SYNC_DELETE);
			//数据量过大，导致每次传送的字符过多，会导致json无法反解析为数组。
			$data	= $m->field( implode(",",$myFields) )->where($where)->limit(50)->select();
		}else if(is_numeric($data)){
			$data	= $m->field( implode(",",$myFields) )->find($data);
		}
		if(empty($data)) return;
		
		$dataNode		= $this->getDataNodeForModel($standerModelName);
		$t				= array();
		$t[$standerModelName]	= $data;
		$putData		= array("sourceNodeName"=>$this->myNodeName,'dataType'=>$data_type,"data"=>$t);
		dump($this->myNodeName);dump($data_type);dump($t);
		foreach ($dataNode as $dataN){
			$this->getMessageEngine()->put($dataN,json_encode($putData));
		}
		
		return true;
	} 
	//重置队列的内容，清空
	public function reset(){
		$this->getMessageEngine()->reset($this->myNodeName);
	}
	
	protected function putResultToMC($nodeURL,$data){
		$putData		= array('dataType'=>self::SYNC_TYPE_RESULT,"data"=>$data);
		$this->getMessageEngine()->put($nodeURL,json_encode($putData));
		return true;
	}
	
	/**
	 * 读取消息列表数据，根据数据，使用不同的方法处理
	 * @param		$isClear		是否是清楚队列信息，如果是，则只获取节点数据，不处理
	 * **/
	public function getDataFromMC($isClear=false){
		$data	= $this->getMessageEngine()->get($this->myNodeName);
		if($isClear) return false;
		if(empty($data)) return false;
		$getAllData	= json_decode($data,true);
		dump($data);dump($this->myNodeName);
		//dump($getAllData["data"]);
		
		switch($getAllData["dataType"]){
			case self::SYNC_TYPE_DATA:
				$resultInfo		= $this->realDataInfo($getAllData["data"],$getAllData["sourceNodeName"]);
				dump($resultInfo);
				$this->putResultToMC($getAllData["sourceNodeName"],$resultInfo);
				break;
			case self::SYNC_TYPE_RESULT:
				$this->realResultInfo($getAllData["data"]);
				break;
		}
		return true;
	}
	
	/**
	 * 处理业务数据
	 * **/
	protected function realDataInfo($dataList,$sourceNodeName){
		$resultInfo	= array();
		foreach($dataList as $standerModelName=>$dataInfo){
			//获取Model的信息
			$modelName	= $this->getMyModelNameFromStanderModel($standerModelName);
			$m	= D($modelName);
			//dump($modelName);dump($m);
			$m->skipDataPowerCheck	= true;
			if(empty($m)){
				continue;
			}
			$m->skipOptionsFilter	= true;
			
			$myFields		= $this->getModelFields($standerModelName);
			$sourceFields	= $this->getModelFields($standerModelName,$sourceNodeName);
			$resultData		= array();
			//获取model数据匹配的关联信息，如果有主键字段设置，则使用，如果没有，则使用第一个同步字段作为主键字段
			$pkFields	= $this->getPkFields($standerModelName,$sourceNodeName);
			dump("pkFields");
			dump($pkFields);
			foreach($dataInfo as $editData){
				$where		= array();
				if(empty($pkFields)){
					$where	= array($myFields[0]=>$editData[$sourceFields[0]]);
				}else{
					foreach($pkFields as $myField=>$sourceField){
						$where[$myField]	= $editData[$sourceField];
					}
				}
				
				$myData	= $m->where($where)->field(implode(",",$myFields))->find();	//本地数据
				//新的本地数据，更新数据时要用此数据[此数据通过字段映射转换得到]
				$i	= 0;$tData	= array();
				foreach($myFields as $f){
					$tData[$f]	= $editData[$sourceFields[$i++]];
				}
				//dump($where);dump($myFields);dump($sourceFields);dump($editData);dump($tData);
				if(empty($myData)){		//本端不存在此数据,无论状态都是添加。
					if($editData["sync_status"]==self::SYNC_DELETE){
						
					}else{
						$tData["sync_status"]	= self::SYNC_NORMAL;
						$m->add($tData);
					}
				}else if($myData["sync_status"]==self::SYNC_LOCK){
					//本端数据被锁定，则不进行数据同步更新。
				}else if(implode("-=-",$editData)==implode("-=-",$myData)){
					if($editData["sync_status"]==self::SYNC_DELETE){
						$this->deleteData($m,$where,$tData);
					}else{
						//数据内容一样，只更新状态
						if($myData["sync_status"]!=self::SYNC_NORMAL){
							$tData	= array();
							$tData["sync_status"]	= self::SYNC_NORMAL;
							$m->where($where)->save($tData);
						}
					}
				}else if($myData["sync_status"]==self::SYNC_INSERT || $myData["sync_status"]==self::SYNC_UPDATE || $myData["sync_status"]==self::SYNC_DELETE){
					if($this->isMainModel($standerModelName)){
						//主节点忽略，等待从节点更新数据后，根据结果更新数据状态
					}else{
						if($editData["sync_status"]==self::SYNC_DELETE){
							$this->deleteData($m,$where,$tData);
						}else{
							$tData["sync_status"]	= self::SYNC_REWRITE;
							$m->where($where)->save($tData);
						}
					}
				}else if($myData["sync_status"] == self::SYNC_NORMAL){
					if($editData["sync_status"]==self::SYNC_DELETE){
						$this->deleteData($m,$where,$tData);
					}else{
						$tData["sync_status"]	= self::SYNC_NORMAL;
						$m->where($where)->save($tData);
					}
				}else{
					$tData["sync_status"]	= self::SYNC_ERROR;
					$m->where($where)->save($tData);
				}
				//结果是普通的，返回操作会根据旧数据状态删除该删除的数据。
				$resultData[]	= array("Result"=>self::SYNC_NORMAL,"oldStatus"=>$editData["sync_status"],"data"=>$editData);
				dump($resultData);
			}
			$resultInfo[$standerModelName]	= $resultData;
		}
		
		return $resultInfo;
	}
	
	
	/*
	 * 处理数据处理结果
	 * ***/
	protected function realResultInfo($data){
		foreach($data as $standerModelName=>$dataList){
			$myFields	= $this->getModelFields($standerModelName);
			$modelName	= $this->getMyModelNameFromStanderModel($standerModelName);
			$m	= D($modelName);
			$m->skipDataPowerCheck	= true;
			if(empty($m)) continue;
			$m->skipOptionsFilter	= true;
			
			$pkFields	= $this->getPkFields($standerModelName);
			dump($pkFields);
			dump($dataList[0]);
			foreach($dataList as $val){
				$where	= array();$editData	= $val["data"];
				if(empty($pkFields)){
					$where	= array($myFields[0]=>$editData[$myFields[0]]);
				}else{
					foreach($pkFields as $myField=>$sourceField){
						$where[$myField]	= $editData[$myField];
					}
				}
				dump($where);
				if($val["oldStatus"]==self::SYNC_DELETE){
					$this->deleteData($m,$where);
				}else{
					$m->where($where)->save(array("sync_status"=>$val["Result"]));
				}
			}
		}
	}

	/**
	 * 同步数据删除的说明：
	 * 如果系统内没有其他的删除标记字段，则说明要求物理删除
	 * 如果系统中还有其他的删除标记字段，则标记删除，并设置同步状态为 SYNC_REAL_DEL
	 * */
	protected function deleteData($m,$where,$toData=array()){
		$delTag	= C("DELETE_TAGS");
		unset($delTag["sync_status"]);
		$realDel	= true;
		if(sizeof($delTag)>0){
			$dbFields	= $m->getDbFields();
			foreach($delTag as $key=>$val){
				if (in_array($key,$dbFields)) {
					$realDel	= false;
					break;
				}
			}
		}
		
		if($realDel){
			$m->where($where)->realDelete();
		}else{
			$delTag["sync_status"]	= self::SYNC_REAL_DEL;
			$m->where($where)->save(array_merge($toData,$delTag));
		}
	}
	//获取model要同步的数据的字段列表
	protected function getModelFields($standerModelName,$nodeName=""){
		if(empty($nodeName)) $nodeName	= $this->myNodeName;
		$fields	= explode(",",$this->syncConfig[$standerModelName][$nodeName]["fields"]);
		return array_merge($fields,array("sync_status"));
	}
	protected function getDataNodeForModel($standerModelName){
		$modelsInfo	= $this->syncSetInfo;
		return $modelsInfo[$standerModelName]["toDataNodes"];
	}
	protected function isMainModel($standerModelName,$nodeName=""){
		if(empty($nodeName)) $nodeName	= $this->myNodeName;
		return $this->syncConfig[$standerModelName][$nodeName]["isMain"];
	}
	protected function getMyModelNameFromStanderModel($standerModelName,$nodeName=""){
		if(empty($nodeName)) $nodeName	= $this->myNodeName;
		return $this->syncConfig[$standerModelName][$nodeName]["modelName"];
	}
	//获取数据的查询条件
	protected function getSyncWhere($standerModelName){
		return $this->syncConfig[$standerModelName][$this->myNodeName]["where"];
	}
	//获取数据唯一判定字段
	protected function getPkFields($standerModelName,$sourceNodeName=""){
		$myNodeName	= $this->myNodeName;
		$modelsInfo	= $this->syncConfig[$standerModelName];
		dump($modelsInfo);
		if(empty($modelsInfo[$myNodeName]["pkFields"])){
			return array();
		}else{
			$myPkFields	= $modelsInfo[$myNodeName]["pkFields"];
			if(empty($sourceNodeName)){
				//处理同步结果时，不需要知道对方的关键字段，只需要自己的字段即可。。因为传回的数据，就是本model的数据
				return array_combine($myPkFields,$myPkFields);
			}else{
				$sourcePkFields	= $modelsInfo[$sourceNodeName]["pkFields"];
				return array_combine($myPkFields,$sourcePkFields);
			}
		}
	}
}

//发送的数据
// array("myNodeURL"=>"","data"=>array("modelName"=>array(array(""),array("")),"modelName"=>array(array(""),array(""))));
//系统model与节点的对应关系
// array("system_name"=>"YangLaoJiGou","nodeList"=>array("SystemDataChange"));


//SYNC_MODEL_TO_MYMODEL	= array("modelName"=>"model")

//array("SYNC_MODELS_INFO"=>array("modelName"=>array("dataNodes"=>array("",""),"isMain"=>true,"fields"=>array("field"=>"sourceFields"))));
