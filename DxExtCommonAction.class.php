<?php
/**
 * Version：2.0
 * 功能描述：
 * 1.自动以Model名称,,通过设置  属性 theModelName 来处理，一般在Action中操作
 * 2.Action操作权限验证
 * 3.记录Action的操作日志
 * 4.多次编译模板文件。
 * */
class DxExtCommonAction extends Action {
	protected $model			= null;
	protected $theModelName		= "";
	
	function __construct() {
		parent::__construct();
		if(empty($this->model)) $this->model  = D($this->getModelName());
		else $this->theModelName	= $this->model->name;
	}

    private	$cacheActionList	= array();	//系统action的缓存，对应menu表
	function _initialize() {
	    $log_id =	$this->writeActionLog();
	    
	    if(C("DISABLE_ACTION_AUTH_CHECK")!==true){
    		$this->cacheActionList	= DxFunction::getModuleActionForMe();
    		//dump($_SESSION);dump($this->cacheActionList["myAction"]);die();
    
    		if (!DxFunction::checkNotAuth(C('NOT_AUTH_ACTION'),C('REQUIST_AUTH_ACTION'))){
    		    //为了不验证公共方法，不如：public、web等，所以将session验证放在里面。
    			if(0 == intval(session(C("USER_AUTH_KEY")))) {
    			    $url    =   C("LOGIN_URL");
    			    if($url[0]!="/") $url = U($url);
    			    redirect($url,0,"");
    			    //redirect($url,2,"登录超时，请重新登录!");
    			}
    			//判断用户是否有当前动作操作权限
    			$privilege = $this->check_action_privilege();
    			if (!$privilege) {  //无权限
    				if($log_id){
    					$this->updateActionLog($log_id);
    				}
    				if(C('LOG_RECORD')) Log::save();
    				$this->success("您无权访问此页面!","showmsg");
    				exit;
    			}
    		}
	    }

		//自定义皮肤
		if (cookie('RESTHOME_SKIN_ROOT')) {
			$SKIN_ROOT = $_COOKIE['RESTHOME_SKIN_ROOT'];
		} else {
			$SKIN_ROOT = "__PUBLIC__/project/Skin/".C("DEFAULT_SKIN")."/";
		}
		$this->assign('SKIN_ROOT', $SKIN_ROOT);
		
		//将系统变量加载到config中，供系统使用。
		$sysSetData		= S("Cache_Global_SysSeting");
		if(empty($sysSetData)){
			$sysSet		= D("SysSetting");
			$sysSetData	= $sysSet->select();
			S("Cache_Global_SysSeting",$sysSetData);
		}
		foreach($sysSetData as $set){
			C("SysSet.".$set["name"],$set["val"]);
		}
	}

	/**
	 * 通用导入程序：导入excel或的csv等数据到数据库中
	 */
	public function import(){
	    $this->importFromExcel();
	}
	
	public function importFromExcel(){
        // 引入excel类库
        require_once DXINFO_PATH.'/Vendor/PHPExcel_1.7.9/PHPExcel.php';
		require_once DXINFO_PATH.'/Vendor/PHPExcel_1.7.9/PHPExcel/Reader/Excel2007.php';
		//require_once 'PHPExcel/IOFactory.php';	
	    if (! empty ( $_FILES ['file_stu'] ['name'] )){
        $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
        $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
        $file_type = $file_types [count ( $file_types ) - 1];
        $filename=iconv("utf-8","gb2312",$_FILES['file_stu']['name']);
        if (strtolower ( $file_type ) != "xls"){
           $this->error ( '不是Excel文件，重新上传！' );
        }
        $savePath = "./Public/upload/";
        if(!move_uploaded_file($tmp_file,$savePath.$filename))
        $this->error ( '上传文件失败！' );
        
        // 获取xls的第一行，及标题列,必须存在标题
        // 将标题列中的字段与要导入的字段进行匹配，如果导入字段匹配则执行数据导入
        
        
		$objReader = PHPExcel_IOFactory::createReader( 'Excel5' ); 
        $objPHPExcel = $objReader->load($savePath.$filename);
        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
        $highestColumn = $objPHPExcel->getActiveSheet()->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $model=$this->model;
        $dbfields=$model->getDbFields();
        for($row = 1; $row <= $highestRow; $row++){
        	for($col = 0; $col < $highestColumnIndex; $col++){
            $excelData[$row][$col] = (string)$objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue();
        	}
        	foreach ($dbfields as $k=>$v){
        	$data[$v]=$excelData[$row][$k];
        	}
        	if($model->create($data)){
        		if($model->add()){
        		$error[$row]=true;
        		}
        	}
        }
        $objPHPExcel->getActiveSheet()->insertNewColumnBefore('A',1);
        for($row = 1; $row <= $highestRow; $row++){
        	if($error[$row]==true){
        	 $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,'导入成功');  
        	}
        	else{ 
        	$objPHPExcel->getActiveSheet()->setCellValue('A'.$row,'导入失败');
        	}
        }
        $objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
        $objWriter->save ( $savePath.$filename );
	  }
	  $this->success ( '导入数据完成！' );
	}
	
	/**
	 * (判断当前用户是否有这种动作的权限)
	 * @param    (字符串)     (action_name)    (动作)
	 */
	public function check_action_privilege($module_name = '',$action_name = '') {
		$cacheAction	= $this->cacheActionList;
		if(empty($cacheAction)) return false;	//不通过
		
		$thisModule	= empty($module_name)?MODULE_NAME:$module_name;
		$thisAction	= empty($action_name)?ACTION_NAME:$action_name;
		//dump($thisModule);dump($thisAction);
		//dump($cacheAction["myAction"][$thisModule][$thisAction]);dump($cacheAction["allAction"][$thisModule][$thisAction]);
		if(empty($cacheAction["myAction"][$thisModule][$thisAction])){
			if(empty($cacheAction["allAction"][$thisModule][$thisAction])){
				return true;	//未定义的Action，默认都有权限操作
			}else{
				return false;
			}
		}else
			return true;
	}
	
	protected function getModelName() {
		if(empty($this->theModelName)) {
			$this->theModelName	= parent::getActionName();
		}
		return $this->theModelName;
	}

	/**
	 * 将用户操作写入日志表中
	 * 初始操作，无论是否权限验证是否通过，都存储，再权限验证后，更新操作的验证信息。
	 */
	public function writeActionLog($moduleName="",$actionName=""){
		$model = D('OperationLog');
		$model->ip  		= get_client_ip()."_".$_SERVER["REMOTE_ADDR"];
		$model->action   	= empty($actionName)?ACTION_NAME:$actionName;
		$model->module 		= empty($moduleName)?MODULE_NAME:$moduleName;
		$action_name		= $this->cacheActionList["allAction"][$model->module][$model->action];

		if(sizeof($action_name)>1){
			$action_name	= argsInRequest($action_name,$_REQUEST);
		}else{
			$action_name	= array_values($action_name);
			$action_name	= $action_name[0]["menu_name"];
		}
		if(empty($action_name) || is_array($action_name)) $model->action_name	= "";
		else $model->action_name	= $action_name;
		$model->account_name  = $_SESSION[C("LOGIN_USER_NICK_NAME")];
		$model->account_id  = $_SESSION[C("USER_AUTH_KEY")];
		$model->over_pri  	= 0;
		unset($_REQUEST['_URL_']);
		unset($_REQUEST["_gt_json"]);
		$model->options = var_export($_REQUEST,true);
		$log_id =$model->add();
		return $log_id;
	}
	public function updateActionLog($log_id){
		$model = D('OperationLog');
		$model ->over_pri =1;
		$model->where(array('id'=>$log_id))->save();
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param string $name 数据对象名称
	 +----------------------------------------------------------
	 * @return HashMap
	 +----------------------------------------------------------
	 * @throws ThinkExecption
	 +----------------------------------------------------------
	 */
	protected function _search($name = '') {
		$model  = $this->model;
		$map    = array ();
		//支持like、大于、小于
        $dbFields   = array_keys($model->getListFields());
		if(APP_DEBUG) Log::write(var_export($_REQUEST,true).var_export($dbFields,true).MODULE_NAME."|".ACTION_NAME,Log::INFO);
		//$dbFields	= $model->getDbFields();
		foreach($_REQUEST as $key=>$val){
			if ($val!=0 && (empty($val) || str_replace("%","",$val)=="")) continue;
			$fieldAdd	= "";
			if( substr($key,0,4)=="egt_" ){
				$key		= substr($key,4);
				$fieldAdd	= "egt";
			}else if( substr($key,0,4)=="elt_" ){
				$key		= substr($key,4);
				$fieldAdd	= "elt";
			}else if( substr($key,0,3)=="gt_" ){
				$key		= substr($key,3);
				$fieldAdd	= "gt";
			}else if( substr($key,0,3)=="lt_" ){
				$key		= substr($key,3);
				$fieldAdd	= "lt";
			}
			
			if (in_array($key,$dbFields,true)) {
				if($fieldAdd == "egt" || $fieldAdd=="elt" || $fieldAdd == "gt" || $fieldAdd=="lt"){
					if(array_key_exists($key, $map))
						$map[$key]	= array($map[$key],array($fieldAdd,$val),"and");
					else $map[$key]	= array($fieldAdd,$val);
				}else if(strtolower(trim($val))=="null"){
					$map[$key] = array("exp","is null");
				}else if($val[0]=="%" || $val[strlen($val)-1]=="%")
					$map[$key] = array("like",$val);
				else
					$map[$key] = $val;
			}
		}
		if(APP_DEBUG) Log::write(var_export($map,true).MODULE_NAME."|".ACTION_NAME."_Search",Log::INFO);
		return $map;
	}

	protected function _searchToString(){
		$s		= array();
		foreach($_REQUEST as $key=>$val){
			$s[]	= $key."=".urldecode($val);
		}
		return implode("&", $s);
	}

	/**
	 +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param Model $model 数据对象
	 * @param HashMap $map 过滤条件
	 * @param string $sortBy 排序
	 * @param boolean $asc 是否正序
	 +----------------------------------------------------------
	 * @return void
	 +----------------------------------------------------------
	 * @throws ThinkExecption
	 +----------------------------------------------------------
	 */
	protected function _list($model, $map, $sortBy = '', $asc = false) {
		//排序字段 默认为主键名
		if (isset ( $_REQUEST ['_order'] )) {
			$order = $_REQUEST ['_order'];
		} else {
			$order = ! empty ( $sortBy ) ? $sortBy : $model->getPk ();
		}
		//排序方式默认按照倒序排列
		//接受 sost参数 0 表示倒序 非0都 表示正序
		if (isset ( $_REQUEST ['_sort'] )) {
			$sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}
		//取得满足条件的记录数
		$count = $model->where ( $map )->count ( 'id' );
		if ($count > 0) {
			import ( "ORG.Page" );
			//创建分页对象
			if (! empty ( $_REQUEST ['listRows'] )) {
				$listRows = $_REQUEST ['listRows'];
			} else {
				$listRows = '';
			}
			$p = new Page ( $count, $listRows );
			//分页查询数据

			$voList = $model->where($map)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select( );
			//echo $model->getlastsql();
			//分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val )) {
					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
				}
			}
			//分页显示
			$page = $p->show ();
			//列表排序显示
			$sortImg = $sort; //排序图标
			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
			$sort = $sort == 'desc' ? 1 : 0; //排序方式
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'sort', $sort );
			$this->assign ( 'order', $order );
			$this->assign ( 'sortImg', $sortImg );
			$this->assign ( 'sortType', $sortAlt );
			$this->assign ( "page", $page );
		}
		import("ORG.Util.Cookie");
		cookie ( '_currentUrl_', __SELF__ );
		return;
	}
	
	/**
	 * TP的系统日志是在函数析构的时候触发写入，所以必须强制调用。
	 * */
	public function __destruct(){
		parent::__destruct();
	}
}
?>
