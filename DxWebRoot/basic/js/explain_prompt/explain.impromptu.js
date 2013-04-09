(function($){
	$.explain = function(message, options,type)
	{
		$.explain.type=(typeof(arguments[2])=="undefined")?'':arguments[2];// 第二个参数;
		$.explain.options = $.extend({},$.explain.defaults,options);
		$.prompt(message,options);
		if(typeof message == 'object')
		{
			$.explain.message = message;
		}
		else
			$.explain.message = $.prompt.states;
		
		$.prompt.jqif.css({opacrity:0});
		
		$.explain.currentState = 0;
		// 左上区域
		msgbox ='<div class="'+ $.prompt.options.prefix +'fade tl" id="'+ $.prompt.options.prefix +'fadetl"></div>';
		// 上面中间
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade tm" id="'+ $.prompt.options.prefix +'fadetm"></div>';
		// 上右
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade tr" id="'+ $.prompt.options.prefix +'fadetr"></div>';
		// 中左
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade ml" id="'+ $.prompt.options.prefix +'fademl"></div>';
		// 中右
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade mr" id="'+ $.prompt.options.prefix +'fademr"></div>';
		// 底部左侧
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade bl" id="'+ $.prompt.options.prefix +'fadebl"></div>';
		// 底部右侧
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade br" id="'+ $.prompt.options.prefix +'fadebr"></div>';
		// 底部中间
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade bm" id="'+ $.prompt.options.prefix +'fadebm"></div>';
		// 点亮区域
		msgbox +='<div class="'+ $.prompt.options.prefix +'fade mm light" id="'+ $.prompt.options.prefix +'fadelight"></div>';
		
		$.explain.bg = $(msgbox).appendTo($.prompt.jqib);
		if($.explain.type == "fixed" ||$.explain.type =='mix')
		{
			$.explain.init(message.length);
		}
		$.explain.bg.css({
			bakckground:"#777",
			opacity: $.prompt.options.opacity,
			zIndex: $.prompt.options.zIndex
		});
		$.explain.bg.css({
			position: "absolute"
		});
	
		$.explain.bg.filter('.mm').css({
			opacity: 1
		});
		$.explain.reposition();
		$(window).resize(function(){
			$.explain.reposition();
		});
	}
	/*
	 * 重新定位背景，把原来的一整块背景，换成九个小块背景
	 */
	$.explain.reposition = function()
	{
		var restoreFx = $.fx.off,
			$window = $(window),
			bodyHeight = $(document.body).outerHeight(true),
			windowHeight = $(window).height(),
			documentHeight = $(document).height(),
			documentWidth = $(document).width();
		var	height = bodyHeight > windowHeight ? bodyHeight : windowHeight,
			top = parseInt($window.scrollTop(),10) + ($.prompt.options.top.toString().indexOf('%') >= 0?(windowHeight*(parseInt($.prompt.options.top,10)/100)) : parseInt($.prompt.options.top,10));
		var pos = $.prompt.states[$.prompt.currentStateName].position;
		if(pos.container)
		{
			var	offset = $(pos.container).offset();
			var contain_width = parseInt($(pos.container).css('width'),10)
								+ parseInt($(pos.container).css('padding-left'))
								+ parseInt($(pos.container).css('padding-right'));
			var contain_height = parseInt($(pos.container).css('height'),10)
								 + parseInt($(pos.container).css('padding-top'))
								 + parseInt($(pos.container).css('padding-bottom'));;
			
			
			var b_height = documentHeight-(parseInt(offset.top,10)+contain_height)-2;
			var t_height =  parseInt(offset.top,10)-2;
			var m_height = contain_height+4;
			var l_width = parseInt(offset.left,10)-2;
			var m_width = contain_width+4;
			var r_width = documentWidth - l_width - m_width-2;
			//	alert(offset.left);
			// 上左
			$.explain.bg.filter('.tl').css({
				top: 0+'px',
				left: 0+'px',
				width:l_width+'px',
				height:t_height+'px'
			});
			// 上右
			$.explain.bg.filter('.tr').css({
				top: 0+'px',
				left: l_width+m_width,
				height:t_height,
				width:r_width
			});
			// 上中
			$.explain.bg.filter('.tm').css({
				top: 0,
				left: l_width+'px',
				width:m_width+'px',
				height:t_height+'px'
			});
			// 中左
			$.explain.bg.filter('.ml').css({
				top: t_height+'px',
				left: 0,
				width:l_width+'px',
				height:m_height+'px'
			});
			// 中右
			$.explain.bg.filter('.mr').css({
				top: t_height+'px',
				left: l_width+m_width+'px',
				width:r_width,
				height:m_height
			});
			// 下左
			$.explain.bg.filter('.bl').css({
				top:t_height+m_height+'px',
				left: 0,
				width:l_width+'px',
				height:b_height+'px'
			});
			// 下中
			$.explain.bg.filter('.bm').css({
				top: t_height+m_height+'px',
				left:l_width+'px',
				width:m_width+'px',
				height:b_height+'px'
			});
			// 下右
			$.explain.bg.filter('.br').css({
				top: t_height+m_height+'px',
				left: l_width+m_width+'px',
				width:r_width+'px',
				height:b_height+'px'
			});
			// 点亮区域
			$.explain.bg.filter('.mm').css({
				top: t_height+'px',
				left:l_width+'px',
				marginLeft: 0+'px',
				width: m_width+'px',
				height:m_height+'px'
			});
		}
		else{
			$.explain.bg.css({
				top: 0+'px',
				left: 0+'px',
				width:0,
				height:0
			});
			$.explain.bg.filter('.tl').css({
				top: 0+'px',
				left: 0+'px',
				width:documentWidth+'px',
				height:documentHeight+'px'
			});
		}
		$.prompt.jqif.css({
			display	:"none"
		});
	}
	/*
	 * 跳转到上一步
	 */
	$.explain.prevState= function()
	{
		$.prompt.prevState();
		$.explain.reposition();
		btn_class();
		
	}
	/*
	 * 跳转到下一步
	 */
	$.explain.nextState = function()
	{
		$.prompt.nextState();
		$.explain.reposition();
		btn_class();
	}
	// 关闭向导
	$.explain.endState = function(callback){
		$.prompt.close(true);
	}
	// 重新开始
	$.explain.startState = function(callback) {
		$.prompt.goToState(0, callback );
		$.explain.reposition();
	};
	/*
	 * 跳转到第几步
	 */
	$.explain.goToState =function(i,callback)
	{
		$.prompt.goToState(i,callback);
		$.explain.reposition();
	}
	/*
	 * fix 模式下取出每一步的按钮
	 */
	$.explain.removeButton = function(){
		$.each($('.'+$.prompt.options.prefix+'_state'),function(i,e){
			$(e).children('.'+$.prompt.options.prefix+'buttons').remove();
		});
	}
	$.explain.init=function(count){
		/*
		 * 加载fix模式下的按钮
		 */
		if($.explain.type == "fixed"){
			$.explain.removeButton();
		}
		var btn_div = $('<div></div>').appendTo($.prompt.jqib).css({'width':"100%"});
		var btn_area = "<ul class="+$.prompt.options.prefix+"_btn><li class='"+$.prompt.options.prefix+"_prev'>上一步</li>";
		for(var i=0; i<count;i++)
		{
			btn_area += "<li class='"+$.prompt.options.prefix+"_step'>"+(i+1)+"</li>";
		}
		btn_area += "<li class='"+$.prompt.options.prefix+"_next'>下一步</li></ul>";
		$.explain.btn = $(btn_area).appendTo(btn_div);
		// 点击向左按钮，显示上一步操作
		$.explain.btn.children('li').first().addClass('expalin_prev').bind("click",function(){
			if(parseInt($.prompt.currentStateName) == 0&&($.explain.options.isLoop==false))
			{
				return false;
			}
			if(parseInt($.prompt.currentStateName) == 0 && (typeof $.explain.options.isLoop !='undefined'&& $.explain.options.isLoop==true))
			{
				$.explain.goToState($.explain.message.length-1);
				return false;
			}
			$.explain.prevState();
			btn_class();
		});
		// 点击向右的按钮，显示下一步操作
		$.explain.btn.children('li').last().addClass('explain_next').bind('click',function(){
			if(parseInt($.prompt.currentStateName,10) == $.explain.message.length-1&&($.explain.options.isLoop==false))
			{
				return false;
			}
			if(parseInt($.prompt.currentStateName) == $.explain.message.length-1)
			{
				if(typeof $.explain.options.isLoop !='undefined'&&$.explain.options.isLoop==true)
				{
					$.explain.goToState(0);
					return false;
				}
			}
			$.explain.nextState();
			btn_class();
		});
		
		var ie6		= ($.browser.msie && $.browser.version < 7);
	    if(ie6)
	    	{
	    	/*
	    	 * 设置当窗口滚动时，不再浮动
	    	 */
	    	$('html').css({
	    		overflow:"hidden"
	    	});
	    	$("body").css({
	    		height:'100%',
	    		overflow:'auto'
	    	});
	    	$.explain.btn.css({
	    		position:'absolute'
	    	});
		  if(typeof $.explain.options.btn_position.bottom != 'undefined')
		   {
			   var top  = $(window).height() -  parseInt($.explain.options.btn_position.bottom)- 50;
		   }
		   if(typeof $.explain.options.btn_position.top != 'undefined')
		   {
				var top  = $.explain.options.btn_position.top;
		   }
		    //在ie6 下滚动滚动条，按钮的top值跟着改变
	       window.onscroll = function() {
		      	$.explain.btn.css('top',document.documentElement.scrollTop+parseInt(top,10)+'px');
	        };
	    }
	    else
	    {
	    	$.explain.btn.css({position:'fixed'});
	    }
	 	// 设置操作步骤样式
		$.explain.btn.css($.extend({
			zIndex:$.prompt.options.zIndex+1
		},$.explain.options.btn_position));
		$.explain.btn.children('li').css({
			float:"left",
			display: "inline",
			cursor:"pointer"
		});
		// 点击中间的每一个按钮，跳到相应的步骤
		$.explain.btn.children('li').each(function(i,element){
			if(i>0&&i<count+1)
			{
				$(element).bind('click',function(){
					$.explain.goToState(i-1);
					btn_class();
				});
			}
		});
		$(document).bind('keydown',keyPressEventHandler);
		btn_class();
	}
	$.explain.defaults={
		isLoop:false,
		btn_position:{
			left:'40%',
			bottom:'30%'
		}
	}
	/*
	按钮样式判断
	*/
	var btn_class = function()
	{
		if($.explain.btn.size()>0 ){
			var step =parseInt($.prompt.currentStateName)+1;
			var count = $.explain.message.length;
			if(step == 1 && $.explain.options.isLoop == false)
			{
				$.explain.btn.children('li').first().addClass('disabled_prev');
			}
			else if($.explain.btn.children('li').first().hasClass('disabled_prev'))
			{
				$.explain.btn.children('li').first().removeClass('disabled_prev');
			}
			if(step == count && $.explain.options.isLoop == false )
			{
				$.explain.btn.children('li').last().addClass('disabled_next');
			}
			else if($.explain.btn.children('li').last().hasClass('disabled_next'))
			{
				$.explain.btn.children('li').last().removeClass('disabled_next');
			}
			$.explain.btn.children("."+$.prompt.options.prefix+"_step").removeClass('explain_selected');
			$.explain.btn.children("."+$.prompt.options.prefix+"_step").eq(step-1).addClass('explain_selected');
		}
		else{
			return;
		}
		
	}
	/*
	 * 键盘操作
	 * 当按下向右的按钮时，到下一步
	 * 当按下向左的按钮式，执行上一步操作
	 * 当按下ESC键时，关闭帮助窗口
	 */
	var keyPressEventHandler=function(e){
		var key = (window.event) ? event.keyCode : e.keyCode; // MSIE or
				// escape key closes
		if(key==37) {
			$.explain.btn.children('li').first().click();
		}
		else if(key ==39){
			$.explain.btn.children('li').last().click();
		}
		// constrain tabs
		else if(key==27)
		{
			$.explain.endState();
		}
	}
})(jQuery);