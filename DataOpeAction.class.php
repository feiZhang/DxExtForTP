<?php
/**
 * Version：2.0
 * 1.关闭模板的多次编译缓存。设置属性 cacheTpl = false; 
 * 2.数据的增删改查
 * 3.数据状态变更。
 * 4.数据唯一性验证
 * */
class DataOpeAction extends DxExtCommonAction{
    protected $defaultWhere = array();
    private $disableDxTplCache = false;

    /* 数据列表 for Grid **/
    public function get_datalist(){
        $model  = $this->model;
        if(empty($model)) die("model为空!");
        if ($_REQUEST ["print"] == "1") {
            $fieldsStr = $model->getPrintFieldString();
            $enablePage = false;
        } else {
            $enablePage = $model->getModelInfo ( "enablePage" );
            if ($enablePage !== false) $enablePage = true;
            $fieldsStr = $model->getListFieldString();
        }
        require_once (DXINFO_PATH."/Vendor/GridServerHandler.php");
        $gridHandler    = new GridServerHandler();
        if($enablePage){
            $start          = intval($gridHandler->pageInfo["startRowNum"])-1;
            $pageSize       = intval($gridHandler->pageInfo["pageSize"]);
            $pageSize       = ($pageSize==0?20:$pageSize);
        }
        if($start<0) $start = 0;

        $where          = array_merge($this->defaultWhere,$this->_search());
        //使用Model连贯操作时，每一个连贯操作，都会往Model对象中赋值，如果嵌套使用Model的连贯操作，会覆盖掉原来已经存在的值，导致bug。
        if(isset($_REQUEST['export']) && !empty($_REQUEST['export'])){
            $data_list  = $model->where($where)->field($fieldsStr)->order($model->getModelInfo("order"))->select();
        }else{
            if($enablePage){
               $data_list  = $model->where($where)->field($fieldsStr)->limit( $start.",".$pageSize )->order($model->getModelInfo("order"))->select();
            }else
                $data_list  = $model->where($where)->field($fieldsStr)->order($model->getModelInfo("order"))->select();
        }
        fb::log(MODULE_NAME."get_datalist:".$model->getLastSQL());
        //无数据时data_list = null,此时返回的数据，grid不会更新rows，这导致，再删除最后一条数据时，grid无法删除前端的最后一样。
        if(empty($data_list)){
            $data_list  = array();
        }else{
            $data_change    = $model->getModelInfo("data_change");
            if(is_array($data_change)){
                foreach($data_change as $key=>$val){
                    DxFunction::$val($data_list,$key);
                }
            }
        }
        //计算总计：
        if($model->getModelInfo("showTotal") && sizeof($data_list)>0){
            $total  = array();
            foreach ($data_list as $data){
                foreach($data as $index=>$vvv){
                    $total[$index] += floatval($vvv);
                }
            }
            foreach($this->model->getListFields() as $fieldName=>$field){
                if(array_key_exists("total",$field) && array_key_exists($fieldName,$total)){
                    $total[$fieldName] = $field["total"];
                }
            }
            $data_list[] = $total;
        }

        if ($_REQUEST ["print"] == "1"){
            $this->ajaxReturn(array("data"=>$data_list,"fields"=>$model->getPrintFields()));
        }else if(isset($_REQUEST['export']) && !empty($_REQUEST['export'])){
            $this->export($data_list, trim($_REQUEST['export']));
        }else{
            $data_count     = $enablePage?$model->where($where)->count():sizeof($data_list);
            $gridHandler->setData($data_list);
            $gridHandler->setTotalRowNum($data_count);
            $gridHandler->printLoadResponseText();
        }
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
        $model  = $this->model;
        if(empty($model)) die("model为空!");

        if($fields_list===null){
            $fields_list = $model->getExportFields();
        }
        if($subject===null){
            $subject    = $this->model->getModelInfo("title");
            $exportname = $this->model->getModelInfo("title").".".$type;
        }else{
            $subject    = $subject;
            $exportname = $subject.".".$type;
        }
        if($customHeader===null){
            $customHeader   = $this->model->getModelInfo("gridHeader");
        }
        
        if(empty($exportname)){
            $exportname="export";
        }
        $exportname=DxFunction::get_filename_bybrowser($exportname);
        
        //dump($fields_list);dump($data);die();
        //导出excel
        header("Pragma: no-cache");
        header('Content-type:application/vnd.ms-excel; charset=UTF-8');
        header("Content-Disposition:attachment;filename=$exportname.xls");
        $this->assign("subject",$subject);
        $this->assign("listFields",$fields_list);
        $this->assign('objectData',$data);
        if(!empty($customHeader)) $this->assign("customHeader",$customHeader);

        $this->display("data_export");
    }

        
    /* 
     * 保存add和edit页面提交的数据
     * 1.保存数据
     * 2.提示信息
     * 数据操作与数据返回分离，如果在子类中重定义save方法，可以复用installOrUpdate方法。。即：数据操作，与数据展现分离
     * 注意：php中 if(0=="err") 为true 
     **/
    public function save(){
        fb::log($_REQUEST,$m);
        $m  = $this->model;
        $v = $this->insertOrUpdate($m);
        if($v === "create_err"){
            $msg    = $m->getError();
            $this->ajaxReturn($msg,"创建数据失败!请检查必填项是否填写完整!($msg)",0);
        }else if($v === false){
            $this->ajaxReturn($m->getDbError(),"数据操作失败，请与管理员联系!".$m->getError(),0);
        }else{
            $this->ajaxReturn($v,"数据操作成功!",1);
        }
    }
    
    /**
     * dxDisplay实现二次编译功能。。
     * @param string $templateFile 模板名称
     * @param string $cacheType    使用同一个模版文件，但是需要二次编译为不同的缓存。
     */
    protected function dxDisplay($templateFile,$cacheType=""){
        //一个Model可能被两个Module使用，但是显示的界面不同。。:机构管理－》监测指标
        //一个Module的同一个页面，不同用户显示的界面不同。。:机构管理－》监测指标
        //相同model的add 和 edit 页面，listFields完全相同，但是显示内容可以不同。
        if($this->model instanceof DxExtCommModel){
            $cacheAliaName = "_".$cacheType."_".$this->model->getModelInfoMd5()."_".$this->model->getListFieldsMd5()."_".session('role_id');
        }else{
            //PublicAction 没有model
            $cacheAliaName = "_".$cacheType."_".session('role_id');
        }
        //$cacheAliaName .= "_".md5(json_encode($_REQUEST));
        $tempFile   = TEMP_PATH.'/'.MODULE_NAME.'_'.ACTION_NAME.$cacheAliaName.C('TMPL_TEMPLATE_SUFFIX');
        if(C("APP_DEBUG") || $this->disableDxTplCache===true || !file_exists($tempFile) ){
            if(C("TOKEN_ON")){
                //多次编译会导致生成多个TOKEN
                C("TOKEN_ON",false);
                $tempT  = $this->fetch($templateFile);
                C("TOKEN_ON",true);
            }else
                $tempT  = $this->fetch($templateFile);
            file_put_contents($tempFile, $tempT);
        }
        return $this->fetch($tempFile);
    }
    public function setDxTplCacheDisable(){
        $this->disableDxTplCache = true;
    }
    /* 显示页面内容 **/
    public function index(){
        $model  = $this->model;
        if(empty($model)) die();

        $this->assign ( "pkId", $model->getPk());
        $this->assign ( "modelInfo", $this->getModelInfo());
        $gridField  = $model->fieldToGridField();
        //因为Think模板引擎强制将所欲的{}认为是标签，进行解析，而在preg_**函数解析的过程中，会给所有的"加上\，则TP需要对解析出的函数执行 stripslashes，一切导致 \n变成了n，从而导致字段的js代码出错
        $this->assign("gridFields",str_replace("{","{ ",json_encode($gridField["gridFields"])));
        $this->assign("datasetFields",str_replace("{","{ ",json_encode($gridField["datasetFields"])));
        $this->assign("listFields",$model->getEditFields());        //为了在Search中直接使用字段定义生成input框
        $this->assign("InitSearchPara",$this->_searchToString());   //通过URL传递的数据过滤参数
        if(isset($_REQUEST["ignoreInitSearch"])){
            //如果设置忽略初始化查询条件，则设置原始路径为不带参数路径。
            $this->assign("ignoreInitSearch","ignore");
        }else{
            $this->assign("ignoreInitSearch","");
        }
        foreach($_REQUEST as $key=>$val){
            $this->assign($key,str_replace("%","",$val));
        }

        $this->assign('dx_data_list', DXINFO_PATH."/DxTpl/data_list.html");
        $dataListHtml = $this->dxDisplay("data_list");

        echo $this->display("Public:header");
        if($this->haveHeaderMenu){
            echo $this->display("Public:menu");
        }
        echo $dataListHtml.$this->display("Public:footer");
    }
    protected function getModelInfo(){
        $model = $this->model;
        $enablePage     = $model->getModelInfo("enablePage");
        if($_REQUEST["print"]=="1") $enablePage = false;

        //支持通过url传递过来的ModelTitle
        //因为要在新增修改界面中显示，model的标题，所以需要保存在session中，随后根据情况清楚掉。
        $modelTitle = empty($_REQUEST["modelTitle"])?$model->getModelInfo("title"):$_REQUEST["modelTitle"];
        $addTitle   = $model->getModelInfo("addTitle");
        if(empty($addTitle)) $addTitle  = "新增".$modelTitle;
        $importTitle    = $model->getModelInfo("importTitle");
        if(empty($importTitle)) $importTitle    = "导入exl文件";
        $editTitle  = $model->getModelInfo("editTitle");
        if(empty($editTitle)) $editTitle    = "修改".$modelTitle;

        return array_merge (
            $model->getModelInfo(),
            array (
                "modelTitle" => $modelTitle,
                "addTitle" => $addTitle,
                "editTitle" => $editTitle,
                "importTitle"=>$importTitle,
                "enablePage" => $enablePage,
            )
        );
    }
    public function get_model(){
        $gridField  = $this->model->fieldToGridField();
        $gridField["modelInfo"] = $this->getModelInfo();
        $gridField["pkId"] = $this->model->getPk();
        $this->ajaxReturn($gridField,"JSON");
    }

    /* 追加数据 **/
    public function add(){
        //print_r($_REQUEST);die("99");
        $model  = $this->model;
        // dump($model);die();
        if(empty($model)) die();

        //判断是否为修改数据
        $vo = array();$pkId = 0;
        if(isset($_REQUEST["id"]))
            $pkId     = intval($_REQUEST["id"]);

        //列出字段列表
        $listFields = $model->getEditFields($pkId);
        $this->assign( "listFields",$listFields);
        $this->assign ( "modelInfo", $model->getModelInfo());
        $this->assign ( "modelName", $model->getModelName());

        if($pkId>0){
            //要修改的 数据内容
            $where   = array($model->getPk()=>$pkId);
            $vo      = $model->where( $where )->getInfo($listFields);
            if($vo){
                $this->assign('pkId',$pkId);
            }else{
                $this->error('要修改的数据不存在!请确认操作是否正确!');
            }
        }else{
            $vo = $model->getListFieldDefault();
        }
        $recordDataInfo = array_merge($vo,$_REQUEST);
        $this->assign('recordDataInfo', $recordDataInfo);
        //引用于模板继承，使用变量作为模板文件
        $this->assign('dx_data_edit', DXINFO_PATH."/DxTpl/data_edit.html");

        if($pkId>0){
            $dataListHtml = $this->dxDisplay("Public:data_edit","edit");
        }else{
            $dataListHtml = $this->dxDisplay("Public:data_edit");
        }

        if(array_key_exists("haveHeader",$_REQUEST)){
            echo $this->display("Public:header");
        }
        if(array_key_exists("haveHeaderMenu",$_REQUEST)){
            echo $this->display("Public:menu");
        }
        echo $dataListHtml;
        if(array_key_exists("haveHeader",$_REQUEST)) $this->display("Public:footer");
    }

    /**
     * 数据展示页面
     * */
    public function view(){
        echo $this->dxDisplay("Public:data_view");
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
        $m          = $this->model;
        $pk         = $m -> getPk();
        $id         = $_REQUEST["id"];
        if (!empty($m) && isset($id)){
            $where  = array ($pk => array ('in', explode ( ',', $id ) ) );
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
        $model          = $this->model;
        if (! empty ( $model )) {
            $id = $_REQUEST["id"];
            if (intval ( $id )>0) {
                $pk = $model->getPk ();
                if(strpos($id, ",")) $condition = array ($pk => array ('in', explode ( ',', $id ) ) );
                else $condition = array($pk=>intval($id));
                $list           = $model->where ( $condition )->delete();
                fb::log($model->getLastSQL());
                $deleteState    = true;
            }
        }

        if($deleteState) $this->ajaxReturn(array("data"=>0,"info"=>"删除成功!","status"=>1),"JSON");
        else $this->ajaxReturn(array("data"=>0,"info"=>"删除失败!","status"=>0),"JSON");
    }

    public function __destruct(){
        parent::__destruct();
    }
}

