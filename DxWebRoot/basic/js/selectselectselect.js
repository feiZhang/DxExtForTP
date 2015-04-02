/* 
 * 多级联动，数据选择组件。
 * 比如：区域选取
 */
var cantonData          = new Array();
var cantonInit          = false;
(function($){
    /**
     * 使用方式 
     $(function(){
        $.selectselectselect(list, 'cantonDis', '1673', '3520',function(t){alert(t);});
     }); 
     */
    /**
     * containerDom         显示被加到目标dom元素的id
     * data {[parent_id:1,pkid:1,title:"郑州",val:""],[parent_id:1,pkid:1,title:"郑州",val:""]} json格式的数据信息..
     * cantonDomId          canton数据的dom元素的id
     * defaultKey           默认选择的数据 可以是数组
     * rootKey              树的根ID
     * selectEven           选择数据后的回调函数
     * completeEven         select构建完成后的回调函数
     * toTextName           这个暂时不用，使用hide的input进行textTo设置。
     */
    $.selectselectselect = function (containerDom,data,cantonDomId,defaultKey,rootKey,selectEven,completeEven,showRootKey,toTextName) {
        if(typeof this !== 'object'){
            //强制进行new操作
            return new $.selectselectselect(containerDom,data,cantonDomId,defaultKey,rootKey,selectEven,completeEven,showRootKey,toTextName);
        }

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
                        };
                    },dataType:'json',async:false});
            }
            tree = cantonData;
        }else{
            tree = data;
        }

        var _this           = this;
        if($(containerDom).find("input#"+cantonDomId).val()!=""){
            defaultKey = $(containerDom).find("input#"+cantonDomId).val()
        }
        $(containerDom).find("input#"+cantonDomId).change(function(){
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
                    $(containerDom).find("input#"+cantonDomId).attr("text",$(valSelect).find("option:selected").attr("key"));
                    $(containerDom).find("input#"+cantonDomId).val($(valSelect).val());
                }
            });
            
            if(rootKey!=undefined && rootKey!=''){
                rootKey     = parseInt(rootKey);
                if(showRootKey==true){
                    var t   = new Array();
                    t.push(tree[rootKey]);
                    _this.createSelect(t);
                }else{
                    _this.createSelect(tree[rootKey]);
                }
            }else{
                _this.createSelect(tree[0]);
            }
            _this.setDefaultSelect(defaultKey);
        }
        //选取某个数据后，触发，生成下级选取列表
        _this.select = function(key) {
            _this.createSelect(tree[key]);
        }
        //根据数组生成select下拉选项
        _this.createSelect  = function(data){
            if (undefined != data) {
                var dataLength = data.length;
                var strHtml = "<div class=\"cantonDiv\" style=\"display:inline\">";
                strHtml += "<select class='autowidth fdnSelectSelect";

                // if(toTextName!=undefined && toTextName!=""){
                //     strHtml += " textTo' textTo='" + toTextName;
                // }
                
                strHtml += "' name='" + cantonDomId + "_selectselectselect' type='canton'>";
                strHtml += "<option value=\"\" short_name=\"\" full_name=\"\" key=\"\">请选择</option>";
                for(i=0;i<dataLength; i++) {
                    if (undefined != data[i] && data[i].name!='请选择') {
                        if (undefined != data[i].fdn) {
                            strHtml += "<option short_name=\"" + data[i].name + "\" full_name=\"" + data[i].full_name + "\" key=\"" + data[i].pkid + "\" value=\""+ data[i].fdn +"\">" + data[i].name + "</option>";
                        }else{
                            strHtml += "<option short_name=\"" + data[i].name + "\" full_name=\"" + data[i].full_name + "\" key=\"" + data[i].pkid + "\" value=\""+ data[i].pkid +"\">" + data[i].name + "</option>";
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
                        $(containerDom).find("select[type='canton']").each(function(j){
                            var selectItem = $(this).find("option[key='"+parseInt(item,10)+"']");
                            if (null != selectItem.get(0)) {
                                selectItem.attr("selected", true);
                                $(this).change();
                            }
                        });
                    });
                } else if (defaultKey.constructor == Number) {
                    if(defaultKey!=0 && $("select[type='canton']").length>0){
                        $(containerDom).find("select[type='canton']").each(function(i){
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
        if(completeEven!=undefined && completeEven!=""){
            completeEven();
        }
        return _this;
    }
})(jQuery);
