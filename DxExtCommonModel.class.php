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
    protected $viewTableName    = "";
    private $viewTableIsSelect  = false;

    protected $fullTextState    = 0;        //Model追加的fulltext数据的状态,一般在model的_before_insert  _before_update中改变此属性
    /* 将所有的数据库字段，全初始化为数据列表字段，默认使用数据库字段名 */
    function initListFields(){
        if(sizeof($this->listFields) > 0) return;
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

    // 将数据列表Grid要显示的字段，整合为一个字符串，作为SELECT 语句的字段列表
    public function getListFieldString() {
        return $this->getFieldsString ( self::HIDE_FIELD_DATA );
    }
    // 将要打印的字段，整合为一个字符串，作为SELECT 语句的字段列表
    public function getPrintFieldString() {
        return $this->getFieldsString ( self::HIDE_FIELD_PRINT );
    }
    private function getFieldsString($hideState) {
        $r = array ();
        foreach ( $this->getNoHideFields ( $hideState ) as $key => $val ) {
            if (isset ( $val ["field"] ))
                $r [] = $val ["field"];
            else if (isset ( $val ["name"] ))
                $r [] = $val ["name"];
            else
                $r [] = $key;
        }
        return implode ( ",", $r );
    }

    function __construct($name='',$connection='') {
        parent::__construct($name,$connection);

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
        if(isset($tDpFields) && is_array($tDpFields)){
            foreach(C('DP_POWER_FIELDS') as $dp_fields){
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
     * **/
    public function getListFields($reNew=false,$original=false){
        Log::write($this->name."->getListFields",LOG::INFO);
        if($original) return $this->listFields;
        //调试阶段，可能需要不断改变listFields的值
        if($reNew || C("APP_DEBUG")){
            $this->setCacheListFields();
            return $this->cacheListFields;
        }
        if(empty($this->cacheListFields)){
            $cacheFile = '_fields/'.$this->name."_listFields_".$this->getListFieldsMd5();
            $this->cacheListFields  = F($cacheFile);
            //dump($this->cacheDictDatas);
            if(empty($this->cacheListFields)){
                $this->setCacheListFields($cacheFile);
            }
        }
        return $this->cacheListFields;
    }
    public function getListFieldsMd5(){
        return md5(json_encode($this->listFields));
    }
    public function getModelInfoMd5(){
        return md5(json_encode($this->modelInfo));
    }
    private function setCacheListFields($cacheFile){
        $tListFields   = array();
        foreach($this->listFields as $key=>$field){
            $tListFields[isset($field["name"])?$field["name"]:$key] = $this->getOneListField($key,$field);
        }

        //转换Model的自动验证规则为formValidation形式
        $tempValid  = $this->convertValid($this->_validate);
        foreach($tempValid as $fld =>$vvvv){
            $tListFields[$fld]["valid"][self::MODEL_INSERT] = implode(" ",$vvvv[self::MODEL_INSERT]);
            $tListFields[$fld]["valid"][self::MODEL_UPDATE] = implode(" ",$vvvv[self::MODEL_UPDATE]);
        }

        F($cacheFile,$tListFields);
        $this->cacheListFields  = $tListFields;
    }
    private function getOneListField($key,$field){
        if(!isset($field["name"])) $field["name"]   = $key;
        if($field["type"]=="canton" && empty($field["valChange"])){
            $field["valChange"] = array("model"=>"Canton");
        }
        if(intval($field["width"])<1) $field["width"] = "80";

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
        }else if(is_array($field["valChange"])){
            //将字典表，转换为valChange数据
            if(isset($field["valChange"]["model"])){
                if($this->name==$field["valChange"]["model"])
                    $m    = $this;
                else
                    $m    = D($field["valChange"]["model"]);
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
        }

        if(isset($field["default"])){
            //设置默认值
            $d  = $field['default'];
            if(is_array($d)){
                if(count($d)>=2){
                    switch($d[0]){
                    case "func":
                        $func=$d[1];
                        if(function_exists($func)){
                            $param_arr=$d;
                            //移除前两个元素
                            array_shift($param_arr);
                            array_shift($param_arr);
                            $field["default"]=call_user_func_array($func, $param_arr);
                        }
                        break;
                    }
                }
            }else
                $field['default']=$d;
            //重置默认值
        }
        return $field;
    }
    // 通过key改变model的listFields属性
    public function setListField($key, $v) {
        $this->listFields [$key] = array_merge ( $this->listFields [$key], $v );
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
                if(!isset($field["name"])) $field["name"]   = $name;
                $f[$field["name"]]  = $field;
                $f[$field["name"]]["readOnly"]  = (bool)($field["readOnly"] & self::HIDE_FIELD_ADD);
                $f[$field["name"]]["display_none"]  = (bool)($field["display_none"] & self::HIDE_FIELD_ADD);
            }else if(!($field["hide"] & self::HIDE_FIELD_EDIT) && $pkId>0){
                if(!isset($field["name"])) $field["name"]   = $name;
                $f[$field["name"]]  = $field;
                $f[$field["name"]]["readOnly"]  = (bool)($field["readOnly"] & self::HIDE_FIELD_EDIT);
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
    private function getNoHideFields($hideTag){
        //编辑数据的字段列表，编辑数据时，要隐藏某些字段
        $f  = array();
        $frozen=array();
        foreach($this->getListFields() as $key=>$field){
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
     * ****/
    public function fieldToGridField(){
        $gridFields     = array();
        if($this->getModelInfo("hasCheckBox")){
            $gridFields[0]  = array("id"=>"chk","isCheckColumn"=>true,"header"=>"全选");
        }
        $datasetFields  = array();
        $lFields        = $this->getListFields();
        foreach($lFields as $fieldNameKey   => $field){
            if(!($field["hide"] & self::HIDE_FIELD_LIST)){
                $fieldName  = empty($field["name"])?$fieldNameKey:$field["name"];
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
                            if($field["valFormat"]=="douhao")
                                $valueToJson    = "if(value=='') return '';var value = value.split(',');var r='';$(value).each(function(i,v){if(valChangeDatas[v]!=undefined) r+=valChangeDatas[v]+' ';});return r;";
                            else
                                $valueToJson    = "if(value[0]=='['){value = eval(value);var r='';$(value).each(function(i,v){r+=valChangeDatas[v]+' ';});return r;}else{return value;}";
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
        //dump($datasetFields);dump($gridFields);die();
        return array("gridFields"=>$gridFields,"datasetFields"=>$datasetFields);
    }
    /**
     * 移除：将Model所需的查询列，获取到
     * **/
    public function getSearchFields(){
        $searchFiled    = array();
        foreach($this->getListFields() as $field){
            if(isset($field["search"])){
                $searchFiled[]  = array_merge($field["search"],$field);
                //array("type"=>$field["type"],"enname"=>$field["name"],"cnname"=>$field["title"],"valChange"=>$field[""]));
            }
        }
        return $searchFiled;
    }
    
    /**
     * 使用相套sql语句，代替视图
     * 1.请勿将where条件写在  函数的参数中，请使用where进行where参数传递
     */
    public function find($options = array()) {
        if(!empty($this->viewTableName)){
            $orgTableName    = $options["table"];
            $options["table"]   = $this->viewTableName;
            $this->viewTableIsSelect = true;
            $res  = parent::find($options);
            $this->viewTableIsSelect = false;
            $options["table"]   = $orgTableName;
        }else
            $res  = parent::find($options);
        return $res;
    }
    public function select($options=array()){
        if(!empty($this->viewTableName)){
            $orgTableName    = $options["table"];
            $options["table"]   = $this->viewTableName;
            $this->viewTableIsSelect = true;
            $res  = parent::select($options);
            $this->viewTableIsSelect = false;
            $options["table"]   = $orgTableName;
        }else
            $res  = parent::select($options);
        return $res;
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
        if($this->skipDataPowerCheck || DxFunction::checkInNotArray(C('DP_NOT_CHECK_MODEL'),array(),$this->name)) return;

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
        
        if(is_array(C('DP_POWER_FIELDS')) && sizeof(C('DP_POWER_FIELDS'))>0 && (!array_key_exists("DP_ADMIN", $_SESSION) || !$_SESSION["DP_ADMIN"])){        //为了提高代码执行效率
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
                foreach(C('DP_POWER_FIELDS') as $dp_fields){
                    $dataPowerOneW      = array();
                    $field_name         = $dp_fields["field_name"];
                    //如果没有定义session的名称，则使用字段名称。
                    if(array_key_exists("session_field", $dp_fields)) $session_field_name = $dp_fields["session_field"];
                    else $session_field_name = $field_name;
                    //Log::write("field".var_export($dp_fields,true).MODULE_NAME."|".ACTION_NAME."__DP_POWER_FIELDS",Log::INFO);
                    //Log::write("field".var_export($dbFields,true).MODULE_NAME."|".ACTION_NAME."__DBFIELDs",Log::INFO);
                    if($dp_fields["type"] & self::DP_TYPE_ENABLE && isset($_SESSION[$session_field_name]) && array_search($field_name,$dbFields,true)){
                        //Log::write($session_field_name."_field_".var_export($_SESSION,true).MODULE_NAME."|".ACTION_NAME."SESSION",Log::INFO);
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
        //dump($this->name);
        //大部分人员，喜欢使用管理员来操作数据，所以删除标记的数据，管理员也不能看到。
        $tempOptionsWhere       = "";
        if(!empty($dataPowerFieldW))
            $tempOptionsWhere       = $this->addOptionsWhere($dataPowerFieldPublic,implode(" AND ",$dataPowerFieldW),"OR");
        $tempOptionsWhere       = $this->addOptionsWhere($tempOptionsWhere,implode(" AND ",$dataPowerFieldDelete),"AND");
        //dump($options["where"]);
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

    public function getCacheDictTableData(){
        $userId = intval(session(C("USER_AUTH_KEY")));
        //如果没有初始化
        if(empty($this->cacheDictDatas)){
            $this->cacheDictDatas   = F('_fields/'.$this->name."_".$userId."_dict");
            //dump($this->cacheDictDatas);
            if(empty($this->cacheDictDatas)) $this->setCacheDictTableData();
        }
        return $this->cacheDictDatas;
    }
    /**
     * 设置缓存，公共的字典缓存是大家共享的，比如：老人类型，，私有的缓存是各自单独存放，比如职工信息
     * 再调用字典表的时候一定要注意，不要调用到getListFields方法，否则如果两个Model相互 valChange 引用，则会导致镶嵌引用，死循环。
     * */
    protected function setCacheDictTableData(){
        if($this->getModelInfo("dictType")=="mySelf") $userId   = intval(session(C("USER_AUTH_KEY")));
        else $userId    = 0;
        $dictConfig = $this->getModelInfo("dictTable");

        if(!empty($dictConfig)) {
            if(is_array($dictConfig)) $dictConfig   = implode(",",$dictConfig); //兼容老格式
            if(sizeof(explode(",",$dictConfig))<2) $dictConfig  = $this->getPk().",".$dictConfig;   //使用主键作为key
            $tV = $this->field($dictConfig)->select();
            if($tV){
                $this->cacheDictDatas = DxFunction::arrayToArray($tV);
            }
            return F('_fields/'.$this->name."_".$userId."_dict",$this->cacheDictDatas);
        }
        return 0;
    }

    /**
     * 将数据操作记录保存到表 DataChangeLog 中
     * **/
    protected function save_data_data_change_log($data,$options,$event){
        if($this->getModelName()=="DataChangeLog" || $this->getModelName()=="OperationLog") return;
        $m  = D("DataChangeLog");
        if($m){
            $m->add(array("model_name"=>$this->getModelName(),"module_name"=>MODULE_NAME,"action_name"=>ACTION_NAME,
                'options'=>var_export($options,true),'options_ser'=>serialize($options),'data'=>var_export($data,true),'data_ser'=>serialize($data),
                "event"=>$event,'user_id'=>$_SESSION[C('USER_AUTH_KEY')],'user_name'=>$_SESSION[C('LOGIN_USER_NICK_NAME')]));
        }
    }
    //where 条件并不是单一的 一维数组，可能会有And组合，所以需要，递归数组获取到。
    protected function getPkIdFromWhere($options){
        $pkId   = $this->getPk();
        foreach ($options as $key=>$val){
            if($key===$pkId){
                return $val;
            }else{
                if(is_array($val)){
                    return $this->getPkIdFromWhere($val);
                }
            }
        }
        return 0;
    }
    protected function _after_delete($data, $options){
        $this->save_data_data_change_log($data,$options, "delete");
        if(C("FULLTEXT_SEARCH") && $this->getModelInfo("toString")!=""){
            $m  = D("FulltextSearch");
            $m->where(array("object"=>$this->name,"pkid"=>$this->getPkIdFromWhere($options)))->delete();
        }
    }
    protected function _before_update(&$data, $options) {
        parent::_before_update($data, $options);
        $this->myAutoOperation($data,self::MODEL_UPDATE);
        return true;
    }
    protected function _after_update($data, $options){
        $this->save_data_data_change_log($data,$options, "update");
        //更新字典表缓存
        $this->setCacheDictTableData();
        //更新全文检索表
        if(C("FULLTEXT_SEARCH") && $this->getModelInfo("toString")!=""){
            $m          = D("FulltextSearch");
            //更新数据提交过来的数据，并不一定是数据库的所有字段，比如：卡号不能修改，则提交过来的数据将不会包含卡号，所以，需要重新从数据库获取新数据。
            $pkId       = $this->getPkIdFromWhere($options);
            $saveState  = $m->where(array("object"=>$this->name,"data_id"=>$this->getPkIdFromWhere($options)))->save(
                array("content"=>$this->toString($pkId),
                "message_state"=>$this->fullTextState,
                "object_title"=>$this->getModelInfo("title"))
            );
        }
    }
    protected function _before_insert(&$data, $options) {
        parent::_before_insert($data, $options);
        $this->myAutoOperation($data,self::MODEL_INSERT);
        return true;
    }
    protected function _after_insert($data,$options) {
        $this->save_data_data_change_log($data, $options, "insert");
        //缓存字典表数据 For 数据引用的字典表，数据转换。
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
                                unlink(C("UPLOAD_BASE_PATH").dirname($file["url"])."/".$file["name"]);
                                if(!empty($file["thumbnail_url"])){
                                    unlink(C("UPLOAD_BASE_PATH").dirname($file["thumbnail_url"])."/thumbnail_".$file["name"]);
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
     * 否则，再 _parseOptions 中调整where内容，会影像数据更新。
     * 判定已经有查询条件时，元save方法，未检查 $this->options,,实际应该更进一步，违背编码实现数据的一致性。所有的update，如果data中有主键，则直接将主键作为条件
     * **/
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
     * 增加1.如果没有传递此字段的值，并且设置属性为ignore则对其进行忽略,,如果有值，则不进行自动填充
     */
    protected function myAutoOperation(&$data,$type) {
        // 自动填充
        if(!empty($this->_auto)) {
            foreach ($this->_auto as $auto){

                //框架添加的代码
                if(!array_key_exists($auto[5],$data) && $auto[5]=="ignore") continue;
                if(!empty($data[$auto[0]])) continue;       //如果有这个值，则不要覆盖。

                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if(empty($auto[2])) $auto[2] = self::MODEL_INSERT; // 默认为新增的时候自动填充
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    switch($auto[3]) {
                        case 'function':    //  使用函数进行填充 字段的值作为参数
                        case 'callback': // 使用回调方法
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  = call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
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
        // 如果没有传值默认取POST数据
        if(empty($data)) {
            $data    =   $_REQUEST;
        }elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }
        // 验证数据
        if(empty($data) || !is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }

        // 检查字段映射
        $data = $this->parseFieldsMap($data,0);

        // 状态
        $type = $type?$type:(!empty($data[$this->getPk()])?self::MODEL_UPDATE:self::MODEL_INSERT);

        // 数据自动验证
        if(!$this->autoValidation($data,$type)) return false;

        // 表单令牌验证
        if(C('TOKEN_ON') && !$this->autoCheckToken($data)) {
            $this->error = L('_TOKEN_ERROR_');
            return false;
        }

        // 验证完成生成数据对象
        if($this->autoCheckFields) { // 开启字段检测 则过滤非法字段数据
            $vo   =  array();
            foreach ($this->fields as $key=>$name){
                if(substr($key,0,1)=='_') continue;
                $val = isset($data[$name])?$data[$name]:null;
                //保证赋值有效
                if(!is_null($val)){
                    $vo[$name] = (MAGIC_QUOTES_GPC && is_string($val))?   stripslashes($val)  :  $val;
                }
            }
        }else{
            $vo   =  $data;
        }

        // 创建完成对数据进行自动处理
        $this->myAutoOperation($vo,$type);
        // 赋值当前数据对象
        $this->data =   $vo;
        // 返回创建的数据以供其他调用
        return $vo;
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
            if(isset($ret[$fld])){
                $ret[$fld]  = array_unique(array_merge($ret[$fld], $oneValid));
            }else{
                $ret[$fld]  = $oneValid;
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
        // 判断验证条件,根据验证条件，附加第一层次验证;; noempty 和 existed 是不存在的前台规则，前台只要不是 required 则空值不验证
        switch($cond) {
            case Model::MUST_VALIDATE:      // 必须验证 不管表单是否有设置该字段
            case Model::EXISTS_VALIDATE:    //存在字段就验证
                $ret[]='required';
                break;
            case Model::VALUE_VALIDATE:    // 值不为空的时候才验证
                $ret[]='noempty';
                break;
            default:    // 默认表单存在该字段就验证
                $ret[]='existed';
                break;
        }
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
                //other rule
                $enable_rule    = true;
                break;
        }
        /**
         * 前端验证的提示信息，是在js中指定的。来自于 rule的定义，是统一的。。此处写这个东西，虽然可以统一前后台消息内容，但是会导致消息内容重复。
         * 注释掉，则前后台提示信息不一致。。所以，尽量后台验证规则的描述语言与前台一致。
         * if(!empty($error)){
         *      $ret[]="funcCall[showFieldMessage,$error]";
         * }
         * **/
        
        if($enable_rule){
            switch($vrule){
                case "regex":
                    //php regex to javascript regex
                    //$ret[]="funcCall[checkForm[{$error}]]";
                    break;
                case "function":
                    $ret[]="ajax[checkFieldByFunction,{$rule}]";
                    break;
                case "callback":
                    //php callback to javascript callback
                    break;
                case "confirm":
                    $ret[]="equals[$rule]";
                    break;
                case "equal":
                    //必须等于某个值，则无需填入，，
                    //$ret[]="funcCall[eq[{$rule}]]";
                    //$ret[]="custom[eq({$rule})]";
                    break;
                case "in":
                    $rule=str_replace(",", "|", $rule);
                    $ret[]="funcCall[rangein[{$rule}]]";
                    break;
                case "length":
                    list($min,$max)   =  explode(',',$rule);
                    $ret[]="minSize[{$min}]";
                    $ret[]="maxSize[{$max}]";
                    break;
                case "between":
                    list($min,$max)   =  explode(',',$rule);
                    if(is_numeric($min)){
                        //integer or float
                        $ret[]="min[{$min}]";
                        $ret[]="max[{$max}]";
                    }else{
                        $min=str_replace(" ", "s", $min);
                        $min=str_replace(":", "x", $min);
                        $max=str_replace(" ", "s", $max);
                        $max=str_replace(":", "x", $max);
                        if(strpos($min, "x")!=false||strpos($max, "x")!=false){
                            //datetime
                            $ret[]='custom[dateTime]';
                        }else{
                        //date
                            $ret[]='custom[date]';
                        }
                        $ret[]="future[{$min}]";
                        $ret[]="past[{$max}]";
                    }
                    break;
                case "ip_allow":
                    break;
                case "ip_deny":
                    break;
                case "unique":
                    //thinkphp unique have some problem, not work;
                    $ret[]="ajax[checkFieldByUnique]";
                    break;
            }
        }
        return array_unique($ret);
    }

    /**
     * 验证数据是否唯一
     * @param   $name   要验证那个字段
     * @param   $data   要验证的数据，就是where条件
     * @param   $type   INSERT、UPDATE、BOTH
     * */
    public function checkUnique($name, $data, $type){
        $ret    = true;
        foreach($this->_validate as $valid){
            $when   = isset($valid[5])?$valid[5]:self::MODEL_BOTH;
            if($valid[0]==$name && ($type & $when)>0){
                $ret    = $this->_validationFieldItem($data, $valid);
                if(!$ret){
                    $this->error    = $valid[2];
                    break;
                }
            }
        }
        return $ret;
    }
    /**
     * 根据创建文件名称
     * 属性：
     * default:字段默认值
     * pk     ：true 该字段为主键，递增。
     * type   : 字段的类型，包括：（int,varchar,canton,upload,date,datetime,y-m,y)
     * size   : 字段的长度。
     */
    public function fnCreateTable(){
        $thModel = new Model();
        $tszTableName        = parse_name($this->name);
        //组织字符串
        //$tszCreateTableSql    = sprintf('create table if not exists  %s (',$tszTableName);
        $thFieldList        = $this->listFields;
        foreach ($thFieldList as $key=>$thFieldInfo){
            $tszFieldName        = $key;
            $thTableFieldInfo    =  $this->fnTableFieldInfo($tszFieldName);
            if(!empty($thTableFieldInfo)){
                $tszSize = sprintf('(%s)',$thTableFieldInfo['size']);
                $thFieldSql[$tszFieldName]        = sprintf('`%s` %s %s comment "%s"',$tszFieldName,$thTableFieldInfo['type'],empty($thTableFieldInfo['size'])?"":$tszSize,$thTableFieldInfo['comment']);
                if(!empty($thFieldInfo['default'])){
                    $thFieldSql[$tszFieldName]    = sprintf('%s default "%s"',$thFieldSql[$tszFieldName],$thFieldInfo['default']);
                }
                if($thFieldInfo['pk']){
                    $thFieldSql[$tszFieldName]    = sprintf('%s AUTO_INCREMENT,PRIMARY KEY (`%s`)',$thFieldSql[$tszFieldName],$tszFieldName);
                }
            }
        }
        $tszCreateTableSql    = sprintf('create table if not exists  %s ( %s)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT="%s"',$tszTableName,implode($thFieldSql, ','),$this->modelInfo['title']);
        $thModel->query($tszCreateTableSql);
    }
    /**
     * @param string $field_name 类型
     */
    public function fnTableFieldInfo($field_name){
        $tszFieldName        = $field_name;
        if(empty($tszFieldName)||!is_string($tszFieldName)){
            return false;
        }else{
            if($thFieldInfo    = $this->listFields[$tszFieldName]){
                if(empty($thFieldInfo['type'])){
                    $thFieldInfo['type']    = 'string';
                }elseif ($thFieldInfo['type']=='float' && empty($thFieldInfo['size'])){
                    $thFieldInfo['size']    = "9,2";
                }
                if($thFieldInfo['pk']){
                    $thFieldInfo['type']    ='int';
                }
                //默认情况下按照
                switch ($thFieldInfo['type']) {
                    case 'canton':
                        $tszType     = 'varchar';
                        $tiTsize     = '45';
                        break;
                    case 'date' :
                    case 'y_m' :
                    case 'datetime':
                        $tszType     = 'datetime';
                        break;
                    case 'uploadFile':
                        $tszType     = 'varchar';
                        $tiTsize     = 1000;
                        break;
                    case 'y':
                        $tszType     = 'year';
                        break;
                    case 'int':
                        $tszType     = 'int';
                        $tiTsize     = ($thFieldInfo['size'])?$thFieldInfo['size']:11;
                        break;
                    case 'select':
                        $tszType     = 'int';
                        $tiTsize     = 4;
                        break;
                    case 'enum' :
                    case 'select':
                        $valChange   = $thFieldInfo['valChange'];
                        //如果值根据Model转换，或者转换的值中含有中文字符，那么不作为枚举型存在，否则用枚举性处理
                        if(array_key_exists('model',$valChange)||preg_match("/([\x81-\xfe][\x40-\xfe])/",implode('', $valChange))){
                            $tszType ='int';
                            $tiTsize = ($thFieldInfo['size'])?$thFieldInfo['size']:3;
                        }
                        else {
                            $tszType = sprintf("enum('%s')",implode('\',\'', array_values($thFieldInfo['valChange'])));
                        }
                        break;
                    case 'string' :
                    case 'varchar':
                    case 'password':
                    case "cutPhoto":
                        $tszType    = 'varchar';
                        $tiTsize    = ($thFieldInfo['size'])?$thFieldInfo['size']:245;
                        break;
                    default:
                        $tszType    = $thFieldInfo['type'];//dump($thFieldInfo);dump(($thFieldInfo['size']));
                        $tiTsize    = $thFieldInfo['size'];
                        break;
                }
                $thFieldInfo['type'] = $tszType;
                $thFieldInfo['size'] = $tiTsize;
                $thFieldInfo['comment'] = $thFieldInfo['title'];
                return $thFieldInfo;
            }
            else return false;
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
        }if ($returnArray   == false){
            $dataPowerFieldDelete = implode(" AND ", $dataPowerFieldDelete);
        }
        return $dataPowerFieldDelete;
    }
}

