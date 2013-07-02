<?php
/**
 * Version：2.0
 * 1.关闭模板的多次编译缓存。设置属性 cacheTpl = false; 
 * 2.数据的增删改查
 * 3.数据状态变更。
 * 4.数据唯一性验证
 * */
class DataOpeAction extends DxExtCommonAction{
	protected $defaultWhere		= array();
	//系统会自动根据Model信息生成Tpl文件的cache，如果一个Model的两个index展示界面不同，比如：一个是老人信息，有查询，一个是老人核查，没有查询，则需要将此属性设置为false，不启用tpl缓存
	protected $cacheTpl			= true;
	        
	/* 数据列表 for Grid **/
	public function get_datalist(){
        $model	= $this->model;
        if(empty($model)) die("model为空!");
		$enablePage	= $model->getModelInfo("enablePage");
		if($enablePage!==false) $enablePage	= true;
		require_once (DXINFO_PATH."/Vendor/GridServerHandler.php");
        $gridHandler 	= new GridServerHandler();
        if($enablePage){
			$start 			= intval($gridHandler->pageInfo["startRowNum"])-1;
			$pageSize 		= intval($gridHandler->pageInfo["pageSize"]);
			$pageSize		= ($pageSize==0?20:$pageSize);
        }
        if($start<0) $start = 0;
		
		$where			= array_merge($this->defaultWhere,$this->_search());
		//使用Model连贯操作时，每一个连贯操作，都会往Model对象中复制，如果嵌套使用Model的连贯操作，会覆盖掉原来已经存在的值，导致bug。
		$fieldsStr = $model->getListFieldString();
		if(isset($_REQUEST['export']) && !empty($_REQUEST['export'])){
            $data_list  = $model->where($where)->field($fieldsStr)->order($model->getModelInfo("order"))->select();
            $this->export($data_list, trim($_REQUEST['export']));
        }else{
            if($enablePage){
                $data_list  = $model->where($where)->field($fieldsStr)->limit( $start.",".$pageSize )->order($model->getModelInfo("order"))->select();
            }else
                $data_list  = $model->where($where)->field($fieldsStr)->order($model->getModelInfo("order"))->select();
        }
        if($_REQUEST["showsql"]=="1"){
            print_r($where);
            echo "<br />-------<br />";
            print_r($model->getLastSQL());
            echo "<br />-------<br />";
        }
        //无数据时data_list = null,此时返回的数据，grid不会更新rows，这导致，再删除最后一条数据时，grid无法删除前端的最后一样。
        if(empty($data_list)){
            $data_list	= array();
        }else{
            $data_change	= $model->getModelInfo("data_change");
            if(is_array($data_change)){
                foreach($data_change as $key=>$val){
                    $val($data_list,$key);
                }
            }
        }

		$data_count  	= $enablePage?$model->where($where)->count():sizeof($data_list);
		$gridHandler->setData($data_list);
		$gridHandler->setTotalRowNum($data_count);
        $gridHandler->printLoadResponseText();
	}
    
    /**
     * 处理数据导出.
     * @param $data array 要导出的数据记录集
     * @param $type string 导出数据类型.默认为xls
     * @param $fields_list array 要导出的的数据的属性.默认使用model->getExportFields().<br/>
     * 格式说明array('field'=>array('name'=>"field", 'title'=>"Tittle in list"));
     * @param $subject string 标题.
     * @return */
    protected function export($data, $type="xls", $fields_list=null, $subject=null,$customHeader=null){
        $model	= $this->model;
        if(empty($model)) die("model为空!");
        
        if($fields_list===null){
            $fields_list = $model->getExportFields();
        }
        if($subject===null){
        	$subject	= $this->model->getModelInfo("title");
        	$exportname = $this->model->getModelInfo("title").".".$type;
        }else{
        	$subject	= $subject;
        	$exportname = $subject.".".$type;
        }
        if($customHeader===null){
        	$customHeader	= $this->model->getModelInfo("gridHeader");
        }
        
        if(empty($exportname)){
            $exportname="export";
        }
        $exportname=get_filename_bybrowser($exportname);
        
        //dump($fields_list);dump($data);die();
        //导出excel
        header("Pragma: no-cache");
        header('Content-type:application/vnd.ms-excel; charset=UTF-8');
        header("Content-Disposition:attachment;filename=$exportname.xls");
        $this->assign("subject",$subject);
        $this->assign("listFields",$fields_list);
        $this->assign('objectData',$data);
        if(!empty($customHeader)) $this->assign("customHeader",$customHeader);

        if(file_exists(THEME_PATH.$this->theModelName.'/export'.C('TMPL_TEMPLATE_SUFFIX'))){
            $this->display("export");
            exit();
        }else{
            $this->display("DxPublic:data_export");
            exit();
        }
    }

	
	/* 保存数据 **/
	public function save(){
        $m  = $this->model;
        //强制，将设置为readOnly的字段注销掉，防止自己构造post参数。。比如：入院时间是不允许修改的，但是用户可以自己构造post数据，提交入院时间字段，则tp的create会更新这个字段。
        //目前的Readonly只支持edit的时候，add的时候，不支持。
        foreach($m->getListFields() as $key=>$val){
            if(!empty($_REQUEST[$m->getPk()]) && (isset($val["readOnly"]) && $val["readOnly"])){
                unset($_GET[$val["name"]]);
                unset($_POST[$val["name"]]);
                unset($_REQUEST[$val["name"]]);
            }else if($val["type"]=="uploadFile" && (is_array($_REQUEST[$key]) || !empty($_REQUEST["old_".$key]))){
                //如果数据传递过来的是数组，则进行数据整合为json格式，比如：多文件上传.
                $_REQUEST[$key]	= $_POST[$key]	= $_GET[$key]	= $this->moveAndDelFile($key,$m->getModelName());
            }else if($val["type"]=="set" && is_array($_REQUEST[$key])){
                //如果字段是set和 mul select。则将数据整合为json
                if($val["valFarmat"]=="douhao")
                    $_REQUEST[$key]	= $_POST[$key]	= $_GET[$key]	= json_encode($_REQUEST[$key]);
                else
                    $_REQUEST[$key]	= $_POST[$key]	= $_GET[$key]	= implode(",",$_REQUEST[$key]);
            }elseif ($val['type']	== 'cutPhoto'){
            	$key = DxFunction::move_file(C("TEMP_FILE_PATH").'/'.$_POST[$key],MODULE_NAME.'/');
            }
        }

        if(!empty($m) && $m->create()){
            $v = false;
            //dump($_REQUEST);dump($m);die();
            if(!empty($_REQUEST[$m->getPk()])){
                $v      = $m->save();
                $pkId 	= $_REQUEST[$m->getPk()];
            }else{
                $v      = $m->add();
                //$pkId	= $m->getLastInsID();//当有后置操作时可能取不到值。
                $pkId	= $v;
            }
            if($v === false){
                $this->ajaxReturn($m->getDbError(),session(MODULE_NAME."_modelTitle")."数据操作失败，请与管理员联系!".$m->getError(),0);
            }else{
                $returnD    = array("id"=>$pkId,"rows"=>$v);
                $this->ajaxReturn($returnD,session(MODULE_NAME."_modelTitle")."数据操作成功!",1);
            }
        }else{
        	fb::log($m);
        	$msg	= $m->getError();
        	$this->ajaxReturn($m->getError(),"创建数据出现错误!请检查必填项是否填写完整!($msg)",0);
        }
	}
		
    protected function dxDisplay($templateFile){
		$tempFile	= TEMP_PATH.'/'.$this->theModelName.'_'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX');
		if(!$this->cacheTpl || C('APP_DEBUG') || !file_exists($tempFile)){
            if(C("TOKEN_ON")){
                //多次编译会导致生成多个TOKEN
                C("TOKEN_ON",false);
                $tempT	= $this->fetch($templateFile);
                C("TOKEN_ON",true);
            }else
                $tempT	= $this->fetch($templateFile);
			file_put_contents($tempFile, $tempT);
		}
		$this->display($tempFile);

    }
    /* 显示页面内容 **/
	public function index(){
		$model  = $this->model;
		if(empty($model)) die();

		//支持通过url传递过来的ModelTitle
		$enablePage	= $model->getModelInfo("enablePage");
		if($enablePage!==false) $enablePage	= true;
		session(MODULE_NAME."_modelTitle",empty($_REQUEST["modelTitle"])?$model->getModelInfo("title"):$_REQUEST["modelTitle"]);
		$addTitle	= $model->getModelInfo("addTitle");
		if(empty($addTitle)) $addTitle	= "新增".session(MODULE_NAME."_modelTitle");
		$editTitle	= $model->getModelInfo("editTitle");
		if(empty($editTitle)) $editTitle	= "修改".session(MODULE_NAME."_modelTitle");
		
		$this->assign("modelInfo",
					array(
						"modelTitle"=> session(MODULE_NAME."_modelTitle"),
						"addTitle"=>$addTitle,
						"editTitle"=>$editTitle,
						"otherManageAction"=>$model->getModelInfo("otherManageAction"),
						"searchHTML"=>$model->getModelInfo("searchHTML"),
						"readOnly"=>$model->getModelInfo("readOnly")?$model->getModelInfo("readOnly"):false,
						"gridHeader"=>$model->getModelInfo("gridHeader"),
						"enablePage"=>$enablePage?"1":"0",
						"helpInfo"=>$model->getModelInfo("helpInfo"),
					));
		
		$gridField	= $model->fieldToGridField();
		//因为Think模板引擎强制将所欲的{}认为是标签，进行解析，而在preg_**函数解析的过程中，会给所有的"加上\，则TP需要对解析出的函数执行 stripslashes，一切导致 \n变成了n，从而导致字段的js代码出错
		$this->assign("gridFields",str_replace("{","{ ",json_encode($gridField["gridFields"])));
		$this->assign("datasetFields",str_replace("{","{ ",json_encode($gridField["datasetFields"])));
		$this->assign("listFields",$model->getEditFields());		//为了在Search中直接使用字段定义生成input框
		$this->assign("InitSearchPara",$this->_searchToString());	//通过URL传递的数据过滤参数
		if(isset($_REQUEST["ignoreInitSearch"])){
			//如果设置忽略初始化查询条件，则设置原始路径为不带参数路径。
			$this->assign("ignoreInitSearch","ignore");
		}else{
			$this->assign("ignoreInitSearch","");
		}
		foreach($_REQUEST as $key=>$val){
			$this->assign($key,str_replace("%","",$val));
		}
        $this->dxDisplay("data_list");
    }
    
    /* 追加数据 **/
	public function add(){
 		//print_r($_REQUEST);die("99");
		$model  = $this->model;
		// dump($model);die();
		if(empty($model)) die();
				
		//判断是否为修改数据
		$vo = array();$pkId	= 0;
		if(isset($_REQUEST["id"]))
			$pkId     = intval($_REQUEST["id"]);

		//列出字段列表
		$this->assign("listFields",$model->getEditFields($pkId));
// 		dump($model->getEditFields($pkId));

		if($pkId>0){
			//要修改的 数据内容
			$vo     = $model->find( $pkId );
			if($vo){
				$this->assign('pkId',array($model->getPk(),$pkId));
			}else{
				$this->error('要修改的数据不存在!请确认操作是否正确!');
			}
		}
		$this->assign('valid', $model->getValidate(Model::MODEL_INSERT));
		$this->assign('objectData', array_merge($vo,$_REQUEST));

        $this->dxDisplay("Public:data_edit");
	}
    
	/**
	 * 数据展示页面
	 * */
    public function view(){
        $this->dxDisplay("Public:data_view");
    }
	
	/**
	 * 快速改变某个数据某个字段的值，比如，修改数据状态。
	 * @v    要改变的数据值
	 * @id   要改变的数据id，可以使用逗号隔开，一次修改多个
	 * @f    要修改的字段名称
	 */
	public function change_status()
	{
	    $fieldName  = "status"; 
	    if(!empty($_REQUEST["f"])){
	        $fieldName    = $_REQUEST["f"];
	    }
		$m  		= $this->model;
		$pk			= $m -> getPk();
		$id			= $_REQUEST["id"];
		if (!empty($m) && isset($id)){
			$where	= array ($pk => array ('in', explode ( ',', $id ) ) );
            $data[$fieldName]   = trim($_REQUEST["v"]);
            $data   = array_merge($_REQUEST,$data);
			if($m -> where($where) -> save($data))
				$this -> ajaxReturn("","状态修改成功!",1);
			else
				$this -> ajaxReturn("","状态修改失败!请重试!",0);
		}else $this -> ajaxReturn("","非法请求!请j试!",0);
	}

    /** 通过ajax提交删除请求 **/
    public function delete(){
        $deleteState    = false;
        $model  		= $this->model;
        if (! empty ( $model )) {
            $pk = $model->getPk ();
            $id = $_REQUEST["id"];
            if (isset ( $id )) {
                if(strpos($id, ",")) $condition = array ($pk => array ('in', explode ( ',', $id ) ) );
                else $condition = array($pk=>intval($id));
                $list			= $model->where ( $condition )->delete();
                $deleteState	= true;
            }
        }

        if($deleteState) $this->ajaxReturn(0,"删除".session(MODULE_NAME."_modelTitle")."成功!",1,"JSON");
        else $this->ajaxReturn(0,"删除".session(MODULE_NAME."_modelTitle")."失败!",0,"JSON");
    }

	public function __destruct(){
		parent::__destruct();
	}
	
    /**ajax表单验证方法.<br/>
     * 用户函数原型:<br/>
     * //此函数应用到ThinkPHP model的函数验证上
     * function foo(array('val'=>'field value', 'id'=>'field_id'))<br/>
     * 返回值:true,验证通过;false,验证不通过.
     * function ajaxfoo(array('val'=>'field value', 'id'=>'field_id', ['pk'=>'record id']))<br/>
     * 返回值定义array('field_id', true, [msg])*/
    public function checkFieldByFunction(){
        $func	= "ajax".$_REQUEST['func'];
        $id		= $_REQUEST['fieldId'];
        $ret	= array($id,false,'不能通过数据验证,请输入其它值.');
        if(function_exists($func)){
            $param	= array('val'=>$_REQUEST['fieldValue'], 'id'=>$id, "name"=>$_REQUEST['fieldName']);
            if(isset($_REQUEST['pk'])){
                $param['pk']=$_REQUEST['pk'];
            }
            $ret	= call_user_func($func, $param);
        }
        die(json_encode($ret));
    }
    /**
     * 数据验证：后台验证数据的唯一性，比如：用户登录名
     * */
    public function checkFieldByUnique(){
    	$m  	= $this->model;
    	$ret	= array($_REQUEST['fieldId'],false,'数据不唯一,请输入其它值.');
    	$name	= $_REQUEST['fieldName'];
    	$data	= array($name=>$_REQUEST['fieldValue']);
    	$type	= Model::MODEL_INSERT;
    	if(!empty($_REQUEST["pk"])){
    		$data[$this->model->getPk()]	= $_REQUEST["pk"];
    		$type		= Model::MODEL_UPDATE;
    	}
    	if($m->checkUnique($name,$data,$type)){
    		$ret[1]	= true;
    		$ret[2]	= "数据可用!";
    	}else{
    		$ret[2]	= $m->getError();
    	}
    	die(json_encode($ret));
    }
    
    /**
     * 将新上传的文件移动到实际目录中，并将旧的无效的文件删除
     * @param		$key		字段名
     * @param		$modelName	model名称作为存放文件的目录名
     * @param		$returnJson	是否返回的数据格式化为json格式
     * 注意：
     * Linux下，ls /home/a/../c/p.php  可以用，但是cp /home/a/../c/p.php /tmp/则会提示p.php文件不存在，所以需要将路径中..移除掉。
     * 原设计：为了将文件存储路径 (./ORGA/Runtime) 和 图片显示的Url(http://xxx/Uploads/../ORGA/Runtime)统一处理，所以数据库存储路径中包含 ../  
   	 * */
    protected function moveAndDelFile($key,$modelName,$returnJson=true){
    	$value	= array();
    	foreach($_REQUEST[$key] as $one){
    		$value[]	= json_decode($one,true);
    	}
    	//旧文件被删除的，在这里要进行删除，，，全新的文件，要移动到实际存放路径。
    	$old_val	= json_decode($_REQUEST["old_".$key],true);
    	if(sizeof($old_val)>0){
    		foreach($old_val as $ov_key=>$v){
    			$cunzai	= false;
    			foreach($value as $nv_key=>$nv){
    				if($nv["url"]==$v["url"]){
    					//"real_name":"1411270.png","name":"13565930481411270.png","file_path":"20121227\/13565930481411270.png","size":109886,"type":"image\/png","url":".\/ORGA\/Runtime\/TMMP_IMG\/20121227\/13565930481411270.png","thumbnail_url":".\/ORGA\/Runtime\/TMMP_IMG\/thumbnail\/20121227\/13565930481411270.png","delete_url":"http:\/\/job\/yanglaoyuan2\/?file=13565930481411270.png","delete_type":"DELETE"
    					$cunzai	= true;
    					$value[$nv_key]["cunzai"]	= true;
    					break;
    				}
    			}
    			$old_val[$ov_key]["cunzai"]	= $cunzai;
    		}
    	}
    	foreach($old_val as $ov_key=>$ov){
    		if($ov["cunzai"]!==true){
    			unlink(C("UPLOAD_BASE_PATH").dirname($ov["url"])."/".$ov["name"]);
    			if(!empty($ov["thumbnail_url"])){
    				unlink(C("UPLOAD_BASE_PATH").dirname($ov["thumbnail_url"])."/thumbnail_".$ov["name"]);
    			}
    		}
    	}
    	foreach($value as $tkey=>$tval){
    		if($tval["cunzai"]!==true){
    			$value[$tkey]["url"]	= move_file(substr(dirname($tval["url"]),2)."/".$tval["name"],"/".$modelName,"dateY_m");
    			if(!empty($tval["thumbnail_url"])){
    				$value[$tkey]["thumbnail_url"]	= move_file(substr(dirname($tval["thumbnail_url"]),2)."/".$tval["name"],"/".$modelName,"dateY_m","thumbnail_".$tval["name"]);
    			}
    		}
    	}
    	if($returnJson)
    		return json_encode($value);
    	else
    		return $value;
    }
}
