/**
 * 此页面的前提是：jquery支持
 * 功能：首页上方的快捷操作按钮，鼠标放上时显示全部按钮，鼠标离开后隐藏按钮，点击某个按钮后右下角页面转向相应链接
 */
(function($){
    $(function(){
        //快捷方式的默认位置在屏幕左侧的中间
        var windowHeight = $(window).height(); //网页高度
        var shortCutHeight = windowHeight - 100; // 设置为快捷菜单高度
        $('#shortcut').height(shortCutHeight);
        var sc_main_left= (windowHeight-shortCutHeight)/2;      
        $('#shortcut').css('top',sc_main_left);
        $('#sc_main').height(parseInt($('#shortcut').height()))
        //让常用操作按钮居中
        var leftNum = $('#shortcut').width();//距离左侧的位置
        var topNum = ($('#shortcut').height()- $('#sc_btn').height()) / 2;
        $('#sc_btn').css({
            'left':leftNum + 'px', 
            'margin-top': topNum + 'px'
        });
                
        //让快捷方式图标隐藏，只显示常用操作按钮
        var hiddenNum =-($('#sc_main').width());
        $('#shortcut').css('left',hiddenNum);
        //图标的默认透明度
        $('#sc_main').css('opacity',0.1);
        /**
         * 常用操作显示函数
         */
        var shortcut_dis=function(event){
            if(parseInt($("#shortcut").css('left'))<0){
                //加上条件防止形成循环
                $('#shortcut').animate({
                    'left':'0'
                });
                //改变图标的透明度
                $('#sc_main').animate({
                    'opacity':'1'
                });
            }
        }
        /**
		 * 常用图标隐藏函数
		 */
        var shortcut_hide=function(event){
            //加上条件防止形成循环
            $('#shortcut').animate({
                'left':hiddenNum
            });
            $('#sc_main').animate({
                'opacity':'0.1'
            });
			
        }
        /**
	 * 鼠标放到div上显示整个div,鼠标离开时隐藏，hover用法，第一个函数表示鼠标悬停时，第二个函数表示鼠标离开时
         */
        var delayTime = 500;
        $("#sc_btn").live("mouseover", function(event){
            var _this=$(this);
            var timeId = setTimeout(function(){
                shortcut_dis(event);
            }, 500);
            _this.data('_last_time', $.now());
            _this.data('_last_id', timeId);
        }).live("mouseout", function(event){
            var _this=$(this);
            var id=_this.data('_last_id');
            var time=_this.data('_last_time');
            if(id){
                if($.now()-time<delayTime){
                    clearTimeout(id);
                }
            }
            if (!countPositionForBtn(event)) {
                shortcut_hide(event);
            } 
        }).live("cancel_hide", function(){
            var _this=$(this);
            var id=_this.data('_tp_timeoutid');
            clearTimeout(id);
        }); 
               
        $("#sc_main").hover(function(event){
             
            }, function(event){
                if (!countPositionForMain(event)) {
                    shortcut_hide(event);
                }
            });        
       
         
        /**
	  根据链接，右下角frame链接变换
	 */
        $.shortcut_link = function(linkhref){
            //把右侧子页面刷新
            top.$('#iframe1').contents().find('#mcMainFrame')[0].contentWindow.location.href=linkhref;
        }
        /**
         *点击快捷方式链接后，右下角链接变换
         */
        var x=$("a[shortcut='shortcut']").live('click',function(event){
            var o=$(this);
            var sc_href=o.attr('href') || '#';
			
            $.shortcut_link(sc_href);
            event.preventDefault();
        });
		
    });
    
    function countPositionForMain(event) {
        var menuLeft = $("#sc_btn").offset().left;
        var menuTop = $("#sc_btn").offset().top;
        var menuWidth = $("#sc_btn").width();
        var menuHeight = $("#sc_btn").height();
        var mouseX = event.pageX;
        var mouseY = event.pageY;
        if (mouseX >= menuLeft && mouseX <= menuLeft + menuHeight) {
            if (mouseY >= menuTop && mouseY <= menuTop + menuWidth) {
                return true;
            }
        }
        return false;
    }
    
    function countPositionForBtn(event) {
        var leftSize = $("#shortcut").offset().left;
        var topSize = $("#shortcut").offset().top;
        var widthSize = $("#sc_main").width();
        var heightSize = $("#sc_main").height();
        var menuLeft = $("#sc_btn").offset().left;
        var mouseX = event.pageX;
        var mouseY = event.pageY;

        if (0 == leftSize && mouseX <= widthSize) {
            if (mouseY >= topSize && mouseY <= topSize + heightSize) {
                return true;
            }
        } else if (mouseX <= menuLeft && mouseX <= widthSize) {
            return true;
        }
        return false;
    }
})(jQuery);