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
    protected $model            = null;
    protected $theModelName     = "";
    private $cacheActionList    = array();  //系统action的缓存，对应menu表
    protected $haveHeaderMenu   = true;

    function __construct() {
        parent::__construct();
        if(empty($this->model)) $this->model  = D($this->getModelName());
        else $this->theModelName    = $this->model->name;

        if($_REQUEST["haveHeaderMenu"]=="false" || C("HAVE_HEADER_MENU")==false){
            $this->assign("haveHeaderMenu",false);
            $this->haveHeaderMenu = false;
        }else{
            $this->assign("haveHeaderMenu",true);
            $this->haveHeaderMenu = true;
        }
    }

    function _initialize() {
        $url    =   C("LOGIN_URL");
        if($url[0]!="/" || substr($url,0,4)!="http") C("LOGIN_URL",U($url));

        fb::log($_REQUEST);
        $this->cacheActionList  = DxFunction::getModuleActionForMe();

        if(!in_array(ACTION_NAME,array("get_datalist")))
            $log_id =   $this->writeActionLog();

        if(C("DISABLE_ACTION_AUTH_CHECK")!==true){
            if (!DxFunction::checkNotAuth(C('NOT_AUTH_ACTION'),C('REQUIST_AUTH_ACTION'))){
                //为了不验证公共方法，比如：public、web等，所以将session验证放在里面。
                if(0 == intval(session(C("USER_AUTH_KEY")))) {
                    redirect(C("LOGIN_URL"),0,"");
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

        //将系统变量加载到config中，供系统使用。
        D("SysSetting")->cacheData();
    }

    /**
     * 保存add和edit页面提交的数据
     * 系统固定使用pkId作为主键进行数据传递的原因：因为js的数据验证插件validate无法配置动态获取到不同名称的值，只能固定为pkId
     */
    public function insertOrUpdate($m){
        $pkId = "pkId";
        //强制，将设置为readOnly的字段注销掉，防止自己构造post参数。。比如：入院时间是不允许修改的，但是用户可以自己构造post数据，提交入院时间字段，则tp的create会更新这个字段。
        //目前的Readonly只支持edit的时候，add的时候，不支持。
        foreach($m->getListFields() as $key=>$val){
            $fieldName = $val["name"];
            if(!empty($_REQUEST[$pkId]) && (isset($val["readOnly"]) && $val["readOnly"])){
                unset($_GET[$fieldName]);
                unset($_POST[$fieldName]);
                unset($_REQUEST[$fieldName]);
            }else if($val["type"]=="uploadFile" && (is_array($_REQUEST[$fieldName]) || !empty($_REQUEST["old_".$fieldName]))){
                //如果数据传递过来的是数组，则进行数据整合为json格式，比如：多文件上传.
                $_REQUEST[$fieldName] = $_POST[$fieldName] = $_GET[$fieldName] = $this->moveAndDelFile($fieldName,$m->getModelName());
            }else if($val["type"]=="set" && is_array($_REQUEST[$fieldName])){
                //如果字段是set和 mul select。则将数据整合为json
                //注意：如果存储为json，则无法对此字段进行数据检索
                if($val["valFarmat"]=="json")
                    $_REQUEST[$fieldName] = $_POST[$fieldName]  = $_GET[$fieldName]   = json_encode($_REQUEST[$fieldName]);
                else
                    $_REQUEST[$fieldName] = $_POST[$fieldName]  = $_GET[$fieldName]   = "0,".implode(",",$_REQUEST[$fieldName]).",0";
            }elseif ($val['type']   == 'cutPhoto'){
                if(!empty($_REQUEST[$fieldName])){
                    if(!empty($_REQUEST["old_$fieldName"])) unlink(C("UPLOAD_BASE_PATH").$_REQUEST["old_$fieldName"]);
                    $_REQUEST[$fieldName] = $_POST[$fieldName] = $_GET[$fieldName] = DxFunction::move_file(C("TEMP_FILE_PATH").'/'.$_REQUEST[$fieldName],MODULE_NAME);
                }else{
                    unset($_GET[$val["name"]]);
                    unset($_POST[$val["name"]]);
                    unset($_REQUEST[$val["name"]]);
                }
            }
        }

        if(!empty($_REQUEST[$pkId])){
            $_POST[$m->getPk()] = $_REQUEST[$pkId];
            $_REQUEST[$m->getPk()] = $_REQUEST[$pkId];
        }
        if(!empty($m) && $m->create()){
            $v = false;
            //fb::log($_REQUEST,$m);
            if(intval($_REQUEST[$pkId])>0){
                $v = $m->save();
                if($v!==false) $v = $_REQUEST[$pkId];
            }else{
                $v = $m->add(); //如果添加成功返回的就是pkId
            }
            fb::log($m->getLastSQL(),$v);
            return $v;
        }else{
            fb::log($m->getError());
            return "create_err";
        }
    }
    /**
     * 将新上传的文件移动到实际目录中，并将旧的无效的文件删除
     * @param       $key        字段名
     * @param       $modelName  model名称作为存放文件的目录名
     * @param       $returnJson 是否返回的数据格式化为json格式
     * 注意：
     * Linux下，ls /home/a/../c/p.php  可以用，但是cp /home/a/../c/p.php /tmp/则会提示p.php文件不存在，所以需要将路径中..移除掉。
     * 原设计：为了将文件存储路径 (./ORGA/Runtime) 和 图片显示的Url(http://xxx/Uploads/../ORGA/Runtime)统一处理，所以数据库存储路径中包含 ../  
     * */
    protected function moveAndDelFile($key,$modelName,$returnJson=true){
        $value  = array();
        foreach($_REQUEST[$key] as $one){
            $value[]    = json_decode($one,true);
        }
        //1.新 旧 数据中都存在的保留，2 新文件不存在，旧文件存在的，删除 3.新新文件存在，旧文件不存在的，移动到实际目录
        $old_val    = json_decode($_REQUEST["old_".$key],true);
        if(sizeof($old_val)>0){
            foreach($old_val as $ov_key=>$v){
                $cunzai = false;
                foreach($value as $nv_key=>$nv){
                    if($nv["url"]==$v["url"]){
                        //"real_name":"1411270.png","name":"13565930481411270.png","file_path":"20121227\/13565930481411270.png","size":109886,"type":"image\/png","url":".\/ORGA\/Runtime\/TMMP_IMG\/20121227\/13565930481411270.png","thumbnail_url":".\/ORGA\/Runtime\/TMMP_IMG\/thumbnail\/20121227\/13565930481411270.png","delete_url":"http:\/\/job\/yanglaoyuan2\/?file=13565930481411270.png","delete_type":"DELETE"
                        $cunzai = true;
                        $value[$nv_key]["cunzai"]   = true;
                        break;
                    }
                }
                $old_val[$ov_key]["cunzai"] = $cunzai;
                if($cunzai===false){
                    unlink(C("UPLOAD_BASE_PATH").dirname($old_val[$ov_key]["url"])."/".$old_val[$ov_key]["name"]);
                    if(!empty($old_val[$ov_key]["thumbnail_url"])){
                        unlink(C("UPLOAD_BASE_PATH").dirname($old_val[$ov_key]["thumbnail_url"])."/thumbnail_".$old_val[$ov_key]["name"]);
                    }
                }
            }
        }

        foreach($value as $tkey=>$tval){
            if($tval["cunzai"]!==tre){
                $value[$tkey]["url"]    = DxFunction::move_file(C("TEMP_FILE_PATH").dirname($tval["url"])."/".$tval["name"],"/".$modelName,"dateY_m");
                if(!empty($tval["thumbnail_url"])){
                    $value[$tkey]["thumbnail_url"]  = DxFunction::move_file(C("TEMP_FILE_PATH").dirname($tval["thumbnail_url"])."/".$tval["name"],"/".$modelName,"dateY_m","thumbnail_".$tval["name"]);
                }
            }
        }
        if($returnJson)
            return json_encode($value);
        else
            return $value;
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
        $num = 0;//导入总的记录数
        $successNum = 0;//导入成功的记录数
        $errorNum = 0;//导入失败的记录数    
        if (! empty ( $_FILES ['file_stu'] ['name'] )){
        $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
        $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
        $file_type = $file_types [count ( $file_types ) - 1];
        $filename=$_FILES['file_stu']['name'];
        if (strtolower ( $file_type ) != "xls"){
           $this->error ( '不是Excel文件，重新上传！' );
        }
        $savePath = C("TEMP_FILE_PATH");
        //如果零时路径不存在则创建
        if(!is_dir($savePath))
        mkdir($savePath,0777);
        if(!move_uploaded_file($tmp_file,$savePath.'/'.$filename))
        $this->error ( '上传文件失败！' );
        $objReader = PHPExcel_IOFactory::createReader( 'Excel5' ); 
        $objPHPExcel = $objReader->load($savePath.'/'.$filename);
        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
        $highestColumn = $objPHPExcel->getActiveSheet()->getHighestColumn();
        //将表格的A、B、C...列名装换成1、2、3....
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $model=$this->model;
        $importfields = $model->getImportFields();//model中可以导入的字段
        for($col = 0; $col < $highestColumnIndex; $col++){
           $flag = false;
          //excel标题字段
           $excelFields[$col] = (string)$objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col, 1)->getValue();
           foreach ($importfields as $i){
              if($excelFields[$col] == $i[title]){
                //excel标题对应的数据库中字段的名字
                $excelsqlFields[$col] = $i[name];
                //判断excel标题字段和model中可导入的字段是否相互对应    
                $flag = true;
                }
            }
            if($flag == false)
            $this->error ( $excelFields[$col].'字段无法导入！' );//表格中的字段和model中的字段不对应，不进行导入
        }
        for($row = 2; $row <= $highestRow; $row++){
           //获取excel每一行的数据，除去标题行
           for($col = 0; $col < $highestColumnIndex; $col++){
              $excelData[$row][$col] = (string)$objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col, $row)->getFormattedValue();
              }
              foreach ($excelsqlFields as $k=>$v){
                //valChange数据的转换
                 foreach ($importfields as $m){
                       if($m[valChange]!=NULL)
                          foreach($m[valChange] as $i=>$j){
                            if(is_array($j))
                                foreach($j as $m=>$n){
                                  if($excelData[$row][$k]== $n)
                                    $data[$row][$v]=$m;
                                }
                            if($excelData[$row][$k]== $j)
                            $data[$row][$v]=$i; 
                          }
                   }
                   //判断数据库中是否已有该字段的信息，如有的话不能重复导入 
                   if($v==$model->getModelInfo("notDuplicate")){
                   if($model->where('%s="%s"',$v,$excelData[$row][$k])->find())
                   $errorcontent[$row] = '数据库中已有'.$excelData[$row][$k]."的信息";//错误信息的内容
                   }
                   if($data[$row][$v]==NULL)    
                   $data[$row][$v]=$excelData[$row][$k];
                   }
                   //对数据库中没有的记录进行导入
                   if($errorcontent[$row]==NULL){
                     if($model->create($data[$row])){
                        if($model->add()){
                           $error[$row]=true;//判断数据是否导入成功
                           
                        }
                     }else{ $errorcontent[$row] = $model->getError();}//数据导入时返回的错误信息
                   }
                   $num++;
        }
        //重新组装文件，在文件开头增加导入结果列
        $objPHPExcel->getActiveSheet()->insertNewColumnBefore('A',1);
        for($row = 2; $row <= $highestRow; $row++){
           if($error[$row]==true){
             $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,'导入成功'); 
             $successNum++; 
           }
           else{ 
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$row,'导入失败'.$errorcontent[$row]);
            $errorNum++;
            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
        $objWriter->save ( $savePath.'/'.$filename );
      }
      $this->success ( '导入数据完成！总共导入'.$num.'条记录，成功'.$successNum.'条，失败'.$errorNum.'条' );
    }

    /**
     * (判断当前用户是否有这种动作的权限)
     * @param    (字符串)     (action_name)    (动作)
     */
    public function check_action_privilege($module_name = '',$action_name = '') {
        $cacheAction    = $this->cacheActionList;
        if(empty($cacheAction)) return false;   //不通过

        $thisModule = empty($module_name)?MODULE_NAME:$module_name;
        $thisAction = empty($action_name)?ACTION_NAME:$action_name;
        //dump($thisModule);dump($thisAction);
        //dump($cacheAction["myAction"]);dump($cacheAction["allAction"][$thisModule][$thisAction]);
        if(empty($cacheAction["myAction"][$thisModule][$thisAction])){
            if(empty($cacheAction["allAction"][$thisModule][$thisAction])){
                return true;    //未定义的Action，默认都有权限操作
            }else{
                return false;
            }
        }else
            return true;
    }

    protected function getModelName() {
        if(empty($this->theModelName)) {
            $this->theModelName = parent::getActionName();
        }
        return $this->theModelName;
    }

    /**
     * 将用户操作写入日志表中
     * 初始操作，无论是否权限验证是否通过，都存储，再权限验证后，更新操作的验证信息。
     */
    public function writeActionLog($moduleName="",$actionName=""){
        $model = D('OperationLog');
        $model->ip          = get_client_ip()."_".$_SERVER["REMOTE_ADDR"];
        $model->action      = empty($actionName)?ACTION_NAME:$actionName;
        $model->module      = empty($moduleName)?MODULE_NAME:$moduleName;
        $action_name        = $this->cacheActionList["allAction"][$model->module][$model->action];

        //更新菜单的点击次数
        D("Menu")->updateClickTimes(array("module_name"=>$model->module,"action_name"=>$model->action));

        if(sizeof($action_name)>1){
            $action_name    = DxFunction::argsInRequest($action_name,$_REQUEST);
        }else{
            $action_name    = array_values($action_name);
            $action_name    = $action_name[0]["menu_name"];
        }
        if(empty($action_name) || is_array($action_name)) $model->action_name   = "";
        else $model->action_name    = $action_name;
        $model->account_name        = $_SESSION[C("LOGIN_USER_NICK_NAME")]==null?"":$_SESSION[C("LOGIN_USER_NICK_NAME")];;
        $model->account_id          = $_SESSION[C("USER_AUTH_KEY")]==null?"":$_SESSION[C("USER_AUTH_KEY")];
        $model->over_pri            = 0;
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
    public function createTable(){
        $this->model->fnCreateTable();
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
        //有些model显示的内容是多表关联，所以不能使用getDbFields
        $dbFields   = $model->getListFields();
        foreach($_REQUEST as $key=>$val){
            if (strlen($val)<1 && empty($val)) continue;
            $fieldAdd   = "";
            if( substr($key,0,4)=="egt_" ){
                $key        = substr($key,4);
                $fieldAdd   = "egt";
            }else if( substr($key,0,4)=="elt_" ){
                $key        = substr($key,4);
                $fieldAdd   = "elt";
            }else if( substr($key,0,3)=="gt_" ){
                $key        = substr($key,3);
                $fieldAdd   = "gt";
            }else if( substr($key,0,3)=="lt_" ){
                $key        = substr($key,3);
                $fieldAdd   = "lt";
            }else if( strpos($key,"%")!==false ){
                if($key[0] == '%'){
                    $val = '%'.$val;
                    $key = substr($key,1);
                }
                if($key[strlen($key)-1] == '%'){
                    $val = $val.'%';
                    $key = substr($key,0,-1);
                }
                $fieldAdd   = "like";
            }

            if (array_key_exists($key,$dbFields)) {
                if($dbFields[$key]["type"]=="date"){
                    $MAX_DATE = '3999-12-31 23:59:59';
                    $MIN_DATE = '1908-01-01 00:00:00';
                    if($fieldAdd == "elt" || $fieldAdd == "lt") $val = $val.substr($MAX_DATE,strlen($val));
                    if($fieldAdd == "egt" || $fieldAdd == "gt") $val = $val.substr($MIN_DATE,strlen($val));
                }
                if($fieldAdd == "egt" || $fieldAdd=="elt" || $fieldAdd == "gt" || $fieldAdd=="lt"){
                    if(array_key_exists($key, $map)){
                        $map[$key] = array($map[$key],array($fieldAdd,$val),"and");
                    }else{
                        $map[$key] = array($fieldAdd,$val);
                    }
                }else if(strtolower(trim($val))=="null"){
                    $map[$key] = array("exp","is null");
                }else if($fieldAdd == "like"){
                    $map[$key] = array("like",$val);
                }else if(is_array($val)){
                    if($dbFields[$key]["type"]=="set"){
                        $tempV = array();
                        foreach($val as $vvv){
                            $tempV[] = $key." LIKE '%,".$vvv.",%'";
                        }
                        if(empty($where['_string']))
                            $map['_string'] = implode(" OR ",$tempV);
                        else
                            $map['_string'] .= " AND ".implode(" OR ",$tempV);
                    }else{
                        $map[$key] = array("in",implode(",",$val));
                    }
                }else{
                    $map[$key] = $val;
                }
            }
        }
        if(APP_DEBUG){
            fb::log($map,"_search");
        }
        return $map;
    }

    protected function _searchToString(){
        $s      = array();
        foreach($_REQUEST as $key=>$val){
            $s[]    = $key."=".urldecode($val);
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
