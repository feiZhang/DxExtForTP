/**
 * 此页面负责完成整个系统共有效果的js实现
 * */
(function($) {
	$(function() {
		/*输入框获得焦点后和其他输入框样式区别显示*/
		$('input[type="text"],textarea').live('focus', function() {
			var obj = $(this);
			if (obj.hasClass('Wdate')) {
				obj.addClass("data-input-focus");
			} else {
				var width = obj.width();
				obj.addClass("input-focus").width(width);
			}

		}).live('blur', function() {
			$(this).removeClass("input-focus").removeClass('data-input-focus');
		});
		
		/*页面的第一个输入框默认获取焦点，用于提高操作效率*/
		setTimeout(function() {
			$('input:first[type="text"]:not(.Wdate)').focus()
		}, 800);
		
		/*输入框如果输入回车，自动转换为tab*/
		$('input[type="text"],select').live('keypress', function(event) {
			if (event.which == 13) {
				var inputs = $('input[type="text"],select,textarea'); // 获取表单中的所有输入框  
				var idx = inputs.index(this); // 获取当前焦点输入框所处的位置
				// 判断是否是最后一个输入框
				if (idx != inputs.length - 1) {
					inputs[idx + 1].focus(); // 设置焦点
					return false;// 取消默认的提交行为
				} else {
					return true;
				}
			}
		});
		
		//显示大图
		if($("img.showOrigImage").length>0){
			$("img.showOrigImage").imgPreview({
			    containerID: 'imgPreviewWithStyles',
			    thumbPrefix: '../',
			    onHide: function(link){
			        $('span', this).remove();
			    },
			    srcAttr:"src"
			});
		}
	})

})(jQuery);