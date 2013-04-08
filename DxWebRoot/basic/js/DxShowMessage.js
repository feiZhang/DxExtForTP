/**
 * 此插件运行的前提是：jquery支持， artdialog4 弹出框插件支持
 * 功能：在页面上调用处进行消息提示。
 * 方式：消息提示目前可支持两种方式。1 右下角类似qq公告方式弹出 2 页面中间弹出框方式，并可定义多长时间后自动消失。
 * 调用方式：$.DxShowMessage(msg);  或者  var msg=$.DxShowMessage(msg);msg.time=xx;msg.show(); 或 msg.show({"time":"3"})
 * msg为json变量{
 * 	'content':'消息内容',				//消息内容，可为html，唯一必填
 * 	'title':'消息标题',				//可不填
 * 	'method':'rightAndBottom 或 centre',		//method=rightAndBottom 为右下角弹出，method=rightAndBottom为中间弹出
 * 	'time':'0不自动隐藏，其他数值为几秒后自动隐藏消息',		//0表示不自动消失，不为0表示几秒后自动消失。
 * 	'width':消息框宽度，可以是数字，可以是带单位的串如320px,
 * 	'height':消息框高度，同宽度}
 */
(function($){
	//定义几个全局变量
	$.DxShowMessage	= function(msginfo){
		var _this = this ; // 把_this保存下来，以后用_this代替this，以免产生歧义
		//公有变量
		_this.method	= 'rightAndBottom';		//消息表现方式 rightAndBottom:右下角公告，altDialog:中间弹出框
		_this.content	= '';					//消息内容，可以是html，以满足有些消息带链接，可点击
		_this.time		= 3;					//0不自动隐藏，其他数值为几秒后自动隐藏消息
		_this.title		= '系统提示';				//消息标题
		_this.width		= 250;					//消息宽度
		_this.height	= 180;					//消息高度
		/*构造函数*/
		_this.init		= function(){
			//给消息提示方式，消息内容，标题，宽度，高度，自动关闭时间赋值，如果没有参数，取默认值
			//var info		= msginfo || {'method':'1','content':'您有新的消息','time':'0','title':'消息','width':320,'height':240};//没有参数时取默认值
			_this.method	= msginfo.method || _this.method;
			_this.content	= msginfo.content || _this.content;
			_this.time		= msginfo.time || _this.time;
			_this.title		= msginfo.title || _this.title;
			_this.width		= msginfo.width || _this.width;
			_this.height	= msginfo.height || _this.height;
		};
		/**
		 * 弹出消息，根据消息展示方式调用不同接口把消息展示出来
		 * 调用此函数，页面将自动把消息以指定的方式表现出来，如果传入time值》0，自在time指定秒数后消息自动关闭。
		 */
		_this.show	= function(msginfo){
			_this.init(msginfo);
			switch(_this.method){
				case 'centre':
					//中间弹出框
					var newdialog	= art.dialog({
										    time: _this.time,
										    title: _this.title,
										    content: _this.content,
										    width: _this.width,
										    height: _this.height
										});
					break;
				case 'rightAndBottom':
				default:
					//右下角弹出框
					var newdialog	= art.dialog({
										    title: _this.title,
										    content: _this.content,
										    time:_this.time,
										    width: _this.width,
										    height: _this.height,
										    left: '100%',
										    top: '100%',
										    fixed: true,
										    drag: false,
										    resize: false
										});
					break;
			}
		};
		
		_this.show();//人为调用函数，使 init 相当于构造函数
		return _this;
	};
})(jQuery);