/**
 * DataOpe的扩展js操作，比如：删除、修改、状态改变等等。
 * */
function dataOpeAdd(config){
    dataOpeEdit(config);
}

function dataOpeEdit(config){
    var moduleName = config.moduleName;
    var dialogTitle = config.title;
    var urlPara = config.data==undefined?"":config.data;
    var data_id = config.id==undefined?0:config.id;

    var this_post_url   = URL_URL;
    if(dialogTitle=="" || dialogTitle==0 || dialogTitle==undefined) dialogTitle=$("#modelInfo_editTitle").val();
    if(dialogTitle=="" || dialogTitle==0 || dialogTitle==undefined) dialogTitle="修改";
    if(moduleName!=0 && moduleName!='' && moduleName!=undefined) this_post_url  = APP_URL + "/" + moduleName;
    $.dialog({
        id:"editObject",
        title:dialogTitle,
        content:'正在加载页面!<img src="' + DX_PUBLIC + '/public/loading.gif" />',
        esc:true,
        padding:0,
        lock:true,
        ok:function(){
            if($("form#itemAddForm").length<1) return true;
            $("form#itemAddForm").attr("action",this_post_url + "/save");
            $('form#itemAddForm').submit();
            if(config.reloadPage=="1"){
                $('form#itemAddForm').attr("afterSubmit","reloadPage");
            }
            return false;
        },
        okValue:"保存",
        cancelValue:"取消",
        cancel:function(){},
        initialize:function(){
            var theThis     = this;
            $.get(this_post_url + "/edit/" + data_id + "?" + urlPara,function(html){
                theThis.content(html);
                angular.bootstrap(document,["dxApp"]);
                $(theThis.dom.main).contents().find(":input:visible").not(".Wdate").eq(0).focus();
                $("form#itemAddForm").attr("action",this_post_url);
            });
        }
    });
}
function dataOpeDelete(config){
    var this_post_url   = URL_URL;
    var moduleName = config.moduleName;
    var msg = config.msg;
    if(moduleName!=0 && moduleName!='' && moduleName!=undefined) this_post_url  = APP_URL + "/" + moduleName;
    if(msg==undefined) msg="确定要删除此数据?";
    $.dialog({
        id:"deleteDataOpeItem",
        title:"提醒",
        lock:true,
        content:msg,
        ok:function(){
            _this   = this;
            _this.button({id: 'ok',disabled: true},{id:'cancel',disabled:true});

            $.get(this_post_url+"/delete/"+config.id,function(data){
                _this.time(2000).title("提示").content(data.info);
                if(config.reloadPage=="1"){
                    setInterval(function(){document.location.reload();},2000);
                }else{
                    if(data.status){
                        Sigma.GridCache["theDataOpeGrid"].reload();
                    }
                }
            },"json");
            return false;
        },
        okValue:"确定",
        cancel:function(){},
        cancelValue:"取消"
    });
}
function dataOpeListDialog(config){
    $.dialog({
        id:"dataOpeListDialog",
        title:config.title || "列表",
        lock:true,
        content:'正在加载页面!<img src="' + DX_PUBLIC + '/public/loading.gif" />',
        padding:0,
        ok:function(){
            if(config.ok != undefined) config.ok(this);
        },
        okValue:"确定",
        cancel:function(){},
        cancelValue:"取消",
        initialize:function(){
            var theThis     = this;
            var this_post_url   = URL_URL;
            var moduleName = config.moduleName;
            if(moduleName!=0 && moduleName!='' && moduleName!=undefined) this_post_url  = APP_URL + "/" + moduleName;
            var html = "<div id='dataListConDialog' style='width:600px;height:500px;'><div id='dataListDialog'></div></div>";
            if(config.html != undefined) html = html + config.html;

            $.get(this_post_url + "/get_model",function(data){
                theThis.content(html);

                var dxGridList = new $.dxGrid();
                dxGridList.init({ "gridDiv":"dataListDialog",",loadUrl":"","gridFields":data.gridFields,"datasetFields":data.datasetFields,"parentGridDiv":"dataListConDialog",
                        "enablePage":0,"enableExport":0,"enablePrint":0,
                        "customRowAttribute":"",
                        "stripeRows":"","pkId":data.pkId
                });
                dxGridList.setBaseURL(this_post_url);
                if(config.dataUrl != undefined) dxGridList.setData(this_post_url + "/" + config.dataUrl);
                dxGridList.showGrid({"excludeHeight":config.excludeHeight,"onComplete":config.onComplete});
            });
        }

    });
}

/**
 * 数据查询函数
 * */
function getDataSearchUrl(){
    var para    = new Object();
    $("input.dataOpeSearch,select.dataOpeSearch").each(function(){
        if($(this).val()=="") return;
        if($(this).attr("type")=="radio"){
            if($(this).attr("checked")=="checked"){
                para[$(this).attr("name")]    = $(this).val();
            }
        }else{
            var tPara   = $(this).val();
            if($(this).hasClass("likeLeft")) tPara  = "%" + tPara;
            if($(this).hasClass("likeRight")) tPara = tPara + "%";
            para[$(this).attr("name")]    = tPara;
        }
    });
    para = jQuery.param(para);
    return para;
}
function dataOpeSearch(noAllData){
    if(noAllData){
        dxGrid.query(getDataSearchUrl());
    }else{
        dxGrid.query("");
    }
}

function resetPasswd(config){
    $.dialog({
        id: 'Prompt',
        fixed: true,
        lock: true,
        title:"重置密码",
        content: [
            '<div style="margin-bottom:5px;font-size:12px">请输入新密码:</div>',
            '<div>',
            '<input type="password" class="d-input-text" value="" style="width:18em;padding:6px 4px" />',
            '</div>'
            ].join(''),
        initialize: function () {
            input = this.dom.content.find('.d-input-text')[0];
            input.select();
            input.focus();
        },
        ok: function () {
            var _this   = this;
            $.get(URL_URL + "/resetPassword?i="+ config.id +"&p="+input.value,
                    function(data){
                        _this.content(data.info).time(2000).button({
                            id: 'ok',
                            disabled: true
                        },{
                            id:'cancel',
                            value:'关闭'
                        });
                    },"json");
            return false;
        },
        okValue:"确定",
        cancel: function () {},
        cancelValue:"取消"
    });
}

/**
 * 打开剪切头像对话框
 * */
function showUploadPhoto(img,input){
    $.dialog({
        id:"upload_cut_photo",
        title:"上传头像",
        lock:true,
        ok:function(){
            var xyz = $("#selectXY");
            if(xyz.text()==""){
                showDialog("提醒","请先上传头像文件!");
                return false;
            }
            if(xyz.width()==0 || xyz.height()==0){
                showDialog("提醒","请先选择头像区域!");
                return false;
            }
            var _this   = this;
            $.ajax({
                type : "POST",
                url : APP_URL + "/Basic/cut_img",
                data : {"img":xyz.text(),
                 "width":xyz.width(),"height":xyz.height(),
                 "left":xyz.css("marginLeft"),
                 "top":xyz.css("marginTop")},
                success : function(data){
                    if(data.status){
                        img.attr("src",APP_URL + "/" + (data.data.url).substring(1));
                        input.val(data.data.file);
                        _this.content(data.info).time(2000).button({
                            id: 'ok',
                            disabled: true
                        },{
                            id:'cancel',
                            value:'关闭'
                        });
                    }else{
                        showDialog("错误",data.info);
                    }
                },
                dataType : "json"
            });
            return false;
        },
        okValue:"确认裁剪并提交",
        cancel:function(){},
        cancelValue:"取消",
        initialize:function(){
            var theThis     = this;
            $.get(APP_URL + "/Basic/upload_photo",function(html){
                theThis.content(html);
            });
        }
    });
}

/**
 * 将html中的url全部下载 
 */
function downLoadAllFile(obj){
    var url = $(obj).find(a[download]);
    $(url).each(function(index,a){
        window.open($(a).attr("href"));
    });
}

/**处理多选select值*/
function _dataope_onSetChange(obj){
    var _this=$(obj);
    var ret=[];
    _this.find("option").each(function(idx, e){
        if($(e).attr("selected")){
            ret.push($(e).attr("value"));
        }
    });
    _this.next('input').val(ret.join(","));
    return false;
}

/**处理多选项(checkbox)值*/
function _dataope_onCheckChange(obj){
    var _this=$(obj);
    var ret=[];
    var p=_this.parentsUntil(".checkset").parent();
    p.find(".checkitem").each(function(idx, e){
        if($(e).attr("checked")){
            ret.push($(e).attr("value"));
        }
    });
    p.find(".checksetval").val(ret.join(","));
    return false;
}


//数据验证后，自动执行此操作。
function formSubmitComplete(form, r){
    if(r){
        //将textTo的数据赋值
        $("input.textTo[type='radio']").each(function(){
            toId  = $(this).attr("textTo");
            $("input" + "#" + toId).val($(this).attr("text"));
        });
        $("select.textTo").each(function(){
            toId  = $(this).attr("textTo");
            if($(this).val()=="")
                $("input" + "#" + toId).val("");
            else if($(this).hasClass("cantonSelect")){
                // 最后一个可能为空选
                var tvvv = $(this).find('option:selected').attr('key');
                if(tvvv!=undefined && tvvv!=""){
                    $("input" + "#" + toId).val($(this).find('option:selected').attr('key'));
                }
            }else
                $("input" + "#" + toId).val($(this).find('option:selected').text());
        });

        //触发savedata事件,用于支持fckeditor保存数据.
        $('form#itemAddForm').find(":input").trigger("savedata");

        var theThis     = $.dialog.get('editObject');
        theThis.button({id: 'ok',disabled: true,'value':'数据正在处理'},{id:'cancel',disabled:true});
        $.ajax({
            type : "POST",
            url : $("form#itemAddForm").attr("action"),
            data : $("form#itemAddForm").serialize(),
            success : function(msg) {
                if (msg["status"] == 0) {
                    showDialog("提示",msg["info"]);
                    theThis.button({id: 'ok',disabled: false,'value':'确定'},{id:'cancel',disabled:false});
                } else {
                    if($('form#itemAddForm').attr("afterSubmit")=="reloadPage"){
                        setInterval(function(){document.location.reload();},2000);
                    }else if(Sigma.GridCache["theDataOpeGrid"]){
                        Sigma.GridCache["theDataOpeGrid"].reload();
                    }
                    theThis.content(msg['info']).time(2000).button({id: 'ok',disabled: true},{id:'cancel',value:'关闭'});
                }
            },
            dataType : "json"
        });
    }
    return false;
}

