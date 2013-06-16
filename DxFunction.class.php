<?php
class DxFunction{
    
	/**
	 * 获取一个当前日期
	 * */
	function getMySqlNow(){
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
		$to_path	= C("UPLOAD_BASE_PATH").$base_path."/";
		
		if(substr($sub_path,0,4)=="date"){
			$sub_path	= "/".date(substr($sub_path,4),time());
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
						deleteDir($file);//递归执行
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
	//		$filename=iconv('utf-8', 'gbk', $filename);//防止文件名存储时乱码
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
	 * @param	$no_array		存放no的数组，
	 * @param	$yes_array		存放yes的数组，
	 * @return					true:确认不验证		false:确认需验证
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
	 * @param	$ignore		是否忽略缓存，在登录时，要强制获取新的权限信息，所以需要忽略之
	 * */
	function getModuleActionForMe($ignore=false){
		$allAction	= S("Cache_module_action_ALL");
		$menuM	= null;
		if(empty($allAction)){
			$menuM			= D('Menu');
			//if($menuM instanceof Model) return array();    //如果没有自定义Menu的Model，则表示没有启用此功能。
			$allAction		= array();
			$action_list	= $menuM->getAllAction();
			foreach($action_list as $l){
				if(!empty($l["module_name"]) && !empty($l["action_name"]))
					$allAction[$l["module_name"]][$l["action_name"]][$l[$menuM->getPk()]]	= array("args"=>$l["args"],"menu_name"=>$l["menu_name"]);
			}
			S("Cache_module_action_ALL",json_encode($allAction));
		}else{
			$allAction	= json_decode($allAction,true);
		}
		
		$my_id			= session(C('USER_AUTH_KEY'));
		if(intval($my_id)<1) return array("allAction"=>$allAction);
	
		if(!$ignore) $myAction	= S("Cache_module_action_".$my_id);
		if(!$ignore && !empty($myAction)){
			$myAction	= json_decode($myAction,true);
			return array("allAction"=>$allAction,"myAction"=>$myAction);
		}
	
		if(empty($menuM)) $menuM = D('Menu');
		$action_list  	= $menuM->getMyAction();
		$myAction		= array();
		foreach($action_list as $l){
			if(!empty($l["module_name"]) && !empty($l["action_name"]))
				$myAction[$l["module_name"]][$l["action_name"]][$l[$menuM->getPk()]]	= array("args"=>$l["args"],"menu_name"=>$l["menu_name"]);
		}
	
		S("Cache_module_action_".$my_id,json_encode($myAction),3600);
		return array("allAction"=>$allAction,"myAction"=>$myAction);
	}
	//检验某个Action是否有权限。。用于在页面验证URL是否可以显示
	function judge_action_priv($module_name="",$action_name=""){
		$cacheAction = getModuleActionForMe();
		if(empty($cacheAction)) return false;	//不通过
		$thisModule	= empty($module_name)?MODULE_NAME:$module_name;
		$thisAction	= empty($action_name)?ACTION_NAME:$action_name;
		//dump($thisModule);dump($thisAction);dump($cacheAction["myAction"][$thisModule][$thisAction]);
		if(empty($cacheAction["myAction"][$thisModule][$thisAction])){
			if(empty($cacheAction["allAction"][$thisModule][$thisAction]))
				return true;	//未定义的Action，默认都有权限操作
			else{
				return false;
			}
		}else
			return true;
	}
	/**
	 * 权限验证的辅助功能，用于处理Module、Action相同，但是args不同的权限验证。
	 * 此函数只能判断简单的参数信息，复杂的参数无法正确判断，比如：
	 * args	分别为：a=1		a=1&b=2		a=1&b=2&c=3
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
		$defaultName	= "";
		foreach($actions as $action){
			//如果没有找到与参数匹配的action，则使用args为空的action
			if(empty($action["args"])){
				$defaultName = $action["menu_name"];
				continue;
			}
			$args	= explode("&",str_replace("?","",$action["args"]));
			$check	= false;
			foreach($args as $tv){
				$tv	= explode("=",$tv);
				if($request[$tv[0]]==$tv[1]){
					$check	= true;
				}else{
					$check	= false;
				}
			}
			if($check){
				return $action["menu_name"];
			}
		}
		return $defaultName;
	}
	
	/**
	 * 将上传的多文件字符串json解析，以适用于grid展示
	 * */
	function uploadFilesToGrid($dataList,$fieldName){
		foreach($dataList as $kk=>$vv){
			$t	= array();
			$fs	= json_decode($vv[$fieldName],true);
			foreach($fs as $file){
				$t[]	= sprintf("<a href='%s/Basic/download?f=%s&n=%s' download='%s' target='download'>%s</a>",__ROOT__,urlencode($file["url"]),urlencode($file["real_name"]),htmlentities($file["real_name"],ENT_QUOTES,'UTF-8'),htmlentities($file["real_name"],ENT_QUOTES,'UTF-8'));
			}
			if(sizeof($t)==1){
				$dataList[$kk][$fieldName]	= $t[0];
			}else
				$dataList[$kk][$fieldName]	= implode("<br \>",$t);
		}
	}
	function fdnToFullName($dataList,$fieldName){
		$canton	= D("Canton")->getCacheDictTableData();
		foreach($dataList as $kk=>$vv){
			$dataList[$kk][$fieldName]	= $canton[$vv[$fieldName]];
		}
	}
	
	
	/**
	 * 根据字段定义生成字段的修改输入框，for  data_edit.html
	 * 1.因为诸如，责任护理员等，每个用户不同内容的字典表存在，所以并不适合直接将file_enum输出成值。。公共字典变动等情况也影响这个功能。。所以，还是将字典表，输出为变量
	 * */
	 function getFieldInput($field,$valid=array(),$field_content=false,$ignoreEditor=false){
		$field_name	= $field["name"];
		if($field_content===false) $field_content	= "\$objectData['".$field_name."']";
		if($ignoreEditor || empty($field["editor"])){
			switch($field["type"]){
				case "password":
					$fieldInput = sprintf("<input name='%s' type='password' value='' style='width:%spx' class=\"itemAddInput %s\" />",$field_name,$field["width"],$valid[$field_name]);
					$fieldInput .= sprintf("<input name='%s' value=\"{\$objectData.%s|htmlentities=###,ENT_QUOTES,'UTF-8'}\" type='hidden' />",$field_name,$field_name);
					break;
				case "enum":
          $fieldInput = sprintf('{:DxFunction::W_FIELD("FormEnum", array("name"=>"%1$s","allowdefault"=>empty($pkId[1]),"validclass"=>$valid["%1$s"],"custom_class"=>"itemAddRadio","value"=>$objectData["%1$s"],"fieldSet"=>$listFields["%1$s"]))}', $field_name);
          break;
				case "set":
          $fieldInput = sprintf('{:DxFunction::W_FIELD("FormCheck", array("name"=>"%1$s","allowdefault"=>empty($pkId[1]),"validclass"=>$valid["%1$s"],"custom_class"=>"itemAddRadio","value"=>$objectData["%1$s"],"fieldSet"=>$listFields["%1$s"]))}', $field_name);
					break;
				case "select":
          $fieldInput = sprintf('{:DxFunction::W_FIELD("FormSelect", array("name"=>"%1$s", "allowdefault"=>empty($pkId[1]), "validclass"=>$valid["%1$s"], "custom_class"=>"itemAddSelect", "value"=>$objectData["%1$s"], "fieldSet"=>$listFields["%1$s"]))}', $field_name);
					break;
				case 'date':
				    $dateFormat    = "yyyy-MM-dd";
				case 'y_m':
				    $dateFormat    = "yyyy-MM";
				case 'time':
				    $dateFormat    = "HH:mm:ss";
				case 'datetime':
				    $dateFormat    = "yyyy-MM-dd HH:mm:ss";    //itemAddDateTime
				    $fieldInput = sprintf('{:DxFunction::W_FIELD("Date", array("name"=>"%1$s", "fieldSet"=>$listFields["%1$s"], format=>"%2$s", "allowdefault"=>empty($pkId[1]),"validclass"=>$valid["%1$s"],"value"=>$objectData["%1$s"]))}', $field_name,$dateFormat);
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
		}else{
			$fieldInput = $field["editor"];
		}
		$req	= strpos($valid[$field_name], 'required')===false?'':'<span class="field_required">*</span>';
		return $fieldInput.$req;
	}
	// 为了将所有的字段widget放到同一个目录下方便引入。。增加此函数
	function W_FIELD($name, $data=array(), $return=false) {
		$class = $name . 'Widget';
		require_cache(dirname(__FILE__) . '/DxWidget/' . $class . '.class.php');
		if (!class_exists($class))
			throw_exception(L('_CLASS_NOT_EXIST_') . ':' . $class);
		$widget = Think::instance($class);
		$content = $widget->render($data);
		if ($return)
			return $content;
		else
			echo $content;
	}
	
	/**
	 * 新增个人消息
	 * @parem $title 消息标题
	 * @param $content 消息内容
	 * @param $type 	消息类型(2：表示系统公告（发给多有的人）；3：部门公告（只发给本部门的人），其他类型，则需要$reveive_id 有值）
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
}	
