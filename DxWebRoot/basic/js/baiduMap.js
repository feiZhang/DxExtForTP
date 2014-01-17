(function($){
    /**
     * @param string city  显示该城市的地图
     * @param json   aimData 被标注的信息体
     *                       例子 
     *                       {addr:"文化路128号"，name:"大象通信", html:"<p>郑州大象公司所在地</p>", lng = 113.695781, lat = 34.730864} 
     *                       其中 lng,lat 代表经度和纬度，可有可无，如果有将按照其生成坐标点
     *                       或着
     *                       [{addr:"文化路陈寨", name:"大象通信", html: "<p>大象通信</p>"},{addr:"文化路128号", name:"大象通信", html: "<p>大象通信</p>", lng :113.689313, lat:34.769065}]
     *                       这样传递数组形式，将标注多个位置
     * @param json   infoFormat 显示标注点窗口的信息格式，例子{title:'简要',width:290, height:160}
     * @param string mapContainer 显示地图的容器的id 默认为map
     */
    $.Map = function(city, aimData, mapContainer,infosetting){
        var map = new BMap.Map(mapContainer);
	var defaultSet = {
				enableMessage:false,
				height:0,
				width:0
				};
        var _this = this;
        _this.MarkPoints    = {};//定义点集合

	$.extend(infosetting,defaultSet);
        map.centerAndZoom(city, 12);
        //向地图增加地图平移缩放控件
        map.addControl(new BMap.NavigationControl());
        // 启动鼠标滚轮操作
        map.enableScrollWheelZoom(); 
        _this.setMark = function(point){
            if((typeof point.lng=="undefined")||isNaN(point.lng) ||(typeof point.lat =="undefined"||isNaN(point.lng))){
                var myGeo = new BMap.Geocoder();
                myGeo.getPoint(point['addr'], function(p){
                    if (p) {
                        point.lng = p.lng;
                        point.lat = p.lat;
                        _this.setPoint(point);
                        return;
                    }
                },city);
            }else{
                _this.setPoint(point);
            }

            return _this;
        }
        _this.setPoint =  function(point){
	    var lng = point.lng.toString();
	    var lat = point.lat.toString();
	    if(lng.indexOf(".")>0){
	    }else{
		point.lng = lng.substr(0,3) + "." + lng.substr(3);
		point.lat = lat.substr(0,2) + "." + lat.substr(2);
	    }
            var p = new BMap.Point(point.lng, point.lat);

	    if(point.icon!==undefined && point.icon!=''){
		    if(point.iconWidth == undefined) point.iconWidth = 20;
		    if(point.iconHeight == undefined) point.iconHeight = 20;
		    var mapicon = new BMap.Icon(point.icon,new BMap.Size(point.iconWidth,point.iconHeight));
		    var marker = new BMap.Marker(p,{icon:mapicon});
	    }else{

		    var marker = new BMap.Marker(p);
	    }
            map.addOverlay(marker);
            var sContent = point.html;
            var infoWindow = new BMap.InfoWindow(sContent,$.extend(infosetting,{title:point.name}));  // 创建信息窗口对象
            point.infoWindow  =  infoWindow;
            marker.addEventListener("click",function(e){
                this.openInfoWindow(infoWindow,e.point);
            });
            //point.marker = marker;
            if(point.show==true) marker.openInfoWindow(infoWindow,point);
//            $.extend(_this.MarkPoints,{point.lng+"-"+point.lat:point});
        }

	_this.clear = function(){
		map.clearOverlays();
	}
        if(typeof aimData  !=  'object'){
            return ;
        }else{
            //设置点信息集合
            //标注点
            for(var mark in aimData){
                var point = aimData[mark];
                _this.setMark(point);
            }
        }
    }
})(jQuery);

