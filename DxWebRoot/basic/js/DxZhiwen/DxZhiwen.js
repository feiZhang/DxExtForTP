/**
 * 此插件运行的前提是：jquery支持，指纹采集ocx控件支持
 * 功能：在页面上调用指纹仪，可采集指纹，可对比两次指纹，指纹最终形成一个特征码，一个指纹图形文件，都需要存储备用。
 * 物理运行前提：客户端注册ocx控件，调用此js的页面要引入指纹仪所用使用的ocx<object></object>插件
 * 调用方式：$.DxZhiwen(property);
 * property为json变量{
 * 	'spDeviceType':'指纹机接入设备入口',				//0：usb  1：串口 2 UsbDisk
 * 	'spComPort':'spDeviceType=1时有效，指明串口端口',				//spDeviceType=1必填，其他不填
 * 	'spBaudRate':'spDeviceType=1时有效，指明串口传输波特率',		//spDeviceType=1 必填，其他不填
 * 	'CharLen':'512或1024，默认512',		//可不填
 * }
 */
(function($){
	//定义几个全局变量
	$.DxZhiwen	= function(property){
		var _this = this ; // 把_this保存下来，以后用_this代替this，以免产生歧义
		//公有变量
		_this.spDeviceType	= '0';		//指纹机接入设备入口 0：usb  1：串口 2 UsbDisk
		_this.spComPort 	= '0';					//spDeviceType=1时有效，指明串口端口 spDeviceType=1必填，其他不填
		_this.spBaudRate	= '0';					//spDeviceType=1时有效，指明串口传输波特
		_this.CharLen		= '512';				//消息标题
		_this.ocxId			='fingercode';			//指纹控件的domid
		_this.object        =null;					//指纹控件对象，取得是_this.ocxId对应的对象
		_this.Msg			='';					//类对外的提示信息
		/*构造函数*/
		_this.init		= function(){
			//给消息提示方式，消息内容，标题，宽度，高度，自动关闭时间赋值，如果没有参数，取默认值
			_this.spDeviceType	= property.spDeviceType || _this.spDeviceType;
			_this.spComPort	= property.spComPort || _this.spComPort;
			_this.spBaudRate		= property.spBaudRate || _this.spBaudRate;
			_this.CharLen		= property.CharLen || _this.CharLen;
			_this.ocxId		= property.ocxId || _this.ocxId;
			_this.object	= document.getElementById(_this.ocxId);
			//对控件对象的属性进行赋值
			_this.object.spDeviceType=_this.spDeviceType;
			_this.object.spComPort=_this.spComPort;
			_this.object.spBaudRate=_this.spBaudRate;
			_this.object.CharLen=_this.CharLen;
		};
		/**
		 * 采集指纹函数，采集成功返回特征码，失败返回0
		 * 无论成功，失败 提示消息会存储到_this.object的Msg属性中
		 */
		_this.get_code	= function(){
			var ret=_this.object.ZAZGetImgCode();
			_this.Msg=_this.object.Msg;
			if(ret==0){
				//成功
				return _this.object.FingerCode;
			}
			else{
				//失败
				return 0;
			}
			
		};
		/**
		 * 返回指纹插件的提示信息，正确错误都有提示信息
		 */
		_this.get_msg=function(){
			return _this.object.Msg;
		}
		
		/**
		 * 把当前指纹图片保存到本地
		 * 参数为要保存的图片在本地路径，不设置默认保存到c:\fingerimg.bmp
		 * 返回：0失败，成功返回文件路径
		 */
		_this.save_file=function(filepath){
			filepath=filepath || 'c:\fingerimg.bmp';
			var ret=_this.object.ZAZSaveImg(filepath);//0成功 非0失败
			if(ret!=0){
				//失败
				return 0;
			}
			else{
				return filepath;
			}
		};
		/**
		 * 把本地某个目录的文件上传到ftp
		 * 参数 ：ftp服务器 地址，端口，用户名，密码 要上传的文件路径
		 */
		_this.upload_ftp_file=function(spHost , spPort, spUser, spPsw, spFileName){
			var ret=_this.object.ZAZUpLoadImgUFtp(spHost , spPort, spUser, spPsw, spFileName);
			return ret;
		};
		/**
		 * 比对两个指纹是否一致，参数两个指纹的特征码
		 * 返回相似结果0-100，,50以下认为不是同一个指纹
		 */
		_this.match_code=function(code1,code2){
			var ret=_this.object.ZAZMatch(code1,code2);
			return ret;
		};
		/**
		 * 设置指纹仪的密码，设置了之后在指纹采集时先验证下密码，密码通过才能采集指纹
		 * 参数，旧密码 新密码  无旧密码，默认为00000000
		 * 返回0成功，返回其他 失败
		 */
		_this.set_password=function(oldpassword,newpassword){
			if(newpassword==''){
				alert('请输入要设置密码');
				return 1;
			}
			if(oldpassword==''){
				oldpassword='00000000';
			}
			_this.object.Password=newpassword;
			var ret=_this.object.ZAZSetPwd(oldpassword);
			return ret;
		};
		/**
		 * 校验指纹仪的内置密码和系统指定密码是否一致
		 * 一致返回0，非0不一致，验证失败，指纹仪不可用
		 */
		_this.check_password=function(password){
			if(password==''){
				alert('请输入要校验的密码');
				return 1;
			}
			_this.object.Password=password;
			var ret=_this.object.ZAZVfyPwd();
			return ret;
		};
		
		_this.init();//人为调用函数，使 init 相当于构造函数
		return _this;
	};
})(jQuery);