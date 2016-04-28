<?php
/**
 * Version：2.0
 * 目的：构建基础的Model模块，提供公共功能代码
 * 功能：
 * 查看Doc/Readme.md文件
 * */
class DxExtCommonModel extends Model {

    const HIDE_FIELD_LIST       = 01;       //列表时是否隐藏列
    const HIDE_FIELD_ADD        = 02;       //增加数据时是否隐藏
    const HIDE_FIELD_EDIT       = 04;       //修改数据时是否隐藏
    const HIDE_FIELD_DATA       = 010;      //列表时，是否不输出此字段数据
    const HIDE_FIELD_EXPORT     = 020;      //是否不导出
    const HIDE_FIELD_PRINT      = 040;      //是否不打印
    const HIDE_FIELD_IMPORT     = 0100;     //是否导入字段

    const DP_TYPE_ENABLE        = 01;   //开启数据权限控制
    const DP_TYPE_PUBLIC        = 02;   //开启公共数据权限，此字段判定，某个数据是否是公共数据。
    const DP_TYPE_STATIC_AUTO   = 04;   //auto的静态设定

    const FULLTEXT_MSG_NOT      = 0;   //不是消息
    const FULLTEXT_MSG_NEW      = 1;   //未处理的消息
    const FULLTEXT_MSG_OLD      = 2;   //已处理的消息

    protected $listFields   = array();  //模型字段的附加信息
    protected $modelInfo    = array();  //模型的附加信息。
    private $cacheDictDatas = array();  //缓存的字典表数据
    private $cacheListFields= array();  //缓存Model的listFileds数据，经过转换的结果
    //数据权限相关
    public $skipDataPowerCheck  = false;    //关闭数据权限域控制。
    protected $viewTableName    = "";      //可以是table，也可以是sql语句
    protected $defaultWhere     = array();
    private $viewTableIsSelect  = false;
    protected $DP_POWER_FIELDS  = array();

    public $noDeleteSearchWhere = array();
    protected $fullTextState    = 0;        //Model追加的fulltext数据的状态,一般在model的_before_insert  _before_update中改变此属性
    /* 将所有的数据库字段，全初始化为数据列表字段，默认使用数据库字段名 */
    function initListFields(){
        if(sizeof($this->listFields) > 0){
            return;
        }
        $dbFields   = $this->getDbFields();
        $listF      = array();
        //dump($dbFields);die();
        foreach($dbFields as $key => $val){
            //0和字符串比较为真 所以用全等判断 不然索引为0的非主键字段也添加不到listFields当中
            if($key!=="_autoinc" && $key!=="_pk"){
                $listF[$val] = array("title"=>$val);
            }
        }
        $this->listFields   = $listF;
    }

    function __construct($name='',$tablePrefix='',$connection='') {
        parent::__construct($name,$tablePrefix,$connection);

        //modelInfo的初试值
        $modelInfoInit = array(
            "customRowAttribute"=>0,
            "onComplete"=>0,
            "enablePage"=>1,
            "max_export"=>5000,
            "addDialogButtons"=>"[]"
        );
        $this->modelInfo = array_merge($modelInfoInit,$this->modelInfo);

        //根据model配置，自动生成数据验证条件
        if(sizeof($this->listFields) > 0){
            foreach($this->listFields as $name => $field){
                switch($field["type"]){
                case "idcard":
                    $year = substr(date("Y"),-2,1);
                    $idcardValid = array($name,"/^[0-9]{6}([0-9]{2}|1[89][0-9]{2}|2[01][0-".$year."][0-9])(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[01])[0-9]{2,3}[0-9Xx]$/","身份证格式不正确",Model::VALUE_VALIDATE,"regex");
                    $this->_validate = array_merge($this->_validate,array($idcardValid));
                    break;
                }
            }
        }

        $this->initListFields();
        //自动填充默认字段create_time 和 update
        $myFields   = $this->getDbFields();
        if(in_array("create_time", $myFields,true)){
            $this->_auto = array_merge($this->_auto,array(array('create_time','DxFunction::getMySqlNow',self::MODEL_INSERT,'function')));
        }
        if(in_array("update_time", $myFields,true)){
            $this->_auto = array_merge($this->_auto,array(array('update_time','DxFunction::getMySqlNow',self::MODEL_BOTH,'function')));
        }

        // 如果这个 Model 有创建人，创建部门，创建人所属区域，最后修改人 等字段时，
        // 自动追加这些属性到 __auto 中,用Session对应的值，进行填充。
        // 将这些数据打标签为 隶属于某人、某部门 。数据域权限控制时要用这些标签。
        // dump($this->getDbFields());die();
        $dbFields       = $this->getDbFields();
        $tDpFields      = C('DP_POWER_FIELDS');
        $tDpFields      = array_merge($tDpFields,$this->DP_POWER_FIELDS);
        if(isset($tDpFields) && is_array($tDpFields)){
            foreach($tDpFields as $dp_fields){
                if(intval($dp_fields["auto_type"])<1) continue; //不自动填充数据
                $field_name = $dp_fields["field_name"];
                $session_field_name = array_key_exists("session_field", $dp_fields)?$dp_fields["session_field"]:$field_name;

                if(array_search($field_name,$dbFields)){
                    if(intval($dp_fields["type"])==self::DP_TYPE_STATIC_AUTO){
                        //设置默认值
                        $this->_auto    = array_merge($this->_auto,
                                array(array($field_name,$session_field_name,$dp_fields["auto_type"]))
                        );
                    }else if(isset($_SESSION[$session_field_name])){
                        $this->_auto    = array_merge($this->_auto,
                                array(array($field_name,$_SESSION[$session_field_name],$dp_fields["auto_type"]))
                        );
                    }
                }
            }
        }
        //Log::write(var_export($this->_auto,true).MODULE_NAME."|".ACTION_NAME,Log::INFO);
    }

    /**
     * 重新整理listFields数据，将原始的listFields转换为运行时状态。比如：valChange的转换。
     */
    public function getListFields($onlyCache=false,$original=false){
        if($original) return $this->listFields;

        $fieldsMd5 = $this->getListFieldsMd5();
        if(empty($this->cacheListFields[$fieldsMd5]) || APP_DEBUG){
            $listFs = $this->getCacheListFields($fieldsMd5);
        }else{
            $listFs = $this->cacheListFields[$fieldsMd5];
        }
        
        if($onlyCache){
            fb::log($listFs,$this->name."->getListFieldsCache");
            return $listFs;
        }else{
            //valCahnge数据。
            $rvFields = array();
            foreach($listFs as $key=>$field){
                if($field["valChangeReadOnly"]!=true){
                    $rvFields[$key] = $this->getFieldValChange($field);
                }
            }
            fb::log($rvFields,$this->name."->getListFields");
            return $rvFields;
        }
    }
    //获取字段的默认值，在新增数据时，需要
    public function getListFieldDefault(){
        $default = array();
        foreach($this->getListFields() as $name=>$field){
            if(array_key_exists("default",$field)){
                $d  = $field['default'];
                if(is_array($d)){
                    switch($d["type"]){
                    case "func":
                        $func = $d["value"];
                        if(function_exists($func)){
                            $default[$name] = call_user_func_array($func, $d["other_info"]);
                        }
                        break;
                    case "class":
                        if(class_exists($d["value"]["className"])){
                            if(method_exists($d["value"]["className"],$d["value"]["methodName"])){
                                $classObj = new $d["value"]["className"];
                                $default[$name] = call_user_func_array(array($classObj,$d["value"]["methodName"]), $d["other_info"]);
                            }
                        }
                        break;
                    case "session":
                        $default[$name] = $_SESSION[$d["value"]];
                        break;
                    }
                }else{
                    $default[$name] = $field["default"];
                }

                switch($field["type"]){
                    case "date":
                        if($field["default"]=="now"){
                            //date("Y-m-d H:i:s");
                            $default[$name] = date(str_replace(array("yyyy","MM","dd","HH","mm","ss"),array("Y","m","d","H","i","s"),$field["valFormat"]));
                        }
                    break;
                    case "canton":
                        if(empty($default[$name])) $default[$name] = C("ROOT_CANTON_FDN");
                    break;
                }
            }
            //将valChange转换为字符数据。
            if(array_key_exists("valChange", $field) && !empty($default[$name])){
                $default[$name."_textTo"] = $field["valChange"][$default[$name]];
            }
        }
        return $default;
    }

    // 将数据列表Grid要显示的字段，整合为一个字符串，作为SELECT 语句的字段列表
    public function getListFieldString($table_alias="") {
        return $this->getFieldsString ( self::HIDE_FIELD_DATA,$table_alias );
    }
    // 将要打印的字段，整合为一个字符串，作为SELECT 语句的字段列表
    public function getPrintFieldString($table_alias="") {
        return $this->getFieldsString ( self::HIDE_FIELD_PRINT,$table_alias );
    }
    private function getFieldsString($hideState,$table_alias="") {
        $r = array ();
        foreach ( $this->getNoHideFields ( $hideState,true ) as $key => $val ) {
            if (isset ( $val ["field"] ))
                $r [] = $table_alias.$val ["field"];
            else if (isset ( $val ["name"] ))
                $r [] = $table_alias.$val ["name"];
            else
                $r [] = $table_alias.$key;
        }
        return implode ( ",", $r );
    }
    public function getListFieldsMd5(){
        return md5(json_encode($this->listFields));
    }
    public function getModelInfoMd5(){
        return md5(json_encode($this->modelInfo));
    }
    private function getCacheListFields($fieldsMd5){
        $cacheFile = 'dict_cache_'.$this->name."_listFields_".$fieldsMd5;
        $info = S($cacheFile);
        if(!empty($info) && !APP_DEBUG) return $info;

        $tListFields   = array();
        foreach($this->listFields as $key=>$field){
            $tListFields[isset($field["name"])?$field["name"]:$key] = $this->getOneListField($key,$field);
        }
        //转换Model的自动验证规则为formValidation形式
        $tempValid  = $this->convertValid($this->_validate);
        // dump($this->_validate);dump($tempValid);
        foreach($tempValid as $fld =>$vvvv){
            $tListFields[$fld]["valid"][self::MODEL_INSERT] = "validate[".implode(",",$vvvv[self::MODEL_INSERT])."]";
            $tListFields[$fld]["valid"][self::MODEL_UPDATE] = "validate[".implode(",",$vvvv[self::MODEL_UPDATE])."]";
            $tListFields[$fld]["valid"]["validateMsg"] = $vvvv["validateMsg"];
            $tListFields[$fld]["valid"]["regex"] = $vvvv["regex"];
        }

        S($cacheFile,$tListFields);
        $this->cacheListFields[$fieldsMd5]  = $tListFields;
        return $tListFields;
    }

    private function getOneListField($key,$field){
        if(!isset($field["name"])) $field["name"]   = $key;
        switch($field["type"]){
            case "canton":
                if(empty($field["width"])) $field["width"] = "180";
                if(!empty($field['textTo']) && empty($field["texttoattr"])) $field["texttoattr"] = "full_name";
                $field["valChange"] = "";   
                //因为canton的valchange内容太多，所以放到页面头部直接载入，每个field中不再体现。一个表中可能有多个canton字段。
                // canton字段统一使用textTo属性进行字段值保存，避免展示的时候，进行数据转换。
                // if(!($field["hide"] & self::HIDE_FIELD_DATA)){
                //     $data_change    = $this->getModelInfo("data_change");
                //     $data_change[$field["name"]] = "cantonFdnToText";
                //     $this->setModelInfo("data_change",$data_change);
                // }
                break;
            case "selectselectselect":
                if(!empty($field['textTo']) && empty($field["texttoattr"])) $field["texttoattr"] = "full_name";
                $field["width"] = "180";
                break;
            case "date":
                if(empty($field["valFormat"])) $field["valFormat"] = "yyyy-MM-dd";
                if(empty($field["width"])) $field["width"] = strlen($field["valFormat"])*6+10;
                $start = strpos("yyyy-MM-dd HH:mm:ss",$field["valFormat"]);
                if(empty($field["renderer"]) && $start!==false){
                    $field["renderer"] = sprintf("var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){if(value==null){return '';}else if(value.replace(/[:0\- ]*/,'','gi')=='') return '';return value.substr(%d,%d);}",$start,strlen($field["valFormat"]));
                }
            case "idcard":
                if(empty($field["idcard"])) $field["idcard"] = "'birthday':'birthday','sex':'sex','id_reg_addr':'id_reg_addr'";
                if(empty($field["width"])) $field["width"] = 140;
                break;
            case "tel":
                if(empty($field["width"])) $field["width"] = 100;
                break;
        }
        if(intval($field["width"])<1) $field["width"] = "80";
        if(intval($field["export_width"])<1) $field["export_width"] = $field["width"];
        if(intval($field["tdCols"])<1) $field["tdCols"] = 1;

        //规整数据的enum字段，默认使用valChange替换，没有valChange字段，则从数据库获取enum的字段定义数据
        if(empty($field["valChange"]) && array_key_exists("type", $field) && ($field["type"]=="enum" || $field["type"]=="set" || $field["type"]=="select")){
            $sql    = sprintf("SELECT COLUMN_TYPE FROM information_schema.`COLUMNS` WHERE DATA_TYPE in ('set','enum') AND `TABLE_SCHEMA`='%s' AND `TABLE_NAME`='%s' AND COLUMN_NAME='%s'",C("DB_NAME"),$this->trueTableName,$field["name"]);
            $tField = $this->query($sql);
            if(!empty($tField)){
                //枚举类型的内部序号是从1开始。
                $field["valChange"] = explode(",","0,".str_replace(array("'","(",")"),array("","",""), substr($tField[0]["COLUMN_TYPE"],5)));
                unset($field["valChange"][0]);
                $field["valChange"]    = array_combine($field["valChange"],$field["valChange"]);
            }else $field["valChange"] = array();
            $field["valChangeReadOnly"] = false;
        }
        if($field["valChangeReadOnly"]){
            $field = $this->getFieldValChange($field);
        }
        return $field;
    }
    //valChange分为固定和不固定的，固定的可以直接缓存到cache中，不固定的要每次获取（比如：对应到字典表的能够人工定义的数据）
    private function getFieldValChange($field){
        if($field["type"]=="selectselectselect"){
            if(isset($field["valChange"]["model"])){
                if($this->name==$field["valChange"]["model"]){
                    $m    = $this;
                }else{
                    $m    = D($field["valChange"]["model"]);
                }

                $allDataVal = $m->getSelectSelectSelect();
                $fdnTreeData = array();
                foreach($allDataVal as $d){
                    if(!is_array($fdnTreeData[$d["parent_id"]])) $fdnTreeData[$d["parent_id"]] = array();
                    array_push($fdnTreeData[$d["parent_id"]],$d);
                }
                $field["fdnChange"] = $fdnTreeData;
            }
        }

        if(is_array($field["valChange"])){
            //将字典表，转换为valChange数据
            if(isset($field["valChange"]["model"])){
                if($this->name==$field["valChange"]["model"]){
                    $m    = $this;
                }else{
                    $m    = D($field["valChange"]["model"]);
                }
                $tValC  = $m->getCacheDictTableData();
            }else if(array_key_exists("sql",$field["valChange"])){
                //使用SQL获得valChange映射
                $tValC  = $this->query($field["valChange"]["sql"]);
                if($tValC){
                    $tValC = DxFunction::arrayToArray($tValC);//原始值是$field["valChange"]
                }
            }else{
                $tValC = $field["valChange"];
            }
            //找到数组的某一部分作为valChange转换，比如：大型数据字典 sysDic
            if(!empty($field["valChange"]["type"])){
                $tType  = explode(",",$field["valChange"]["type"]);
                foreach($tType as $vvv)
                    $tValC  = $tValC[$vvv];
            }
            if(is_array($tValC)) $field["valChange"] = $tValC;
            else $field["valChange"] = array();
            fb::log($field,$this->name."-fieldsValChange");
        }
        return $field;
    }
    // 通过key改变model的listFields属性
    public function setListField($key, $v, $start=0) {
        $field = array();
        if(empty($this->listFields [$key]))
            $field = array($key=>$v);
        else
            $field = array($key=>array_merge ( $this->listFields [$key], $v ));
        if($start==0){
            $this->listFields[$key] = $field[$key];
        }else{
            $field[$key]["name"] = $key;
            array_splice($this->listFields,intval($start),0,$field);
        }
    }
    //新增一个字段
    public function addListField($field) {
        if(is_array($field)) $this->listFields = array_merge ( $this->listFields, $field );
    }

    public function getEditFields($pkId=0){
        //编辑数据的字段列表，编辑数据时，要隐藏某些字段
        $f  = array();
        foreach($this->getListFields() as $name=>$field){
            //修改和新增时隐藏可以分别定义      新增和修改时的readOnly可以分别定义
            if(!($field["hide"] & self::HIDE_FIELD_ADD) && $pkId==0){
                $f[$field["name"]]  = $field;
                $f[$field["name"]]["readOnly"]      = (bool)($field["readOnly"] & self::HIDE_FIELD_ADD);
                $f[$field["name"]]["display_none"]  = (bool)($field["display_none"] & self::HIDE_FIELD_ADD);
            }else if(!($field["hide"] & self::HIDE_FIELD_EDIT) && $pkId>0){
                $f[$field["name"]]  = $field;
                $f[$field["name"]]["readOnly"]      = (bool)($field["readOnly"] & self::HIDE_FIELD_EDIT);
                $f[$field["name"]]["display_none"]  = (bool)($field["display_none"] & self::HIDE_FIELD_EDIT);
            }
        }
        return $f;
    }

    public function getModelInfo($key=""){
        if(empty($key)){
            return $this->modelInfo;
        }
        $val  = $this->modelInfo[$key];
        if($key=="order" && empty($val)) $val   = $this->getPk()." desc";
        return $val;
    }
    public function setModelInfo($key,$val){
        $this->modelInfo[$key] = $val;
    }

    public function setLeftMenu($parentId){
        //左边菜单的父ID,此菜单属于那个id的子菜单
        $this->modelInfo["leftArea"] = "{:W('Menu',array('type'=>\$type,'parent_id'=>".$parentId."))}";
    }

    /**
     * 获取需要到处的字段列表。
     */
    public function getExportFields(){
        return $this->getNoHideFields(self::HIDE_FIELD_EXPORT);
    }
    public function getPrintFields(){
        return $this->getNoHideFields(self::HIDE_FIELD_PRINT);
    }
    public function getImportFields(){
        return $this->getNoHideFields(self::HIDE_FIELD_IMPORT);
    }
    /**
     * 获取某个类型未隐藏的字段列表。
     */
    private function getNoHideFields($hideTag,$cacheFields=false){
        //编辑数据的字段列表，编辑数据时，要隐藏某些字段
        $f  = array();
        $frozen=array();
        foreach($this->getListFields($cacheFields) as $key=>$field){
            //默认导出所有的列
            if(!($field["hide"] & $hideTag)){
                $fieldName  = empty($field['name'])?$key:$field['name'];
                if(isset($field['frozen']) && $field['frozen']){
                    $frozen[$fieldName]=$field;
                }else{
                    $f[$fieldName]  = $field;
                }
            }
        }
        //用于保证frozen的列出现在列表的前方.
        return array_merge($frozen, $f);
    }

    /**
     * 将Model信息转换为前端grid的字段信息（sigma grid）
     */
    public function fieldToGridField(){
        $gridFields     = array();
        if($this->getModelInfo("hasCheckBox")){
            $gridFields[0]  = array("id"=>"chk[]","isCheckColumn"=>true,"header"=>"全选");
        }
        $datasetFields  = array();
        $lFields        = $this->getListFields();
        foreach($lFields as $fieldNameKey   => $field){
            $fieldName  = empty($field["name"])?$fieldNameKey:$field["name"];
            if(!($field["hide"] & self::HIDE_FIELD_LIST)){
                $gridHeader = empty($field["danwei"])?$field["title"]:$field["title"]."(".$field["danwei"].")";
                $gridField = array (
                    "id" => $fieldName,
                    "header" => $gridHeader,
                    "frozen" => ( bool ) ($field ["frozen"]),
                    "grouped" => ( bool ) ($field ["grouped"]),
                    "isCheckColumn"=>(bool)($field["isCheckColumn"]),
                    "width" => $field ["width"]
                );
                if(!empty($field["renderer"])){
                    $gridField["renderer"]  = $field["renderer"];
                }else{
                    $valueToJson = "";
                    if($field["type"]=="uploadFile"){
                        $url = sprintf("<a href='%s/Basic/download?f=%s&n=%s' download='%s' target='download'>%s</a>",__ROOT__,urlencode($file["url"]),urlencode($file["real_name"]),htmlentities($file["real_name"],ENT_QUOTES,'UTF-8'),htmlentities($file["real_name"],ENT_QUOTES,'UTF-8'));
                        $valueToJson    = "if(value[0]=='['){value = eval(value);var r='';$(value).each(function(i,v){r+='<a href=\"' + APP_URL + '/Basic/download?f=' + v['url'] + '&n=' + v['real_name'] + '\">' + v['real_name']+'</a><br />';});return r;}else{return value;}";
                    }else if(is_array($field["valChange"])){
                        //set 存储的数据是json数据；
                        if($field["type"]=="set"){
                            if($field["valFormat"]=="json"){
                                $valueToJson    = "if(value[0]=='['){value = eval(value);var r='';$(value).each(function(i,v){r+=valChangeDatas[v]+' ';});return r;}else{return value;}";
                            }else{
                                $valueToJson    = "if(value=='') return '';var value = value.split(',');var r='';$(value).each(function(i,v){if(valChangeDatas[v]!=undefined) r+=valChangeDatas[v]+' ';});return r;";
                            }
                        }
                        else $valueToJson   = "return valChangeDatas[value];";
                    }
                    if(!empty($valueToJson)) $gridField["renderer"] = sprintf("var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){ var valChangeDatas=%s;%s}",json_encode($field["valChange"]),$valueToJson);
                }
                //数据转换
                if(isset($field["renderer"])) $gridField["renderer"]    = $field["renderer"];

                $gridFields[]       = $gridField;
            }
            if(!($field["hide"] & self::HIDE_FIELD_DATA)){
                $datasetField       = array("name"=>$fieldName,"type"=>empty($field["type"])?"string":$field["type"]);
                $datasetFields[]    = $datasetField;
            }
        }
        // fb::log($datasetFields);fb::log($gridFields);
        return array("gridFields"=>$gridFields,"datasetFields"=>$datasetFields);
    }

    /**
     * 使用相套sql语句，代替视图
     * 1.请勿将where条件写在  函数的参数中，请使用where进行where参数传递
     * 2.Model在select后，进行update或者delete操作，需要恢复table值，否则操作失败....查询完成后自动清空了option所以，无需恢复。
     */
    public function getDefaultWhere(){
        return $this->defaultWhere;
    }
    public function setViewTableName($sql){
        $this->viewTableName = $sql;
    }
    public function find($options = array()) {
        if(!empty($this->viewTableName)){
            //$orgTableName    = $options["table"];
            $options["table"]   = $this->viewTableName;
            $this->viewTableIsSelect = true;
            $res  = parent::find($options);
            $this->viewTableIsSelect = false;
            //$options["table"]   = $orgTableName;
        }else
            $res  = parent::find($options);
        return $res;
    }
    public function select($options=array()){
        if(!empty($this->viewTableName)){
            //$orgTableName    = $options["table"];
            $options["table"]   = $this->viewTableName;
            $this->viewTableIsSelect = true;
            $res  = parent::select($options);
            $this->viewTableIsSelect = false;
            //$options["table"]   = $orgTableName;
        }else{
            $res  = parent::select($options);
        }
        return $res;
    }
    public function getField($field,$sepa=null) {
        if(!empty($this->viewTableName)){
            //$orgTableName    = $options["table"];
            $this->options["table"]   = $this->viewTableName;
            $this->viewTableIsSelect = true;
            $res  = parent::getField($field,$sepa);
            $this->viewTableIsSelect = false;
            //$options["table"]   = $orgTableName;
        }else{
            $res  = parent::getField($field,$sepa);
        }
        return $res;
    }

    //将find的数据，进行数据转换，从而能够直接显示数据。
    public function getInfo($listFields=""){
        $vo = $this->find();
        return $this->findToInfo($vo,$listFields);
    }
    public function findToInfo($vo,$listFields=""){
        if(empty($listFields)) $listFields = $this->getListFields();
        //将set、enum数据进行转换，为了显示具体的数据。。
        foreach($listFields as $field){
            if(!array_key_exists($field["name"],$vo)) continue;
            if($field["type"]=="date"){
                if(substr($vo[$field["name"]],0,10)=="0000-00-00") $vo[$field["name"]] = "";
            }else if(!empty($field["valChange"]) && empty($field["textTo"])){
                switch($field["type"]){
                case "set":
                    if($field["valFormat"]=="json"){
                        $tVals = json_decode($vo[$field["name"]],true);
                    }else{
                        $tVals = explode(",",$vo[$field["name"]]);
                    }
                    foreach($tVals as $tv){
                        if($tv!="" && $tv!=0){
                            $vo[$field["name"]."_textTo"][] = $field["valChange"][$tv];
                        }
                    }
                    $vo[$field["name"]."_textTo"] = implode(",",$vo[$field["name"]."_textTo"]);
                    break;
                default:
                    $vo[$field["name"]."_textTo"] = $field["valChange"][$vo[$field["name"]]];
                    break;
                }
            }
        }
        return $vo;
    }
    /*
     * 将老人资料转换为数据显示格式，将字典内容转换为对应的字符。
     * getInfo 只是为了edit使用，字典字段，还是用的数字。
     */
    public function getInfoToText($dataInfo,$listFields=""){
        if(empty($dataInfo)) return $dataInfo;
        if(empty($listFields)) $listFields = $this->getEditFields();
        foreach($listFields as $field){
            if($field["type"]=="cutPhoto"){
                $dataInfo[$field["name"]] = "http://".$_SERVER["HTTP_HOST"].__APP__."/Basic/showImg?f=".$dataInfo[$field["name"]];
            }else if(!empty($field["valChange"])){
                if(empty($field["textTo"])){
                    $dataInfo[$field["name"]] = $dataInfo[$field["name"]."_textTo"];
                }else{
                    $dataInfo[$field["name"]] = $dataInfo[$listFields[$field["textTo"]]];
                }
            }
        }
        return $dataInfo;
    }

    //3.1.2居然删除了配置 DB_FIELDTYPE_CHECK ，这里只能恢复他。
    protected function _parseOptions($options=array()) {
        if(is_array($options))
            $options =  array_merge($this->options,$options);
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->options  =   array();
        if(!isset($options['table'])){
            // 自动获取表名
            $options['table']   =   $this->getTableName();
            $fields             =   $this->fields;
        }else{
            // 指定数据表 则重新获取字段列表 但不支持类型检测
            $fields             =   $this->getDbFields();
        }

        if(!empty($options['alias'])) {
            $options['table']  .=   ' '.$options['alias'];
        }
        // 记录操作的模型名称
        $options['model']       =   $this->name;

        // 字段类型验证
        if(C('DB_FIELDTYPE_CHECK') && isset($options['where']) && is_array($options['where']) && !empty($fields)) {
            // 对数组查询条件进行字段类型检查
            foreach ($options['where'] as $key=>$val){
                $key            =   trim($key);
                if(in_array($key,$fields,true)){
                    if(is_scalar($val)) {
                        $this->_parseType($options['where'],$key);
                    }
                }elseif('_' != substr($key,0,1) && false === strpos($key,'.') && false === strpos($key,'|') && false === strpos($key,'&')){
                    unset($options['where'][$key]);
                }
            }
        }

        // 表达式过滤
        $this->_options_filter($options);
        return $options;
    }
    /** 自动增加数据权限功能，在所有的查询语句中追加数据权限控制条件 **/
    protected function _options_filter(&$options) {
        /**
         * 增加数据权限域管理的功能。
         * */
        if(APP_DEBUG) Log::write(var_export($options,true).MODULE_NAME."|".ACTION_NAME."__options",Log::INFO);
        if($this->skipDataPowerCheck 
            || DxFunction::checkInNotArray(C('DP_NOT_CHECK_MODEL'),array(),$this->name)
            || in_array($_SESSION["role_id"], C("DP_NOT_CHECK_ROLE"))
            ){
            return;
        }

        $dataPowerFieldW            = array();
        $dataPowerFieldPublic       = "";
        //查询的时候，需要根据视图sql进行数据权限的判定，但是在 修改时 就只能使用dbfileds进行
        if($this->viewTableIsSelect) $dbFields = array_keys($this->getListFields(false,true));
        else $dbFields = $this->getDbFields();
        $dataPowerFieldDelete       = "";
        //if(APP_DEBUG) Log::write(var_export($dbFields,true).MODULE_NAME."|".ACTION_NAME."__dbFields",Log::INFO);
        //追加数据删除字段标志,,直接追加Where条件。
        if(is_array(C('DELETE_TAGS'))){
            foreach(C('DELETE_TAGS') as $key=>$val){
                if(in_array($key,$dbFields)){
                    $dataPowerFieldDelete[] = sprintf("%s!='%s'",$key,$val);
                }
            }
        }

        $tDpFields = C('DP_POWER_FIELDS');
        $tDpFields      = array_merge($tDpFields,$this->DP_POWER_FIELDS);
        //dump($this->getModelName());dump($tDpFields);
        if(is_array($tDpFields) && sizeof($tDpFields)>0 && (!array_key_exists("DP_ADMIN", $_SESSION) || !$_SESSION["DP_ADMIN"])){
            //为了提高代码执行效率
            //某些模块不需要进行数据域验证，比如：登录；；管理员也不受此限制
            if(!DxFunction::checkNotAuth(C('DP_NOT_CHECK_ACTION'))){
                //方法一、是将表名直接转换为一个SQL子语句。。。这个要处理UPDATE太麻烦。
                //              $dataPowerTable = sprintf("(SELECT * FROM %s WHER %s like '%s%%' AND %s like '%s%%')",
                //                      $options['table'],C("DX_DATA_POWER_DEPT"),$_SESSION["dept_code"],C("DX_DATA_POWER_AREA"),$_SESSION["area_code"]);
                //
                //              if(!empty($options['alias'])) {
                //                  $options['select_table']   = $dataPowerTable.' '.$options['alias'];
                //              }else{
                //                  $options['select_table']   = $dataPowerTable.' '.$options['table'];
                //              }
                //方法二、将所有的where追加一些条件。难点是要判断where的类型：string、array、object
                //              print_r($dp_fields);
                foreach($tDpFields as $dp_fields){
                    $dataPowerOneW      = array();
                    $field_name         = $dp_fields["field_name"];
                    //如果没有定义session的名称，则使用字段名称。
                    if(array_key_exists("session_field", $dp_fields)) $session_field_name = $dp_fields["session_field"];
                    else $session_field_name = $field_name;
                    //dump(7777);
                    //dump("field".var_export($dp_fields,true).MODULE_NAME."|".ACTION_NAME."__DP_POWER_FIELDS",Log::INFO);
                    //dump("field".var_export($dbFields,true).MODULE_NAME."|".ACTION_NAME."__DBFIELDs",Log::INFO);
                    if($dp_fields["type"] & self::DP_TYPE_ENABLE && isset($_SESSION[$session_field_name]) && array_search($field_name,$dbFields,true)){
                        //dump($session_field_name."_field_".var_export($_SESSION,true).MODULE_NAME."|".ACTION_NAME."SESSION",Log::INFO);
                        if(is_array($_SESSION[$session_field_name])){
                            foreach($_SESSION[$session_field_name] as $key=>$val){
                                if(!empty($val)){
                                    switch($dp_fields["operator"]){
                                        case "eq":
                                            $dataPowerOneW[]    = sprintf("%s='%s'",$field_name,$val);
                                            break;
                                        default:
                                            $dataPowerOneW[]    = sprintf("%s like '%s%%'",$field_name,$val);
                                            break;
                                    }
                                }
                            }
                        }else if(!empty($_SESSION[$session_field_name])){
                            switch($dp_fields["operator"]){
                                case "eq":
                                    $dataPowerOneW[]    = sprintf("%s='%s'",$field_name,$_SESSION[$session_field_name]);
                                    break;
                                default:
                                    $dataPowerOneW[]    = sprintf("%s like '%s%%'",$field_name,$_SESSION[$session_field_name]);
                                    break;
                            }
                        }
                    }
                    if($dp_fields["type"]&self::DP_TYPE_PUBLIC && array_search($field_name,$dbFields,true)){
                        $dataPowerFieldPublic   = $field_name."=1";
                    }

                    if(!empty($dataPowerOneW))
                    {
                        $dataPowerFieldW[]  = "(".implode(" OR ",$dataPowerOneW).")";
                    }
                }
            }
        }
        //dump($this->name);dump($dataPowerFieldW);
        //大部分人员，喜欢使用管理员来操作数据，所以删除标记的数据，管理员也不能看到。
        $tempOptionsWhere       = "";
        if(!empty($dataPowerFieldW))
            $tempOptionsWhere       = $this->addOptionsWhere($dataPowerFieldPublic,implode(" AND ",$dataPowerFieldW),"OR");
        $tempOptionsWhere       = $this->addOptionsWhere($tempOptionsWhere,implode(" AND ",$dataPowerFieldDelete),"AND");
        $options["where"]       = $this->addOptionsWhere($options["where"],$tempOptionsWhere,"AND");
        //dump($dataPowerFieldW);dump($options["where"]);

        if(APP_DEBUG) Log::write(var_export($dataPowerFieldDelete,true).$this->name."|".MODULE_NAME."|".ACTION_NAME."dataPowerFieldDelete",Log::INFO);
        if(APP_DEBUG) Log::write(var_export($dataPowerFieldW,true).MODULE_NAME."|".ACTION_NAME."dataPowerFieldW",Log::INFO);
        if(APP_DEBUG) Log::write(var_export($dataPowerFieldPublic,true).MODULE_NAME."|".ACTION_NAME."dataPowerFieldPublic",Log::INFO);
    }

    //为Options的Where增加条件。
    public function addOptionsWhere($opWhere,$dataPowerWhere,$type="AND"){
        if(empty($dataPowerWhere)) return $opWhere;

        if(!isset($opWhere) || (is_string($opWhere) && trim($opWhere)=="") || (is_array($opWhere) && sizeof($opWhere)==0)){
            $opWhere = $dataPowerWhere;
        }else if(is_string($opWhere)){
            $opWhere = "(".$opWhere.") ".$type." ".$dataPowerWhere;
        }else if(is_array($opWhere) || is_object($opWhere)){
            if(is_object($opWhere)) $opWhere    = get_object_vars_final($opWhere);
            if(sizeof($opWhere)>0){
                $where['_complex']      = $opWhere;
                $where['_string']       = $dataPowerWhere;
                $where['_logic']        = $type;
                $opWhere        = $where;
            }
        }

        return $opWhere;
    }

    //将Model数据作为缓存进行存储,目前用于SysSetting的设置
    public function cacheDataToConfig($reset=false){
        $modelName = $this->getModelName();
        $sysSetData     = S("Cache_Global_".$modelName);
        if(empty($sysSetData) || $reset || C("APP_DEBUG")){
            $sysSetData = $this->select();
            S("Cache_Global_".$modelName,$sysSetData);
        }
        foreach($sysSetData as $set){
            C($modelName.".".$set["name"],$set["val"]);
        }
    }

    public function getCacheDictTableData($dictConfig=""){
        return $this->setCacheDictTableData($dictConfig);
    }
    /**
     * 设置缓存，公共的字典缓存是大家共享的，比如：老人类型，，私有的缓存是各自单独存放，比如职工信息
     * 再调用字典表的时候一定要注意，不要调用到getListFields方法，否则如果两个Model相互 valChange 引用，则会导致镶嵌引用，死循环。
     * 因为能获得临时的字典内容(参数$dictConfig)，所以，数据变动时，要清除所有的cache。
     * */
    protected function deleteCacheDictTableData(){
        $cacheNames = S('dict_cache_'.$this->name);
        fb::log($cacheNames,$this->name."-deleteCacheDictTableData");
        if(!empty($cacheNames)){
            foreach ($cacheNames as $key => $value) {
                fb::log($value,$this->name."-deleteCacheDictTableData");
                S($value,null);
            }
            S('dict_cache_'.$this->name,null);
        }
    }
    protected function setCacheDictTableData($dictConfig=""){
        if(empty($dictConfig)){
            $dictConfig = $this->getModelInfo("dictTable");
        }
        if(empty($dictConfig)) return array();

        if(is_array($dictConfig)){
            $configMd5 = md5(json_encode($dictConfig));
        }else{
            $configMd5 = md5($dictConfig);
        }

        if($this->getModelInfo("dictType")=="mySelf") $userId   = intval($_SESSION[C("USER_AUTH_KEY")]);
        else $userId    = 0;
        $cacheFileName = 'dict_cache_'.$this->name."_".$userId."_".$configMd5."_dict";
        fb::log($cacheFileName,$this->name."-setCacheDictTableData");
        $this->cacheDictDatas = S($cacheFileName);
        if(!empty($this->cacheDictDatas) && !APP_DEBUG){
            return $this->cacheDictDatas;
        }

        if(!empty($dictConfig)) {
            if(is_array($dictConfig)) $dictConfig = implode(",",$dictConfig); //兼容老格式
            if(sizeof(explode(",",$dictConfig))<2) $dictConfig  = $this->getPk().",".$dictConfig;   //使用主键作为key
            $tV = $this->field($dictConfig)->select();
            fb::log($this->getLastSQL());
            if($tV){
                $this->cacheDictDatas = DxFunction::arrayToArray($tV);
            }
            //缓存cacheName。用于清除缓存。
            $cacheNames = S('dict_cache_'.$this->name);
            if(empty($cacheNames)){
                $cacheNames = array($cacheFileName);
            }else if(!in_array($cacheFileName,$cacheNames)){
                $cacheNames[] = $cacheFileName;
            }
            S('dict_cache_'.$this->name,$cacheNames);
            fb::log($cacheNames);

            S($cacheFileName,$this->cacheDictDatas);
            return $this->cacheDictDatas;
        }
        return array();
    }

    /**
     * 更新与此model保持textTo关系的数据
     * @param {array} $data $isdel=true:要删除的数据查询条件   $isdel=false,要更新的数据内容，即data
     * @param {bool} $isdel 是不是删除出发的更新，删除触发的更新，会将id也进行清除
     * @return  bool   更新是否完成。
     */
    protected function updateTextTo($data,$isdel=false){
        $textTo = $this->getModelInfo("textTo");
        if(empty($textTo) || !is_array($textTo)) return true;
        foreach($textTo as $modelN => $fields){
            if($isdel===true){
                $op = $this->options;
                $idValues = $this->where($data)->getField($fields["fromid"],true);
                $this->options = $op;
                D($modelN)->where(array($fields["toid"]=>array("in"=>$idValues)))->save(array($fields["textto"]=>'',$fields["toid"]=>0));
            }else{
                D($modelN)->where(array($fields["toid"]=>array($data[$fields["fromid"]])))->save(array($fields["textto"]=>$data[$fields["fromtextto"]]));
            }
        }
    }
    //关联删除。类似于updateTextTo
    protected function relationDelete($where){
        $relationDelete = $this->getModelInfo("relationDelete");
        if(empty($relationDelete) || !is_array($relationDelete)) return true;
        foreach($relationDelete as $modelN => $fields){
            $idValues = $this->where($where)->getField($fields["fromid"],true);
            $relationM = D($modelN);
            $relationM->where(array($fields["toid"]=>array("in",$idValues)))->delete();
            fb::log($relationM->getLastSQL());
        }
    }
    
    /**
     * 将数据操作记录保存到表 DataChangeLog 中
     * **/
    protected function save_data_data_change_log($data,$options,$event){
        if(in_array($this->getModelName(),C("NO_SAVE_DATA_CHANGE"))) return;
        $m  = D("DataChangeLog");
        if($m){
            $m->add(array("model_name_cn"=>$this->getModelInfo("title"),"model_name"=>$this->getModelName(),"module_name"=>MODULE_NAME,"action_name"=>ACTION_NAME,
                'options'=>var_export($options,true),'options_ser'=>serialize($options),'data'=>var_export($data,true),'data_ser'=>serialize($data),
                "event"=>$event,'creater_user_id'=>$_SESSION[C('USER_AUTH_KEY')],'creater_user_name'=>$_SESSION[C('LOGIN_USER_NICK_NAME')]));
        }
    }
    /**
     * 从where条件中找点主键的值
     * where 条件并不是单一的 一维数组，可能会有And组合，所以需要，递归数组获取到。
     */
    protected function getPkIdFromWhere($where){
        $pkName = $this->getPk();
        if(empty($pkName)) return array(0);
        return $this->where($where)->getField($pkName,true);
    }
    protected function _after_delete($data, $options){
        $this->save_data_data_change_log($data,$options, "delete");
        if(C("FULLTEXT_SEARCH") && $this->getModelInfo("toString")!=""){
            $m  = D("FulltextSearch");
            $m->where(array("object"=>$this->name,"pkid"=>array("in",$this->getPkIdFromWhere($options["where"]))))->delete();
        }
        $this->deleteCacheDictTableData();
        $this->setCacheDictTableData();
    }
    protected function _before_update(&$data, $options) {
        parent::_before_update($data, $options);
        $this->autoOperation($data,self::MODEL_UPDATE);
        return true;
    }
    protected function _after_update($data, $options){
        $this->save_data_data_change_log($data,$options, "update");
        //更新textTo数据
        $this->updateTextTo($data,false);
        //更新字典表缓存
        $this->deleteCacheDictTableData();
        $this->setCacheDictTableData();
        //更新全文检索表
        if(C("FULLTEXT_SEARCH") && $this->getModelInfo("toString")!=""){
            $m          = D("FulltextSearch");
            //更新数据提交过来的数据，并不一定是数据库的所有字段，比如：卡号不能修改，则提交过来的数据将不会包含卡号，所以，需要重新从数据库获取新数据。
            $pkId       = $this->getPkIdFromWhere($options);
            $pkId       = $pkId[0];
            $saveState  = $m->where(array("object"=>$this->name,"data_id"=>$pkId))->save(
                array("content"=>$this->toString($pkId),
                "message_state"=>$this->fullTextState,
                "object_title"=>$this->getModelInfo("title"))
            );
        }
    }
    protected function _before_insert(&$data, $options) {
        parent::_before_insert($data, $options);
        //autoOperation 在TP中，只在create中执行。。要让他在任何地方执行。
        $this->autoOperation($data,self::MODEL_INSERT);
        return true;
    }
    protected function _after_insert($data,$options) {
        $this->save_data_data_change_log($data, $options, "insert");
        //缓存字典表数据 For 数据引用的字典表，数据转换。
        $this->deleteCacheDictTableData();
        $this->setCacheDictTableData();
        if(C("FULLTEXT_SEARCH") && $this->getModelInfo("toString")!=""){
            $m  = D("FulltextSearch");
            $m->add(array("object"=>$this->name,
                "data_id"=>$data[$this->getPk()],
                "content"=>$this->toString($data),
                "object_title"=>$this->getModelInfo("title"),
                "message_state"=>$this->fullTextState));
        }
    }


    /**全文检索 将Model生成为一个简洁描述  for 全文检索的字符 ***/
    public function toString($data){
        if(is_numeric($data)) {
            $data   = $this->find($data);
        }
        if($this->getModelInfo("toString")){
            $dd = array();
            $toStringF  = $this->getModelInfo("toString");
            foreach($toStringF[1] as $key){
                $dd[]   = $data[$key];
            }
            return vsprintf($toStringF[0],$dd);
        }else{
            return implode(" ",array_values($data));
        }
    }
    //重新构件本Model的现有数据的全文搜索
    public function fulltext_init(){
        if(C("FULLTEXT_SEARCH") && $this->getModelInfo("toString")!=""){
            $m  = D("FulltextSearch");
            $m->where(array("object"=>$this->name))->delete();
            $datas  = $this->select();
            foreach($datas as $data){
                $m->add(array("object"=>$this->name,"pkid"=>$data[$this->getPk()],"content"=>$this->toString($data),"object_title"=>$this->getModelInfo("title")));;
            }
        }
    }

    //首先根据自定义字段获取主键，然后再获取数据库的主键
    public function getPk() {
        foreach($this->listFields as $key => $field){
            if(!empty($field["pk"])) return empty($field["name"])?$key:$field["name"];
        }
        return parent::getPk();
    }

    
    /**
     * 根据ID获取某条Model信息
     * */
    public function getFromId($id){
        $info   = $this->find($id);
        if($info){
            foreach($this->getListFields() as $name=>$val){
                if(is_array($val["valChange"])){
                    $info[$name]    = $val["valChange"][$info[$name]];
                }
            }
        }
        return $info;
    }

    /**
     * 下面是为了实现某些功能重写了Model方法
     * 1.delete标记。数据删除时，只作标记，而不实际删除     DELETE_TAGS => array("field"=>value,"field"=>value)
     * 2.save保存时的bug，由于能够自动 附加 where条件，所以 save(pk)  和 save() 这种从data中找pk作为条件的操作，会失败，所以需要重写save方法。
     * 3.TP原来的所有_auto必须在create中才能运行，这里重新复制了此功能，保证在before insert update前能够使用_auto...另：TP自带的auto可能会覆盖data中的数据，这个也不太好。这里则不会出现这种情况
     * 注意
     * 1.TP原来的delete可以在没有where条件的情况下执行，现在的TP必须有where条件才能执行delete。
     * **/
    public function realDelete($options=array()){
        return parent::delete($options);
    }
    protected function _before_delete($options){
        $this->updateTextTo($options["where"],true);
        $this->relationDelete($options["where"]);
    }
    public function delete($options=array()) {
        $deleteStatus   = false;
        $f              = $this->getDbFields();
        if(is_array(C('DELETE_TAGS'))){
            foreach(C('DELETE_TAGS') as $key=>$val){
                if(in_array($key,$f)){
                    $deleteStatus   = true;
                    break;
                }
            }
        }

        if(empty($this->options["where"]))
            $this->options['where'] = $this->getDeleteWhere($options);
        if(empty($this->options['where'])){
            $this->error    = "Delete No Where";
            return false;
        }

        $op   = $this->options;
        $this->_before_delete($op);
        $this->options = $op; //在_before_delete中，可能回需要查询并删除其他数据。。从而导致Model的options丢失

        if($deleteStatus){
            return $this->deleteTag($op);
        }else{
            //在下面进行图片删除的过程中，可能还要执行此model的其他操作，为了防止option被改变，在此处进行临时存储
            $tOptions       = $this->options;
            $uploadField    = array();
            foreach($this->getListFields(false,true) as $key=>$val){
                //如果类型为上传文件，则删除上传的文件。
                if($val["type"]=="uploadFile"){
                    $uploadField[]  = $key;
                }
            }
            if(!empty($uploadField)){
                $dataList   = $this->field(implode(",",$uploadField))->where($tOptions["where"])->select();
                if($dataList){
                    foreach($dataList as $dataL){
                        foreach($uploadField as $fieldName){
                            $files  = json_decode($dataL[$fieldName],true);
                            foreach($files as $file){
                                unlink(C("UPLOAD_BASE_PATH")."/".$file["file_path"]);
                                if(!empty($file["thumbnail_file_path"])){
                                    unlink(C("UPLOAD_BASE_PATH")."/".$file["thumbnail_file_path"]);
                                }
                            }
                        }
                    }
                }
            }
            $this->options  = $tOptions;

            $vvv = parent::delete($options);
            return $vvv;
        }
    }
    protected function deleteTag($options=array()) {
        // 分析表达式
        $result     = $this->where($options["where"])->save(C('DELETE_TAGS'));
        fb::Log($this->getLastSQL());
        if(false !== $result) {
            $data = array();
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_delete($data,$options);
        }
        // 返回删除记录个数
        return $result;
    }
    protected function getDeleteWhere($options){
        if(empty($options) && empty($this->options['where'])) {
            // 如果删除条件为空 则删除当前数据对象所对应的记录
            if(!empty($this->data) && isset($this->data[$this->getPk()]))
                return array($this->getPk()=>$this->data[$this->getPk()]);
            else
                return false;
        }

        if(is_numeric($options) || is_string($options)) {
            // 根据主键删除记录
            $pk   =  $this->getPk();
            if(strpos($options,',')) {
                $where[$pk]   =  array('IN', $options);
            }else{
                $where[$pk]   =  $options;
                $pkValue = $options;
            }
            return $where;
        }
        return false;
    }

    /**
     * TP save方法的bug，应该，将_parseOptions($options) 放在 id判定之后。看delete方法就是这样。
     * 否则，再 _parseOptions 中调整的 where 内容，将被忽略掉。
     */
    public function save($data='',$options=array()) {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {
                $data    =   $this->data;
                // 重置数据
                $this->data = array();
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 数据处理
        $data = $this->_facade($data);
        //if(!isset($options['where']) && !isset($this->options['where']) ) {
        // 如果存在主键数据 则自动作为更新条件
        if(isset($data[$this->getPk()])) {
            $pk   =  $this->getPk();
            $where[$pk]   =  $data[$pk];
            $options['where']  =  $where;
            $pkValue = $data[$pk];
            unset($data[$pk]);
        }else if(!isset($options['where']) && !isset($this->options['where']) ) {
            // 如果没有任何更新条件则不执行
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }
        //}
        // 分析表达式
        $options =  $this->_parseOptions($options);
        if(false === $this->_before_update($data,$options)) {
            return false;
        }
        $result = $this->db->update($data,$options);
        if(false !== $result) {
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_update($data,$options);
        }
        return $result;
    }
    /**
     * copy自TP，因为是private，所以无法访问。。只能复制出来.
     * 增加
     * 1. 如果没有传递此字段的值，并且设置属性为ignore则对其进行忽略.....好像这个没有用了。
     * 2. 如果有值，则不进行自动填充
     */
    protected function autoOperation(&$data,$type) {
        if(!empty($this->options['auto'])) {
            $_auto   =   $this->options['auto'];
            unset($this->options['auto']);
        }elseif(!empty($this->_auto)){
            $_auto   =   $this->_auto;
        }
        // 自动填充
        if(isset($_auto)) {
            foreach ($_auto as $auto){
                
                //框架添加的代码
                // if(!array_key_exists($auto[1],$data) && $auto[3]=="ignore") continue;
                if(!empty($data[$auto[0]])) continue;       //如果有这个值，则不要覆盖。

                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if(empty($auto[2])) $auto[2] = self::MODEL_INSERT; // 默认为新增的时候自动填充
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    switch(trim($auto[3])) {
                        case 'function':    //  使用函数进行填充 字段的值作为参数
                        case 'callback': // 使用回调方法
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': // 为空忽略
                            if(''===$data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: // 默认作为字符串填充
                            $data[$auto[0]] = $auto[1];
                    }
                    if(false === $data[$auto[0]] )   unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }
    /**
     * 重写了create方法，因为原来的create方法，只接受 POST，改为REQUEST
     */
    public function create($data='',$type='') {
        if(empty($data)) {
            $data    =   $_REQUEST;
        }
        $data = parent::create($data,$type);
        return $data;
    }

    /**
     * 转换model的后台验证规则为 前台验证规则.jQuery validate
     * 后台规则格式：array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
     * 此方法主要是为是初始化规则转换时需要的参数.
     * @param   $valids     TP的验证规则
     * @param   $type       验证时机，，生成验证规则是为了Insert还是为了Update
     */
    protected function convertValid(array $valids){
        $ret    = array();
        $lang   = array();
        foreach($valids as $valid){
            //需要验证的字段;
            // array(field,rule,message,condition,type,when,params)
            $fld    = $valid[0];
            $rule   = $valid[1];
            $error  = $valid[2];
            $cond   = isset($valid[3])?$valid[3]:"";
            $vrule  = isset($valid[4])?$valid[4]:"";
            $vtime  = isset($valid[5])?$valid[5]:self::MODEL_BOTH;

            $cond   = isset($cond)?$cond:Model::EXISTS_VAILIDATE;
            $vrule  = isset($vrule)?$vrule:'regex';
            $oneValid   = $this->genValidate( $rule,  $cond,  $vrule);
            if($vtime == self::MODEL_BOTH) $oneValid = array(self::MODEL_INSERT=>$oneValid,self::MODEL_UPDATE=>$oneValid);
            else $oneValid = array($vtime=>$oneValid);
            //一个字段，可能会定义多个验证规则。这里将验证规则 合并。
            $tempFld = explode(",",$fld);  // callback 验证支持，多个字段验证
            foreach($tempFld as $tfld){
                if(isset($ret[$tfld])){
                    $ret[$tfld]  = array(
                        self::MODEL_INSERT=>array_unique(array_merge($ret[$tfld][self::MODEL_INSERT], $oneValid[self::MODEL_INSERT])),
                        self::MODEL_UPDATE=>array_unique(array_merge($ret[$tfld][self::MODEL_UPDATE], $oneValid[self::MODEL_UPDATE])),
                        "validateMsg" => $ret[$tfld]["validateMsg"],
                        "regex" => $ret[$tfld]["regex"],
                    );
                }else{
                    $ret[$tfld]  = $oneValid;
                }
                if(empty($ret[$tfld]["validateMsg"])) $ret[$tfld]["validateMsg"] = $error;
                if(empty($ret[$tfld]["regex"])) $ret[$tfld]["regex"] = $rule;
            }
        }
        return $ret;
    }

    /**
     * @param       $rule       验证规则
     * @param       $error
     * @param       $cond       TP的验证条件
     * @param       $vrule      TP的附加验证规则
     */
    protected function genValidate( $rule, $cond,  $vrule){
        $ret    = array();
        //根据验证规则，生成第二批验证信息，空 或 其他情况,需要使用附加验证信息进行构建。
        $enable_rule    = false;
        switch($rule){
            case 'require':
                $ret[]='required';
                break;
            case 'email':
                $ret[]='custom[email]';
                break;
            case 'url':
                $ret[]='custom[url]';
                break;
            case 'currency':
                $ret[]='custom[number]';
                break;
            case 'number':
                $ret[]='custom[integer]';
                break;
            default:
                $enable_rule    = true;
                break;
        }
        /**
         * 前端验证的提示信息，是在js中指定的。来自于 rule的定义，是统一的。。此处写这个东西，虽然可以统一前后台消息内容，但是会导致消息内容重复。
         * 注释掉，则前后台提示信息不一致。。所以，尽量后台验证规则的描述语言与前台一致。
         * **/

        if($enable_rule){
            switch($vrule){
                case "regex":
                    //php regex to javascript regex
                    $rule = urldecode($rule);
                    $ret[]="custom[regex]";
                    break;
                case "function":
                case "callback":
                case "unique":
                    $ret[]="ajax[remoteValidataField]";
                    break;
                case "confirm":
                    $ret[]="equals[$rule]";
                    break;
                case "equal":
                    //必须等于某个值，则无需填入，，
                    $ret[]="custom[eq,$rule]";
                    break;
                case "in":
                    $rule = str_replace(",","|",$rule);
                    $ret[]="custom[rangein,$rule]";
                    break;
                case "length":
                    list($min,$max)   =  explode(',',$rule);
                    $ret[]="minSize[{$min}]";
                    $ret[]="maxSize[{$max}]";
                    break;
                case "between":
                    list($min,$max)   =  explode(',',$rule);
                    $ret[]="min[{$min}]";
                    $ret[]="max[{$max}]";
                    break;
                case "past":
                case "future":
                    $ret[]=$vrule."[".$rule."]";
                    break;
                case "ip_allow":
                    break;
                case "ip_deny":
                    break;
            }
        }
        return array_unique($ret);
    }

    /**
     * 扩展TP的验证规则
     */
    public function check($value,$rule,$type='regex'){
        switch($type){
        case "past":
        case "future":
            if($rule[0] == "#"){
                $otherValue = $_REQUEST[substr($rule,1)];
            }else if($rule == "NOW"){
                $otherValue = date("Y-m-d H:i:s");
            }
            return $type=="past"?$value<=$otherValue:$value>=$otherValue;
            break;
        default:
            return parent::check($value,$rule,$type);
            break;
        }
    }

    /**
     * 验证数据合法性
     * remoteValidataField 调用，ajax验证
     * @param   $data   要验证的数据，就是where条件
     * @param   $name   要验证的字段
     * */
    public function validataField($data, $name){
        $type = intval($data[$this->getPk()])>0?self::MODEL_UPDATE:self::MODEL_INSERT;
        foreach($this->_validate as $valid){
            $when   = isset($valid[5])?$valid[5]:self::MODEL_BOTH;
            $fields = explode(",",$valid[0]);
            if(in_array($name,$fields) && ($type & $when)>0){
                $rv = true;
                switch($valid[3]) {
                    case self::MUST_VALIDATE:   // 必须验证 不管表单是否有设置该字段
                        $rv = $this->_validationField($data, $valid);
                        break;
                    case self::VALUE_VALIDATE:    // 值不为空的时候才验证
                        if('' != trim($data[$name])){
                            $rv = $this->_validationField($data, $valid);
                        }
                        break;
                    default:    // 默认表单存在该字段就验证
                        if(isset($data[$val[0]]))
                            $rv = $this->_validationField($data, $valid);
                        break;
                }
                if($rv===false) return $rv;
            }
        }
        return true;
    }

    /**
     * 根据Model定义，生成大致的表创建SQL语句
     */
    public function fnCreateTable(){
        $tszTableName       = parse_name($this->name);
        foreach ($this->listFields as $tszFieldName=>$thFieldInfo){
            $thTableFieldInfo    =  $this->fnTableFieldInfo($thFieldInfo);
            if(!empty($thTableFieldInfo)){
                if($thFieldInfo['pk']){
                    $thFieldSql[$tszFieldName]    = sprintf('    `%s` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT "%s%s",PRIMARY KEY (`%1$s`)',
                                                            $tszFieldName,$thFieldInfo['title'],$thFieldInfo['note']);
                }else{
                    $thFieldSql[$tszFieldName]    = sprintf('    `%s` %s NOT NULL DEFAULT "%s" COMMENT "%s%s"',
                                                            $tszFieldName,$thTableFieldInfo['type'],
                                                            $thFieldInfo['default'],$thFieldInfo['title'],$thFieldInfo['note']);
                }
            }
        }
        $sql = array();
        $sql[] = "DROP TABLE IF EXISTS `".$tszTableName."`;";
        $sql[] = "CREATE TABLE IF NOT EXISTS `".$tszTableName."` (";
        $sql[] = implode(",\n",$thFieldSql);
        $sql[] = ')ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT="'.$this->modelInfo["title"].'";';
        //$this->query($tszCreateTableSql);
        echo implode("\n",$sql);
    }
    public function fnTableFieldInfo(&$thFieldInfo){
        if(empty($thFieldInfo) || !is_array($thFieldInfo)){
            return false;
        }else{
                if(empty($thFieldInfo['type'])){
                    $thFieldInfo['type']    = 'string';
                }else if($thFieldInfo['type']=='float' && empty($thFieldInfo['size'])){
                    $thFieldInfo['size']    = "9,2";
                }
                //默认情况下按照
                switch ($thFieldInfo['type']) {
                    case 'canton':
                        $tszType     = 'VARCHAR(64)';
                        break;
                    case 'date':
                        $tszType     = 'TIMESTAMP';
                        $thFieldInfo["default"] = "0000-00-00 00:00:00";
                        // if(strlen($thFieldInfo["valFormat"])<11){
                        //     if(strpos($thFieldInfo["valFormat"],"yyyy")){
                        //         $tszType     = 'date';
                        //     }else{
                        //         $tszType     = 'time';
                        //     }
                        // }
                        break;
                    case 'uploadFile':
                        $tiTsize     = 2048*8;
                        $tszType     = 'VARCHAR('.$tiTsize.")";
                        break;
                    case 'int':
                        $tiTsize     = ($thFieldInfo['size'])?intval($thFieldInfo['size']):11;
                        if($tiTsize<4) $tszType = "TINYINT(".$tiTsize.") unsigned";
                        else if($tiTsize<6) $tszType = "SMALLINT(".$tiTsize.") unsigned";
                        else $tszType     = 'INT('.$tiTsize.") unsigned";
                        $thFieldInfo["default"] = "0";
                        break;
                    case 'select':
                        if(empty($thFieldInfo["multiple"])){
                            $tszType     = 'TINYINT(4) unsigned';
                            $thFieldInfo["default"] = "0";
                        }else{
                            $tszType     = 'VARCHAR(128)';
                        }
                        break;
                    case 'set':
                        $tszType     = 'VARCHAR(128)';
                        break;
                    case 'enum':
                        $tszType     = 'TINYINT(4) unsigned';
                        $thFieldInfo["default"] = "0";
                        break;
                    case 'string' :
                    case 'password':
                    case "cutPhoto":
                    default:
                        if(intval($thFieldInfo["width"])<1000){
                            $tiTsize    = 128;
                        }else{
                            $tiTsize    = 256*8*4;
                        }
                        $tszType    = 'VARCHAR('.$tiTsize.")";
                        break;
                }
                $thFieldInfo['type'] = $tszType;
                return $thFieldInfo;
        }
    }

    /**
     * 获取标记为删除状态的字段组成的条件。
     * @param       $returnArray        是否返回条件为数组
     * @param       $table_alias        表别名，因为返回的条件，可能是要用到组合查询中。
     * @return string
     */
    public function getDeleteStateWhere($returnArray = true,$table_alias = ""){
        $dbFields               = $this->getDbFields();
        $dataPowerFieldDelete   = "";
        //if(APP_DEBUG) Log::write(var_export($dbFields,true).MODULE_NAME."|".ACTION_NAME."__dbFields",Log::INFO);
        //追加数据删除字段标志,,直接追加Where条件。
        if(!empty($table_alias)){
            $table_alias = $table_alias.'.';
        }
        if(is_array(C('DELETE_TAGS'))){
            foreach(C('DELETE_TAGS') as $key=>$val){
                if(in_array($key,$dbFields)){
                    $dataPowerFieldDelete[] = sprintf("%s%s!='%s'",$table_alias,$key,$val);
                }
            }
        }
        if ($returnArray == false){
            $dataPowerFieldDelete = implode(" AND ", $dataPowerFieldDelete);
        }
        if(empty($dataPowerFieldDelete)) return "1";
        return $dataPowerFieldDelete;
    }

    /**
     * 记录系统日志
     * @param       string      $msg     日志内容
     * @param       array       $data    日志相关的业务数据
     * @param       string      $type    日志类型
     * @return      bool                 存储日志是否成功
     */
    public function log($msg,$data,$type=LOG::ERR){
        $data = array_merge($data,array("_REQUEST"=>$_REQUEST));
    }
}

