/**
 * @author malijie_2007@126.com 
 * @vserion 0.1
 * 
 * 利用百度地图提供的api完成一下功能
 * 1、能够在地图上进行多点标注
 * 2、能够对地图上标注点进行说明。
 * @author hanmeiyan 修改 2013-05-28
 */
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
     *                       点属性：addr_id:标识点id
     *                             addr:'点地址'
     *                             name:覆盖点
     *                             html：弹出框中显示的内容
     *                             lng，lat：覆盖物的经纬度。
     *                             title:弹出框名称。
     *                             icon_img ： 覆盖物图片（可为空）
     *                             icon_width ： 覆盖物图片宽度（可为空）
     *                             icon_height ： 覆盖物图片高度（可为空）
     * @param string mapContainer 显示地图的容器的id 默认为map
     * @param object infoSetting  显示地图的配置{
     *                                          label:true,//显示标识label
     *                                          width:300,//弹出框的宽度，
     *                                          icon_img://标识的图标的样式,
     *                                          icon_width://宽度
     *                                          icon_height://高度
     *                                       }（可为空） 
     */
    $.Map = function(city, aimData, mapContainer,infoSetting){
        if(undefined == arguments[3]) var infoSetting ={};
        var map             = new BMap.Map(mapContainer);
        var _this           = this;
        _this.MarkPoints    = {};//定义点集合
        map.centerAndZoom(city, 12);
        //向地图增加地图平移缩放控件
        map.addControl(new BMap.NavigationControl());
        // 启动鼠标滚轮操作
        map.enableScrollWheelZoom();
        /**
         * 得到各个点的坐标
         */
        _this.setMark       = function(point){
            if((typeof point.lng=="undefined") || isNaN(point.lng) || point.lng=="" || (typeof point.lat =="undefined") || isNaN(point.lng) || point.lng==""){
                var myGeo         = new BMap.Geocoder();
                myGeo.getPoint(point['addr'], function(p){
                    if (p) {
                        point.lng = p.lng;
                        point.lat = p.lat;
                        _this.setPoint(point);
                        return p;
                    }
                },city);
            }
            else{
                _this.setPoint(point);
                return p;
            }
        }
        /**
         * 标识点，为标识点增加单击事件
         */
        _this.setPoint =  function(point){
            var p               = new BMap.Point(point.lng, point.lat);
            //设置自定义图标
            if(point.icon_img){
                //如果该点属性中有覆盖物大小的属性，否则使用系统设置的覆盖物大小
                var icon_width  = point.icon_width?point.icon_width:(infoSetting.icon_width?infoSetting.icon_width:30);
                var icon_height = point.icon_height?point.icon_height:(infoSetting.icon_height?infoSetting.icon_height:30);
                var marker      =   new BMap.Marker(p,{icon:new BMap.Icon(point.icon_img, new BMap.Size(icon_width,icon_height))});
            }else if(infoSetting.icon_img){
                var icon_width  = infoSetting.icon_width?infoSetting.icon_width:30;
                var icon_height = infoSetting.icon_height?infoSetting.icon_height:30;
                var marker      =   new BMap.Marker(p,{icon:new BMap.Icon(infoSetting.icon_img, new BMap.Size(icon_width,icon_height))});
            }else{
                var marker      =   new BMap.Marker(p);
            }
            //增加覆盖点
            map.addOverlay(marker);
            if(point.jump ){
                marker.setAnimation(BMAP_ANIMATION_BOUNCE);
            }
            
            var sContent        = point.html;
            var infoWindow      = new BMap.InfoWindow(sContent,$.extend(infoSetting,{title:point.name}));  // 创建信息窗口对象
            point.infoWindow    = infoWindow;
            //单击鼠标，弹出信息框
            marker.addEventListener("click",function(e){
                this.openInfoWindow(infoWindow,e.point);
                $("img").live("load",function(){
                     infoWindow.redraw();
                });
            });
            //显示label标签
            if((typeof infoSetting.label!='undefined') && infoSetting.label==true){
                var label       = new BMap.Label(point.name,{offset:new BMap.Size(20,-10)});
                marker.setLabel(label);
            }
            point.marker        = marker;
            _this.MarkPoints[point.addr_id]  =  point;
        }
        //初始化数据
        _this.init          = function(){
            if ($.isArray(aimData)) {
                for(var mark in aimData){
                    var point       = aimData[mark];
                    _this.setMark(point);
                }
            } else {
                _this.setMark(aimData);
            }
        }

        /**
         * 打开窗口
         */
        _this.OpenWindow        = function (point_id){
            var point = _this.MarkPoints[point_id];
            if(point&&typeof point['infoWindow']!='undefined'){
                point.marker.openInfoWindow(point.infoWindow);
            }else
                alert('无法准确定位');
        }
        _this.init();
    }
})(jQuery);
