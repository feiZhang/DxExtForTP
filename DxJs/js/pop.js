/**
 * 此页面的前提是：jquery支持， artdialog ymprompt两个弹出框插件支持
 * 功能： 列表页面上a标签加上 pop=pop属性的链接自动调用 ymprompt 弹出框进行处理，pop='view'的链接会自动调用artdialog弹出框处理。
 * 当点击弹出框中修改或新增的提交按钮后，页面自动关闭弹出框，并刷新列表页面，根据列表页面的不同方式，刷新的函数自己定义。
 * 此js会在弹出框的iframe中自动加入class='ympop_iframe_class',用于弹出框中的页面分辨是否在弹出框中，以兼容非弹出框中的页面操作
 * 调用方法：<a href="处理的url" pop='pop' popwidth='300' popheight='300' is_confirm=1 confirm_info='是否确认删除？'>
 * a标签可接收的属性有：
 * pop='pop'，用于修改和添加操作，自动弹出框，用iframe内嵌a标签的href所指向的页面
 * popwidth='300' popheight='300' 可指定弹出框的宽带和高度，若不指定，系统默认弹出框大小为屏幕的宽带-200，屏幕高度-100
 * is_confirm=1 值为1表示弹出框之前需要弹出确认操作 无该属性或属性值为0表示不需要确认
 * confirm_info 有值表示指定确认框的提示信息，前提is_confirm=1
 * pop='view' 用artdialog弹出框，弹出框大小，弹出框url同pop='pop'
 * ymprompt对弹出框iframe支持很好，确定只能同时弹出一个框，弹出多个会错，因此用于仅展示而无操作时不方便，因此扩展了pop='view',可弹出多个框，无提交操作，仅做信息展示
 * artdialog对iframe的支持不好，但可同时弹出多个框，用于信息展示较好。用于操作页面不方便，因为操作后需自动关闭弹出框，不知道关闭的是哪个弹出框
 */
(function($){
	$(function(){
		var x=$("[pop='pop'],[pop='view']").live('click',function(event){
			var o			= $(this);
			var pop_href	= o.attr('href');			
			var pop_title	= o.attr('title') || "提示";
			var pop_width	= o.attr('width') || "auto";
			var pop_height	= o.attr('height') || "auto";
			
			$.dialog.open(pop_href,{"beforeunload":pop_callback,"title":pop_title,"width":pop_width,"height":pop_height})
			//阻止链接跳转页面，而执行pop函数
			event.preventDefault();
		});
		var pop_callback=function(){
			//如果本页定义函数refresh_list 则调用本页函数，否则刷新页面
			try {
				if (typeof (eval(refresh_list)) == "function") {
					refresh_list();
				}
			} catch (e) {
			}
		}
	});
})(jQuery);
