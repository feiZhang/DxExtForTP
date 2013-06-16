<?php
/**
 * 系统公共方法，包括：登录、注销。
 * 登录提交方法有2中，一种ajax，一种直接form提交
 * **/

/**
 * main_url 存储账号登陆后将转向的主页地址。
 * 实例：
 * 		A+B测试，养老院软件：通常登陆后转向到当前版本，部分用户登陆后转向到新版进行新版试运行
 * */
class DxExtPublicAction extends DxExtCommonAction {
    public function index() {
     	if($this->checkSaveAccount()){
     		$main_url	= session("main_url");
     		if(!empty($main_url))
     			$this->redirect($main_url);
     		else $this->redirect(__ROOT__."/");
     	}else
         	$this->redirect('Public/login');
    }
    public function login() {
    	if($this->checkSaveAccount()){
    		$this->redirect(__ROOT__."/");
    	}
    	session(null);
        $this->assign("clientIp", getenv('REMOTE_ADDR'));
        $date = date('Y-m-d,w');
        list($tempDate, $week) = explode(',', $date);
        $weekArray = array('星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');
        $this->assign("serverDate", $tempDate);
        $this->assign('week', $weekArray[$week]);
        if(!empty($_REQUEST["u"])){
        	$this->assign('username',trim($_REQUEST["u"]));
        }
        $this->display();
    }
	public function verify()
	{
        if (isset($_REQUEST['adv'])) {
            Image::showAdvVerify();
        } else {
            Image::buildImageVerify();
        }
	}
	
	Public function checkLogin()
	{
    $rv = $this->userAuth();
		if($rv["state"]){
			$main_url	= session("main_url");
			if(!empty($main_url)){
				$this->assign("jumpUrl",__APP__.$main_url);
      }else{
				$this->assign("jumpUrl",__ROOT__."/");
			}
			$this->success($rv["msg"]);
		}else{
			$this->assign("jumpUrl",U("Public/login"));
			$this->error($rv["msg"]);
		}
  }
	//检查保存用户登录信息是否有效
    protected function checkSaveAccount(){
    	$cookie_account	= cookie("account");
    	if(!empty($cookie_account)){
    		//保存30天登录信息
    		if(intval(substr(authcode($cookie_account,"DECODE"),15)) > time()-60*24*30){
    			$Account 	= D('Account');
    			$authInfo	= $Account->where(array("save_account"=>$cookie_account))->select();
    			if(sizeof($authInfo)>1){		//验证码重复
    				cookie("account",null);
    				return false;
    			}else{
    				$this->setSession($authInfo[0]);
    				return true;
    			}
    		}else{
    			cookie("account",null);
    			return false;
    		}
    	}
    	return false;
    }
    protected function userAuth()
    {
    	//因为后面要使用Session的验证码，但是还需要在用户登陆时，清空原有的Session信息，所有需要变量传递
		$verify	= $_SESSION['verify'];
        $_SESSION['verify']	= "";
        if((C('TEST_USERNAME')=="" || !C("TEST_USERNAME")) && $verify != md5($_REQUEST['seccode'])) {
			return array("state"=>false,"msg"=>'验证码错误！');
		}
		
		import ( 'ORG.RBAC' );
		/* 前台用户可以使用多方式登陆，后台暂设只能使用login_name登陆 */
		switch($_REQUEST["login_type"]){
			case "no":
				$where['id']    		= trim($_REQUEST['username']);
				break;
			case "email":
				$where['email']    		= trim($_REQUEST['username']);
				break;
			case "tel":
				$where['tel']    		= trim($_REQUEST['username']);
				break;
			default:
				$where['login_username']= trim($_REQUEST['username']);
				break;
		}

		$Account 	= D('Account');
		$authInfo	= $Account->where($where)->select();
        //使用用户名、密码和状态的方式进行认证
        if(false == $authInfo) {
            return array("state"=>false,"msg"=>'帐号不存在或已禁用！');
        }else if(sizeof($authInfo)>1){
            return array("state"=>false,"msg"=>'帐号异常，请与管理员联系！'.C("MANAGER_INFO"));
        }else{
            $authInfo   = $authInfo[0];
            if(C("LOGIN_MD5")){		//密码验证方式不同。
            	$inputPass	= md5(trim($_REQUEST['password']));
            	$dbPass		= $authInfo["login_pwd"];
            }else{
            	$inputPass	= trim($_REQUEST["password"]);
            	$dbPass		= DxFunction::authcode($authInfo['login_pwd'], 'DECODE');
            }
                        
            if($authInfo['status'] != 1)
            	return array("state" => false, "msg" => "帐号异常!处于非正常状态,请与管理员联系!".C("MANAGER_INFO"));
            elseif((C('TEST_USERNAME')=="" || !C("TEST_USERNAME")) && $inputPass!=$dbPass) {
                return array("state"=>false,"msg"=>'密码错误！');
            }
            
            if($_REQUEST["saveMyAccount"]=="save"){
            	$authInfo["save_account"]	= authcode(substr(session_id(),0,10).mt_rand(10000,99999).time(), 'ENCODE');
            	cookie("account",$authInfo["save_account"]);
            }
            $this->setSession($authInfo);
            $log_id =	$this->writeActionLog();

            return array("state"=>true,"msg"=>'欢迎['.$authInfo[C("LOGIN_USER_NICK_NAME")].']登录本系统！');
		}
	}
	
	protected function setSession($user){
		session(C('USER_AUTH_KEY'), $user['id']);
		session('login_name', $user['login_username']);
		session('true_name', $user['true_name']);
		session('role_id', $user['role_id']);
		session('canton_id', $user['canton_id']);
		session('canton_fdn', $user['canton_fdn']);
		session('user_type', $user['user_type']);
		session(C("LOGIN_USER_NICK_NAME"), $user[C("LOGIN_USER_NICK_NAME")]);
		if($user['user_type']=="admin") session('DP_ADMIN', true);
		session("main_url",$user["main_url"]);

		DxFunction::getModuleActionForMe();
		
		//数据权限功能。
        foreach(C('DP_PWOER_FIELDS') as $dp_fields){
			if(array_key_exists("session_field",$dp_fields)) $field_name 	= $dp_fields["session_field"];
            else $field_name         = $dp_fields["name"];
            if($dp_fields["isWhere"] && array_key_exists($field_name,$user)){
                session($field_name,$user[$field_name]);
            }
        }
	}
	
	public function logout()
	{
		session(null);
		cookie("account",null);
		if(isset($_REQUEST["ajax"])){
	        $this->ajaxReturn(0,"注销成功",1);
		}else{
    		$this->assign("jumpUrl",U("Public/login"));
    		$this->success('注销成功!');		    
		}
	}
}
?>
