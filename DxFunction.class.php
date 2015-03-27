<?php
class DxFunction{
    /**escape json data, prevent from thinkphp template convert*/
    function escapeJson($data){
        $ret=str_replace("{","{ ", json_encode($data));
        return $ret;
    }
    /**
     * 获取一个当前日期
     * */
    static function getMySqlNow(){
        return date("Y-m-d H:i:s");
    }
    
    /**
     * 此方法用于过滤html属性值.
     * @param String $val 需要过滤的的值
     * @return String 处理过的文本内容.
     */
    function escapeHtmlValue($val){
        $val    = htmlentities($val, ENT_QUOTES,"UTF-8");
        return $val;
    }
    
    /**
     * filter array null value
     */
    function filter_notnull($v){
        return($v!==NULL);
    }
    
    /**
     * 格式化html数据为可显示数据
     * */
    function dhtml($string) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = dhtml($val);
            }
        } else {
            $string = str_replace(array('"', '\'', '<', '>', "\t", "\r", '{', '}'), array('&quot;', '&#39;', '&lt;', '&gt;', '&nbsp;&nbsp;', '', '&#123;', '&#125;'), $string);
        }
        return $string;
    }
    
    /**
     * 移动文件到新目录中,,公共函数
     * */
    function move_file($orig_file,$base_path,$sub_path="",$newFile=""){
        if($base_path[0] != "/") $base_path = "/".$base_path;
        $to_path    = C("UPLOAD_BASE_PATH").$base_path."/";
        if(!file_exists(C("UPLOAD_BASE_PATH"))){
            mkdir(C("UPLOAD_BASE_PATH"));
        }
        if(empty($sub_path)) $sub_path = "dateYmd";
        if(substr($sub_path,0,4)=="date"){
            $sub_path   = "/".date(substr($sub_path,4),time());
        }
        if(!file_exists($to_path)){
            mkdir($to_path);
        }
        if(!file_exists($to_path.$sub_path)){
            mkdir($to_path.$sub_path);
        }
    
        if(empty($newFile)) $newFile = basename($orig_file);
        if(copy($orig_file,$to_path.$sub_path."/".$newFile)){
            unlink($orig_file);
        }
        return $base_path.$sub_path."/".$newFile;
    }
    
    /**
     * 删除非空目录
     * */
    function deleteDir($dirname,$delMySelf=true){
        if(!is_dir($dirname)){
            return false;
        }
        if(file_exists($dirname)){
            $dir = opendir($dirname);
            while ($dir_file = readdir($dir)){
                if($dir_file != "." && $dir_file !=".."){
                    $file = $dirname.'/'.$dir_file;
                    if(is_dir($file)){
                        DxFunction::deleteDir($file);//递归执行
                    }else{
                        unlink($file);
                    }
                }
            }
            closedir($dir);
            if($delMySelf) rmdir($dirname);
        }
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $string      为需加、解密字符串
     * @param unknown_type $operation   "DECODE"时解密,"ENCODE"表示加密
     * @param unknown_type $key         密匙
     * @param unknown_type $expiry      密文有效期  
     * @return unknown
     */
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙    
        $ckey_length = 4;
        // 密匙    
        //$key = md5($key ? $key : $GLOBALS['discuz_auth_key']);    
        $key = md5($key ? $key : '8888');
        //$key = md5($key ? $key : md5($_SERVER['HTTP_USER_AGENT']));
        // 密匙a会参与加解密    
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证    
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文    
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙    
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性    
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确   
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        // 产生密匙簿    
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度    
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分    
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符    
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            // substr($result, 0, 10) == 0 验证数据有效性    
            // substr($result, 0, 10) - time() > 0 验证数据有效性    
            // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性    
            // 验证数据有效性，请看未加密明文的格式 
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因    
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码    
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
    // 根据浏览器类型决定文件名转换成什么编码，，，ie只能用gbk编码的文件名，firefox支持utf8
    function get_filename_bybrowser($filename){
        $my_broswer=$_SERVER['HTTP_USER_AGENT'];
        if(!preg_match("/Firefo|Chrome|Opera|Safari/", $my_broswer)){
            $filename = urlencode($filename);
            $filename = str_replace("+", "%20", $filename);
    //      $filename=iconv('utf-8', 'gbk', $filename);//防止文件名存储时乱码
        }else{
            $filename=  sprintf("\"%s\"", $filename);
        }
        return $filename;
    }
    
    
    //下面函数的一个实际应用，判定action是否为不验证权限action
    function checkNotAuth($no_action,$yes_action=array()){
        return DxFunction::checkInNotArray($no_action,$yes_action,MODULE_NAME,ACTION_NAME);
    }
    /**
     * 判断是否存放在not数组中，并且未存放在yes数组中。
     * 用户判定一个数据的设定值。
     * 1.只要存放在yes数组中，则全部方位 false
     * 2.仅仅存放在not数组中才返回true
     * 3.函数支持2个数组维度
     * 比如：
     * 1.判定Action是否允许不验证权限
     * 2.判定Model是否不进行数据权限验证
     * @param   $no_array       存放no的数组，
     * @param   $yes_array      存放yes的数组，
     * @return                  true:确认不验证     false:确认需验证
     * **/
    function checkInNotArray($no_array,$yes_array=array(),$first="",$secord="")
    {
        if(empty($no_array)) return false;
        //如果整个模块都设置为不需验证。
        if(isset($no_array[$first]) && !is_array($no_array[$first])){
            if(!empty($yes_array)){
                return checkYesArray($yes_array);
            }else
                return true;
        }else{
            if(isset($no_array[$first][$secord])){
                if(!empty($yes_array)){
                    return checkYesArray($yes_array);
                }else
                    return true;
            }else
                return false;
        }
        return false;
    }
    function checkYesArray($yes_array,$first="",$secord=""){
        //如果此模块设置为需强制验证
        if(isset($yes_array[$first]))
            if(is_array($yes_array[$first])){
            //同时 Action 设置强制验证
                if(isset($yes_array[$first][$secord])){
                    return false;
                }else
                    return true;
            }else
                return false;
        else
            return false;
    }
    
    /**
     * 获取登录账号的权限列表。并缓存之。
     * 1.所有菜单列表
     * 2.登录账号有权限访问的菜单列表
     * 系统会出现，Module、Action相同，但是args不相同的的菜单。
     * @param   $ignore     是否忽略缓存，在登录时，要强制获取新的权限信息，所以需要忽略之
     * */
    function getModuleActionForMe($ignore=false){
        $allAction  = S("Cache_module_action_ALL");
        $menuM  = null;
        if(empty($allAction)){
            $menuM          = D('Menu');
            //if($menuM instanceof Model) return array();    //如果没有自定义Menu的Model，则表示没有启用此功能。
            $allAction      = array();
            $action_list    = $menuM->getAllAction();
            foreach($action_list as $l){
                if(!empty($l["module_name"]) && !empty($l["action_name"])){
                    //如果系统的菜单是引用一个目录内的项目，则会有项目的url前缀，将其去掉
                    $modelName = explode("/",$l["module_name"]);
                    $modelName = $modelName[sizeof($modelName)-1];
                    $allAction[$modelName][$l["action_name"]][$l[$menuM->getPk()]]   = array("args"=>$l["args"],"menu_name"=>$l["menu_name"]);
                }
            }
            S("Cache_module_action_ALL",json_encode($allAction));
        }else{
            $allAction  = json_decode($allAction,true);
        }

        $my_id          = session(C('USER_AUTH_KEY'));
        if(intval($my_id)<1) return array("allAction"=>$allAction);

        if(!$ignore) $myAction  = S("Cache_module_action_".$my_id);
        if(!$ignore && !empty($myAction)){
            $myAction   = json_decode($myAction,true);
            return array("allAction"=>$allAction,"myAction"=>$myAction);
        }

        if(empty($menuM)) $menuM = D('Menu');
        $action_list    = $menuM->getMyAction();

        $myAction       = array();
        foreach($action_list as $l){
            if(!empty($l["module_name"]) && !empty($l["action_name"])){
                $modelName = explode("/",$l["module_name"]);
                $modelName = $modelName[sizeof($modelName)-1];
                $myAction[$modelName][$l["action_name"]][$l[$menuM->getPk()]]    = array("args"=>$l["args"],"menu_name"=>$l["menu_name"]);
            }
        }

        S("Cache_module_action_".$my_id,json_encode($myAction),3600);
        return array("allAction"=>$allAction,"myAction"=>$myAction);
    }
    //检验某个Action是否有权限。。用于在页面验证URL是否可以显示
    function judge_action_priv($module_name="",$action_name=""){
        $cacheAction = getModuleActionForMe();
        if(empty($cacheAction)) return false;   //不通过
        $thisModule = empty($module_name)?MODULE_NAME:$module_name;
        $thisAction = empty($action_name)?ACTION_NAME:$action_name;
        //dump($thisModule);dump($thisAction);dump($cacheAction["myAction"][$thisModule][$thisAction]);
        if(empty($cacheAction["myAction"][$thisModule][$thisAction])){
            if(empty($cacheAction["allAction"][$thisModule][$thisAction]))
                return true;    //未定义的Action，默认都有权限操作
            else{
                return false;
            }
        }else
            return true;
    }
    
    /**
     * 权限验证的辅助功能，用于处理Module、Action相同，但是args不同的权限验证。
     * 此函数只能判断简单的参数信息，复杂的参数无法正确判断，比如：
     * args 分别为：a=1     a=1&b=2     a=1&b=2&c=3
     * 则函数在判断到a=1时认为完全匹配，则返回。
     * */
    function argsInRequest($actions,$request){
        unset($request["_URL_"]);
        if(empty($request)){
            foreach($actions as $action){
                if(empty($action["args"])) return $action["menu_name"];
            }
            return false;
        }
        $defaultName    = "";
        foreach($actions as $action){
            //如果没有找到与参数匹配的action，则使用args为空的action
            if(empty($action["args"])){
                $defaultName = $action["menu_name"];
                continue;
            }
            $args   = explode("&",str_replace("?","",$action["args"]));
            $check  = false;
            foreach($args as $tv){
                $tv = explode("=",$tv);
                if($request[$tv[0]]==$tv[1]){
                    $check  = true;
                }else{
                    $check  = false;
                }
            }
            if($check){
                return $action["menu_name"];
            }
        }
        return $defaultName;
    }
    
    /**
     * 将data数据转换grid显示的数据
     * 1.将上传的多文件字符串json解析，以适用于grid展示
     * 2.将fdn转换为字符串，不需要valChange传递过的字符到客户端,,canton的数据量太大，传递到客户端需要较大的资源
     */
    function uploadFilesToGrid(&$dataList,$fieldName){
        foreach($dataList as $kk=>$vv){
            $t  = array();
            $fs = json_decode($vv[$fieldName],true);
            foreach($fs as $file){
                $t[]    = sprintf("<a href='%s/Basic/download?f=%s&n=%s' download='%s' target='download'>%s</a>",__ROOT__,urlencode($file["url"]),urlencode($file["real_name"]),htmlentities($file["real_name"],ENT_QUOTES,'UTF-8'),htmlentities($file["real_name"],ENT_QUOTES,'UTF-8'));
            }
            if(sizeof($t)==1){
                $dataList[$kk][$fieldName]  = $t[0];
            }else
                $dataList[$kk][$fieldName]  = implode("<br \>",$t);
        }
    }
    function cantonFdnToText(&$dataList,$fieldName){
        $canton = D("Canton")->getCacheDictTableData();
        foreach($dataList as $kk=>$vv){
            $dataList[$kk][$fieldName]  = $canton[$vv[$fieldName]];
        }
    }
    function myAccountCantonName($cantonFdn){
        if(empty($cantonFdn)) $cantonFdn = $_SESSION["canton_fdn"];
        $canton = D("Canton")->getCacheDictTableData();
        return $canton[$cantonFdn];
    }
    
            /*
            switch($field["type"]){
                case "password":
                    $fieldInput = sprintf("<input name='%s' type='password' value='' style='width:%spx' class=\"itemAddInput\" />",$field_name,$field["width"]);
                    break;
                case 'date':
                    $fieldInput = sprintf('{:DxFunction::W_FIELD("Date", array("name"=>"%1$s", "fieldSet"=>$listFields["%1$s"], "allowdefault"=>empty($pkId[1]),"validclass"=>$valid["%1$s"],"value"=>$objectData["%1$s"]))}', $field_name);
                    break;
                case "canton":
                    $fieldInput = sprintf('{:DxFunction::W_FIELD("Canton", array("name"=>"%1$s", "fieldSet"=>$listFields["%1$s"], "validclass"=>$valid["%1$s"], "value"=>$objectData["%1$s"]))}', $field_name);
                    break;
                case "uploadFile":
                    $fieldInput = sprintf('{:DxFunction::W_FIELD("UploadFile", array("name"=>"%1$s", "fieldSet"=>$listFields["%1$s"], "validclass"=>$valid["%1$s"], "value"=>$objectData["%1$s"]))}', $field_name);
                    break;
                case "cutPhoto":
                    $fieldInput = sprintf('{:DxFunction::W_FIELD("CutPhoto", array("name"=>"%1$s", "fieldSet"=>$listFields["%1$s"], "allowdefault"=>empty($pkId[1]), "validclass"=>$valid["%1$s"], "value"=>$objectData["%1$s"]))}', $field_name);
                    break;
                case 'string':
                default:
                    $fieldInput = sprintf('{:DxFunction::W_FIELD("String", array("name"=>"%1$s", "fieldSet"=>$listFields["%1$s"],"allowdefault"=>empty($pkId[1]), "validclass"=>$valid["%1$s"], "custom_class"=>"itemAddText", "value"=>$objectData["%1$s"]))}', $field_name);
                    break;
            }
             */
    //代码来源于Widget的randFile方法。
    function createFieldInput($fieldSet,$defaultVal){
        // $tplFile = dirname(__FILE__) . "/DxFieldInput.class.php";
        // include_once($tplFile);
        return DxFieldInput::create($fieldSet,$defaultVal);
    }

    /**
     * 新增个人消息
     * @parem $title 消息标题
     * @param $content 消息内容
     * @param $type     消息类型(2：表示系统公告（发给多有的人）；3：部门公告（只发给本部门的人），其他类型，则需要$reveive_id 有值）
     * @param $deadling 消息到期时间（超过日期不提醒）
     * @parem $receive_id 消息接收人（多个则用“,”隔开）
     * */ 
    function insert_message($title,$content,$type,$deadling="",$receive_id=""){
        $m = D('Message');
        $m->title = $title;
        $m->message_content = $content;
        $m->type = $type;
        $m->create_time = date('Y-m-d');
        $m->create_user_id = session(C('USER_AUTH_KEY'));
        if(!empty($deadling)){
            $m->deadling = $deadling;
        }
        $msg_id = $m->add();
        $canton_fdn = session('_cantonfdn');
        if($msg_id){
            $uhm = D('UserHasMessage');
            switch ($type) {
                case 1:
                    //生日提醒
                    break;
                case 2;
                    //系统公告
                    $uhm->query('insert into user_has_message(message_id,receive_user,state)  select '.$msg_id.',id,1 from account where canton_fdn like "'.$canton_fdn.'%" AND id != "'.session(C('USER_AUTH_KEY')).'"');
                    break;
                case 3;
                    //部门公告只公布给本部门的人
                    $uhm->query('insert into user_has_message(message_id,receive_user,state)  select '.$msg_id.',id,1 from account where canton_fdn = "'.$canton_fdn.'" AND id != "'.session(C('USER_AUTH_KEY')).'"');
                    default:
                    //发给指定人
                    if(empty($receive_id )){
                        return false;
                    }else{
                        $account_list = explode(',',$receive_id);
                        foreach($account_list as $value){
                            $uhm->message_id = $msg_id;
                            $uhm->receice_user = $value;
                            $uhm->state = 1;
                            $uhm->add();
                        } 
                    }
                    break;
            }
            return true;
        }else{
            return false;
        }
    }
    /*
     * 已经阅读信息,更改状态为已读状态。
     * @param $msg_id
     */
    function read_messag($msg_id){
        $m = D('UserHasMessage');
        $m->where(array('message_id'=>$msg_id,'receive_user'=>session(C("USER_AUTH_KEY"))))->save(array('receive_time'=>date("Y-m-d H:i:s"),'state'=>2));
        return true;
    }
    /*
     * 删除信息(个人信息表中的数据一律删除
     *  @param $msg_id
     */
    function delete_messag($msg_id){
        $m = D('UserHasMessage');
        D('Message')->where(array('id'=>$msg_id))->delete();
        $m->where(array('message_id'=>$msg_id))->delete();
        return true;
    }
    
    /**
     * 将数据库返回数组转换为valChange类型数组
     * 即数组列转行
     * 1 2 3 4
     * 5 6 7 8
     * 变换为 array(1=>array(2=>array(3=>array(4))))
     */
    function arrayToArray($tV){
        $rv = array();
        if(empty($tV) || !is_array($tV)){
           return $v;
        }
        
        $keys = array_keys($tV[0]);
        $valStr = $keys[sizeof($keys)-1];
        unset($keys[sizeof($keys)-1]);
        $keyStr = "[\$tt['".implode("']][\$tt['",$keys)."']]";
        $vvv = sprintf("\$rv%s=\$tt['%s'];",$keyStr,$valStr);
        foreach($tV as $tt){
            eval($vvv);
        }
        return $rv;
    }

	/**
     *
     * 将小写的数字转化为大写
     * @param unknown_type $num
     */
    function changeCapitalNum($num){
        $d = array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖');
        $e = array('元','拾','佰','仟','万','拾','佰','仟','亿','拾','佰','仟','万亿');
        $p = array('分','角');
        $zheng='整'; //追加"整"字
        $final = array(); //结果
        $inwan=0; //是否有万
        $inyi=0; //是否有亿
        $len_pointdigit=0; //小数点后长度
        $y=0;
        if($c = strpos($num, '.')) //有小数点,$c为小数点前有几位数
        {
            $len_pointdigit = strlen($num)-strpos($num, '.')-1; // 判断小数点后有几位数
            if($c>13) //简单的错误处理
            {
                echo "数额太大,已经超出万亿.";
                die();
            }
            elseif($len_pointdigit>2) //$len_pointdigit小数点后有几位
            {
                echo "小数点后只支持2位.";
                die();
            }
        }
        else //无小数点
        {
            $c = strlen($num);
            $zheng = '整';
        }
        for($i=0;$i<$c;$i++) //处理整数部分
        {
            $bit_num = substr($num, $i, 1); //逐字读取 左->右

            if($bit_num==0 && $i==$c-5){
                //106783  时应该为一十万，而不是一十零万
            }else if($bit_num!=0 || substr($num, $i+1, 1)!=0) //当前是零 下一位还是零的话 就不显示
                @$low2chinses = $low2chinses.$d[$bit_num];
            if($bit_num || $i==$c-1)
                @$low2chinses = $low2chinses.$e[$c-$i-1];
            else if($bit_num==0 && $i==$c-5)    //万位是0的时候，必须填上万
                @$low2chinses = $low2chinses.$e[$c-$i-1];
        }
        for($j=$len_pointdigit; $j>=1; $j--) //处理小数部分
        {
            $point_num = substr($num, strlen($num)-$j, 1); //逐字读取 左->右
            if($point_num != 0)
                @$low2chinses = $low2chinses.$d[$point_num].$p[$j-1];
            //if(substr($num, strlen($num)-2, 1)==0 && substr($num, strlen($num)-1, 1)==0) //小数点后两位都是0
        }
        $chinses = str_split($low2chinses,2); //字符串转换成数组
        //print_r($chinses);
        for($x=sizeof($chinses)-1;$x>=0;$x--) //过滤无效的信息
        {
            if($inwan==0&&$chinses[$x]==$e[4]) //过滤重复的"万"
            {
                $final[$y++] = $chinses[$x];
                $inwan=1;
            }
            if($inyi==0&&$chinses[$x]==$e[8]) //过滤重复的"亿"
            {
                $final[$y++] = $chinses[$x];
                $inyi=1;
                $inwan=0;
            }
            if($chinses[$x]!=$e[4]&&$chinses[$x]!=$e[8]) //进行整理,将最后的值赋予$final数组
                $final[$y++] = $chinses[$x];
        }
        $newstring=(array_reverse($final)); //$final为倒数组，$newstring为正常可以使用的数组
        $nstring=join($newstring); //数组变成字符串
        if(substr($num,-2,1)==0 && substr($num,-1)<>0) //判断原金额角位为0 ? 分位不为0 ?
        {
            $nstring=substr($nstring,0,(strlen($nstring)-4))."零".substr($nstring,-4,4); //这样加一个零字
        }
        $fen="分";
        $fj=substr_count($nstring, $fen); //如果没有查到分这个字
        return $nstring=($fj==0)?$nstring.$zheng:$nstring; //就将"整"加到后面
    }
}	
   
