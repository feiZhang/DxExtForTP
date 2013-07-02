/*!
* artDialog 5 plugins
* Date: 2012-03-16
* https://github.com/aui/artDialog
* (c) 2009-2012 TangBin, http://www.planeArt.cn
*
* This is licensed under the GNU LGPL, version 2.1 or later.
* For details, see: http://creativecommons.org/licenses/LGPL/2.1/
*/

;(function ($) {

/**
 * 警告
 * @param   {String, HTMLElement}   消息内容
 * @param   {Function}              (可选) 回调函数
 */
$.alert = $.dialog.alert = function (content, callback) {
    return $.dialog({
        id: 'Alert',
        fixed: true,
        lock: true,
        content: content,
        ok: true,
        beforeunload: callback,
        title:'提示'
    });
};


/**
 * 确认选择
 * @param   {String, HTMLElement}   消息内容
 * @param   {Function}              确定按钮回调函数
 * @param   {Function}              取消按钮回调函数
 */
$.confirm = $.dialog.confirm = function (content, ok, cancel) {
    return $.dialog({
        id: 'Confirm',
        fixed: true,
        lock: true,
        title:"提示",
        content: content,
        ok: ok,
        okValue:"确定",
        cancelValue:"取消",
        cancel: cancel
    });
};


/**
 * 弹窗 (iframe)
 * @param	{String}	地址
 * @param	{Object}	配置参数. 这里传入的回调函数接收的第1个参数为iframe内部window对象
 */
$.dialog.open = function (url, options) {
	options = options || {};
	options	= $.extend(options,{
    	padding:0,
        fixed: true,
        lock: true,
        content:["<div class='artDialogLoading'>正在加载页面!<img src='",
                 PUBLIC_URL,
                 "/public/loading.gif' /></div><iframe display='none' scrolling='auto' src='",url,"' />"].join(''),
        initialize: function () {
        	var _this	= this;
			iframe = this.dom.content.find('iframe')[0];
			iframe.style.cssText = "border:none 0;background:transparent;";
			iframe.setAttribute('frameborder', 0, 0);
			iframe.setAttribute('allowTransparency', true);
    		if(_this.config.height!="auto"){
    			iframe.setAttribute("height",_this.config.height);
    		}
    		if(_this.config.width!="auto"){
    			iframe.setAttribute("width",_this.config.width);
    		}

			$(iframe).bind('load', function(){
	    		//隐藏正在加载
	    		$(_this.dom.main).contents().find(".artDialogLoading").eq(0).hide();

		        try {
	    			iwin = this.contentWindow;
	    			$idoc = $(iwin.document);
	    			ibody = iwin.document.body;
	    		} catch (e) {// 跨域
	    			return;
	    		};
	    		
	    		_isIE6 = window.VBArray && !window.XMLHttpRequest;
	    		// 获取iframe内部尺寸
	    		iWidth 	= $idoc.width() + (_isIE6 ? 0 : parseInt($(ibody).css('marginLeft')));
	    		iHeight = $idoc.height();
	    		if(iHeight>500) iHeight=500;
	    		if(iWidth>800) iWidth=800;
	    		if(iHeight<parseInt(_this.config.height)) iHeight=_this.config.height;
	    		if(iWidth<parseInt(_this.config.width)) iWidth=_this.config.width;
	    		console.log($(this),iHeight,iWidth);
	    		$(this).attr({"height":iHeight,"width":iWidth});
	    		_this.size(iWidth,iHeight);
	    		$(this).show();
	    		_this.position();
			});
        }
    });
    return $.dialog(options);
};

/**
 * 输入框
 * @param   {String, HTMLElement}   消息内容
 * @param   {Function}              确定按钮回调函数。函数第一个参数接收用户录入的数据
 * @param   {String}                输入框默认文本
 */
$.prompt = $.dialog.prompt = function (content, ok, defaultValue,inputType) {
    defaultValue 	= defaultValue || '';
    inputType		= inputType || 'text';
    var input;
    
    return $.dialog({
    	title:"提示",
        id: 'Prompt',
        fixed: true,
        lock: true,
        content: [
            '<div style="margin-bottom:5px;font-size:12px">',
                content,
            '</div>',
            '<div>',
                '<input type="',inputType,'" class="d-input-text" value="',
                    defaultValue,
                '" style="width:18em;padding:6px 4px" />',
            '</div>'
            ].join(''),
        initialize: function () {
            input = this.dom.content.find('.d-input-text')[0];
            input.select();
            input.focus();
        },
        ok: function () {
            return ok && ok.call(this, input.value);
        },
        okValue:"确定",
        cancel: function () {},
        cancelValue:"取消"
    });
};


/** 抖动效果 */
$.dialog.prototype.shake = (function () {

    var fx = function (ontween, onend, duration) {
        var startTime = + new Date;
        var timer = setInterval(function () {
            var runTime = + new Date - startTime;
            var pre = runTime / duration;
                
            if (pre >= 1) {
                clearInterval(timer);
                onend(pre);
            } else {
                ontween(pre);
            };
        }, 13);
    };
    
    var animate = function (elem, distance, duration) {
        var quantity = arguments[3];

        if (quantity === undefined) {
            quantity = 6;
            duration = duration / quantity;
        };
        
        var style = elem.style;
        var from = parseInt(style.marginLeft) || 0;
        
        fx(function (pre) {
            elem.style.marginLeft = from + (distance - from) * pre + 'px';
        }, function () {
            if (quantity !== 0) {
                animate(
                    elem,
                    quantity === 1 ? 0 : (distance / quantity - distance) * 1.3,
                    duration,
                    -- quantity
                );
            };
        }, duration);
    };
    
    return function () {
        animate(this.dom.wrap[0], 40, 600);
        return this;
    };
})();


// 拖拽支持
var DragEvent = function () {
    var that = this,
        proxy = function (name) {
            var fn = that[name];
            that[name] = function () {
                return fn.apply(that, arguments);
            };
        };
        
    proxy('start');
    proxy('over');
    proxy('end');
};


DragEvent.prototype = {

    // 开始拖拽
    // onstart: function () {},
    start: function (event) {
        $(document)
        .bind('mousemove', this.over)
        .bind('mouseup', this.end);
            
        this._sClientX = event.clientX;
        this._sClientY = event.clientY;
        this.onstart(event.clientX, event.clientY);

        return false;
    },
    
    // 正在拖拽
    // onover: function () {},
    over: function (event) {		
        this._mClientX = event.clientX;
        this._mClientY = event.clientY;
        this.onover(
            event.clientX - this._sClientX,
            event.clientY - this._sClientY
        );
        
        return false;
    },
    
    // 结束拖拽
    // onend: function () {},
    end: function (event) {
        $(document)
        .unbind('mousemove', this.over)
        .unbind('mouseup', this.end);
        
        this.onend(event.clientX, event.clientY);
        return false;
    }
    
};

var $window = $(window),
    $document = $(document),
    html = document.documentElement,
    isIE6 = !('minWidth' in html.style),
    isLosecapture = !isIE6 && 'onlosecapture' in html,
    isSetCapture = 'setCapture' in html,
    dragstart = function () {
        return false
    };
    
var dragInit = function (event) {
    
    var dragEvent = new DragEvent,
        api = artDialog.focus,
        dom = api.dom,
        $wrap = dom.wrap,
        $title = dom.title,
        $main = dom.main,
        wrap = $wrap[0],
        title = $title[0],
        main = $main[0],
        wrapStyle = wrap.style,
        mainStyle = main.style;
        
        
    var isResize = event.target === dom.se[0] ? true : false;
    var isFixed = wrap.style.position === 'fixed',
        minX = isFixed ? 0 : $document.scrollLeft(),
        minY = isFixed ? 0 : $document.scrollTop(),
        maxX = $window.width() - wrap.offsetWidth + minX,
        maxY = $window.height() - wrap.offsetHeight + minY;
    
    
    var startWidth, startHeight, startLeft, startTop;
    
    
    // 对话框准备拖动
    dragEvent.onstart = function (x, y) {
    
        if (isResize) {
            startWidth = main.offsetWidth;
            startHeight = main.offsetHeight;
        } else {
            startLeft = wrap.offsetLeft;
            startTop = wrap.offsetTop;
        };
        
        $document.bind('dblclick', dragEvent.end)
        .bind('dragstart', dragstart);
            
        if (isLosecapture) {
            $title.bind('losecapture', dragEvent.end)
        } else {
            $window.bind('blur', dragEvent.end)
        };
            
        isSetCapture && title.setCapture();
        
        $wrap.addClass('d-state-drag');
        api.focus();
    };
    
    // 对话框拖动进行中
    dragEvent.onover = function (x, y) {
    
        if (isResize) {
            var width = x + startWidth,
                height = y + startHeight;
            
            wrapStyle.width = 'auto';
            mainStyle.width = Math.max(0, width) + 'px';
            wrapStyle.width = wrap.offsetWidth + 'px';
            
            mainStyle.height = Math.max(0, height) + 'px';
            
        } else {
            var left = Math.max(minX, Math.min(maxX, x + startLeft)),
                top = Math.max(minY, Math.min(maxY, y + startTop));

            wrapStyle.left = left  + 'px';
            wrapStyle.top = top + 'px';
        };
        
        
    };
    
    // 对话框拖动结束
    dragEvent.onend = function (x, y) {
    
        $document.unbind('dblclick', dragEvent.end)
        .unbind('dragstart', dragstart);
        
        if (isLosecapture) {
            $title.unbind('losecapture', dragEvent.end);
        } else {
            $window.unbind('blur', dragEvent.end)
        };
        
        isSetCapture && title.releaseCapture();
        
        $wrap.removeClass('d-state-drag');
    };
    
    
    dragEvent.start(event);
    
};


// 代理 mousedown 事件触发对话框拖动
$(document).bind('mousedown', function (event) {
    var api = artDialog.focus;
    if (!api) return;

    var target = event.target,
        config = api.config,
        dom = api.dom;
    
    if (config.drag !== false && target === dom.title[0]
    || config.resize !== false && target === dom.se[0]) {
        dragInit(event);
        
        // 防止firefox与chrome滚屏
        return false;
    };
});


}(this.art || this.jQuery));

