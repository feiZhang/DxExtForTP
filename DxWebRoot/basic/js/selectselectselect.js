/* 
 * 多级联动，数据选择组件。
 * 比如：区域选取
 */
var cantonData          = new Array();
var cantonDataNoTree    = new Array();
var cantonInit          = false;
(function($){
    /**
     * 使用方式 
     $(function(){
        $.selectselectselect(list, 'cantonDis', '1673', '3520',function(t){alert(t);});
     }); 
     */
    /**
     * data {[parent_id:1,canton_id:1,title:"郑州",val:""],[parent_id:1,canton_id:1,title:"郑州",val:""]} json格式的数据信息..
     * cantonDomId          canton数据的dom元素的id
     * defaultKey           默认选择的数据 可以是数组
     * rootKey              树的根ID
     * selectEven           选择数据后的回调函数
     * completeEven         select构建完成后的回调函数
     */
    $.selectselectselect = function (data,cantonDomId,defaultKey,rootKey,selectEven,completeEven,showRootKey) {
        if(typeof this !== 'object'){
            //强制进行new操作
            return new $.selectselectselect(data,cantonDomId,defaultKey,rootKey,selectEven,completeEven,showRootKey);
        }

        var containerDomId  = "selectselectselect_" + cantonDomId; // containerDomId       显示被加到目标dom元素的id
        var tree            = new Array();
        if(data==0 || data==undefined || data=="" || data.length < 1){
            if(cantonInit == false){
                cantonInit = true;
                $.ajax({url:APP_URL+"/Canton/getSelectSelectSelect",success:function(data){
                        var cantonLength = data.length;
                        for (i=0;i<cantonLength; i++) {
                            if (undefined == cantonData[data[i].parent_id]) {
                                cantonData[data[i].parent_id] = new Array();
                            }
                            cantonData[data[i].parent_id].push(data[i]);
                            cantonDataNoTree[data[i].canton_id] = data[i];
                        };
                    },dataType:'json',async:false});
            }
            tree    = cantonData;
        }

        var containerDom    = $("#" + containerDomId);
        var _this           = this;
        $("input#"+cantonDomId).change(function(){
            _this.setDefaultSelect($(this).val());
        });
        //将data数据重组为多个数组数据
        _this.initData = function(data,rootKey) {
            if(tree.length==0){
                //没有全局数据，则使用传递的数据
                var cantonLength = data.length;
                for (i=0;i<cantonLength; i++) {
                    if (undefined == tree[data[i].parent_id]) {
                        tree[data[i].parent_id] = new Array();
                    }
                    tree[data[i].parent_id].push(data[i]);
                }
            }
            
            $("select[type='canton']", containerDom).live('change', function(){
                $(this).parent("div").nextAll("div.cantonDiv").remove();
                _this.select($(this).find("option:selected").attr("key"));
                //先选“xx街道办”，再选同一select的“请选择”，则获取的值为空，而实际已经选择了“金水区”，则应将“金水区的select框传递出去”
                if($(this).val()=="" && $(this).parent("div").prev("div").length>0) valSelect = $(this).parent("div").prev("div").children("select");
                else valSelect = $(this);
                if(selectEven!=0 && selectEven!=undefined){
                    selectEven(valSelect);
                }else{
                    $("#"+cantonDomId).attr("text",$(valSelect).find("option:selected").attr("key"));
                    $("#"+cantonDomId).attr("text",$(valSelect).val());
                }
            });
            
            if(rootKey!=undefined){
                if (rootKey!=undefined){
                    rootKey     = parseInt(rootKey);
                    if(showRootKey==true){
                        var t   = new Array();
                        t.push(cantonDataNoTree[rootKey]);
                        _this.createSelect(t);
                    }else{
                        _this.createSelect(tree[rootKey]);
                    }
                }else
                    _this.createSelect(tree[0]);
                _this.setDefaultSelect(rootKey);
            }
        }
        //选取某个数据后，触发，生成下级选取列表
        _this.select = function(key) {
            _this.createSelect(tree[key]);
        }
        //根据数组生成select下拉选项
        _this.createSelect  = function(data){
            if (undefined != data) {
                var dataLength = data.length;
                var strHtml = "<div class=\"cantonDiv\" style=\"display:inline\"><select class='autowidth' name='aCanton' type='canton'>";
                strHtml += "<option value=\"\">请选择</option>";
                for(i=0;i<dataLength; i++) {
                    if (undefined != data[i]) {
                        if (undefined != data[i].val) {
                            strHtml += "<option key=\"" + data[i].canton_id + "\" value=\""+ data[i].val +"\">" + data[i].title + "</option>";
                        }else{
                            strHtml += "<option key=\"" + data[i].canton_id + "\" value=\""+ data[i].canton_id +"\">" + data[i].title + "</option>";
                        }
                    }
                }
                strHtml += "</select></div>";
                $(strHtml).appendTo(containerDom);
            }
        }
        //设置下拉框的默认值
        _this.setDefaultSelect = function(defaultKey) {
            if ("" != defaultKey && defaultKey!=0) {
                if(defaultKey.constructor == String){
                    //fdn格式转换为数组格式
                    defaultKey = defaultKey.split(".");
                    if(defaultKey[defaultKey.length-1]=="") defaultKey.pop();
                    $(defaultKey).each(function(i,v){
                        defaultKey[i]   = v;
                    });
                }

                if (defaultKey.constructor == Array) {
                    $.each(defaultKey, function(i, item){
                        $("#"+containerDomId+" select[type='canton']").each(function(j){
                            var selectItem = $(this).find("option[key='"+parseInt(item,10)+"']");
                            if (null != selectItem.get(0)) {
                                selectItem.attr("selected", true);
                                $(this).change();
                            }
                        });
                    });
                } else if (defaultKey.constructor == Number) {
                    if(defaultKey!=0 && $("select[type='canton']").length>0){
                        $("#"+containerDomId+" select[type='canton']").each(function(i){
                            $(this).find("option[key='"+defaultKey+"']").attr("selected", true);
                            if($(this).find("option[key='"+defaultKey+"']").length>0)$(this).change();
                        });
                    }
                }
            }
        }

        _this.initData(data,rootKey);
        //对第一个下拉列表选择默认数据。
        if(defaultKey!=undefined) _this.setDefaultSelect(defaultKey);
        if(completeEven!=undefined){
            completeEven();
        }
        return _this;
    }
})(jQuery);
