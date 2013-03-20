/**
 * DataOpe的扩展js操作，比如：删除、修改、状态改变等等。
 * */
function dataOpeAdd(initData,dialogTitle){
    $.dialog({
        id:"addObject",
        title:dialogTitle,
        content:'正在加载页面!<img src="' + PUBLIC_URL + '/public/loading.gif" />',
        esc:true,
        lock:true,
        padding:"0",
        ok:function(){
            if($("#itemAddForm").length<1) return true;
            if(!$('#itemAddForm').validationEngine('validate')){
                return false;
            }
            //触发savedata事件,用于支持fckeditor保存数据.
            $('#itemAddForm').find(":input").trigger("savedata");
            
            var theThis		= this;
            $.ajax({
                type : "POST",
                url : URL_URL + "/save",
                data : $("#itemAddForm").serialize(),
                success : function(msg) {
                    if (msg["status"] == 0) {
                        showDialog("提示",msg["info"]);
                    } else if(msg["status"] == 2) {
                    	theThis.content(msg['info']);
                        theThis.button({
                            id: 'ok',
                            disabled: true
                        },{
                            id:'cancel',
                            disabled: true
                        });
                    	setTimeout(document.location.href= APP_URL + msg["data"],3000);
                    } else {
                        theThis.content(msg['info']);
                        theThis.time(3000);
                        theThis.button({
                            id: 'ok',
                            disabled: true
                        },{
                            id:'cancel',
                            value:'关闭'
                        });
                        if(Sigma.GridCache["theDataOpeGrid"]){
                        	Sigma.GridCache["theDataOpeGrid"].reload();
                        }
                    }
                },
                dataType : "json"
            });
            return false;
        },
        okValue:"确定",
        cancelValue:"取消",
        cancel:function(){},
        initialize:function(){
            var theThis   	= this;
            $.get(URL_URL + "/add?" + initData,function(html){
                theThis.content(html);
                //需要排除日期类型的输入框(日期类型的输入框在获得焦点后不能弹出日期选择框.)
                $(theThis.dom.main).contents().find(":input:visible").not(".Wdate").eq(0).focus();
            });
        }
    });
}

function dataOpeEdit(id,dialogTitle,modelName){
	var this_post_url	= URL_URL;
	if(dialogTitle=="" || dialogTitle==0 || dialogTitle==undefined) dialogTitle=$("#modelInfo_editTitle").val();
	if(dialogTitle=="" || dialogTitle==0 || dialogTitle==undefined) dialogTitle="修改";
	if(modelName!=undefined) this_post_url	= APP_URL + "/" + modelName;
    $.dialog({
        id:"editObject",
        title:dialogTitle,
        content:'正在加载页面!<img src="' + PUBLIC_URL + '/public/loading.gif" />',
        esc:true,
        padding:"0",
        lock:true,
        ok:function(){
            if($("#itemAddForm").length<1) return true;
            if(!$('#itemAddForm').validationEngine('validate')){
                return false;
            }
            //触发savedata事件,用于支持fckeditor保存数据.
            $('#itemAddForm').find(":input").trigger("savedata");
            var theThis		= this;
            $.ajax({
                type : "POST",
                url : this_post_url + "/save",
                data : $("#itemAddForm").serialize(),
                success : function(msg) {
                    if (msg["status"] == 0) {
                        showDialog("提示",msg["info"]);
                    } else {
                    	if(Sigma.GridCache["theDataOpeGrid"]){
                    		Sigma.GridCache["theDataOpeGrid"].reload();
                    	}
                        theThis.content(msg['info']).time(2000).button({
                            id: 'ok',
                            disabled: true
                        },{
                            id:'cancel',
                            value:'关闭'
                        });
                    }
                },
                dataType : "json"
            });
            return false;
        },
        okValue:"保存",
        cancelValue:"取消",
        cancel:function(){},
        initialize:function(){
            var theThis   	= this;
            $.get(this_post_url + "/edit/" + id,function(html){
                theThis.content(html);
            });
        }
    });
}
function dataOpeDelete(id,msg){
	if(msg==undefined) msg="确定要删除此数据?";
    $.dialog({
        id:"deleteDataOpeItem",
        title:"提醒",
        lock:true,
        content:msg,
        ok:function(){
            _this	= this;
            $.get(URL_URL+"/delete/"+id,function(data){
                if(data.status){
                    Sigma.GridCache["theDataOpeGrid"].reload();
                }
                _this.time(2000).title("提示").content(data.info).button({
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
        cancel:function(){},
        cancelValue:"取消"
    });
}

/**
 * 数据查询函数
 * */
function getDataSearchUrl(){
	var para	= new Object();
    $("input.dataOpeSearch").each(function(){
        if($(this).val()=="") return;
        if($(this).attr("type")=="radio"){
            if($(this).attr("checked")=="checked"){
                para[$(this).attr("id")]	= $(this).val();
            }
        }else{
            var tPara	= $(this).val();
            if($(this).hasClass("likeLeft")) tPara	= "%" + tPara;
            if($(this).hasClass("likeRight")) tPara	= tPara + "%";
            para[$(this).attr("id")]	= tPara;
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

function resetPasswd(id){
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
        	var _this	= this;
        	$.get(URL_URL + "/resetPassword?i="+id+"&p="+input.value,
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
 * 上传文件 for  add  edit
 * acceptFileTypes : fileType,
 * uploadTemplateId : uploadTemplateId,
 * templatesContainer : tmplContainer,
 * */
function uploadFile(fileObject,options){
	option	= $.extend(options,{
		url:APP_URL + "/Basic/upload_file",
		dataType : 'json',
		autoUpload : true,
		fileInput:$(fileObject).find("input[type='file']"),
		singleFileUploads : true,
		forceIframeTransport:true,
        uploadTemplateId: 'template-upload',
        downloadTemplateId: 'template-download'
		});
	fileObject.fileupload(option);
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
        	xyz	= $("#selectXY");
        	if(xyz.text()==""){
        		showDialog("提醒","请先上传头像文件!");
        		return false;
        	}
        	if(xyz.width()==0 || xyz.height()==0){
        		showDialog("提醒","请先选择头像区域!");
        		return false;
        	}
        	var _this	= this;
            $.ajax({
                type : "POST",
                url : APP_URL + "/Basic/cut_img",
                data : {"img":xyz.text(),
        		 "width":xyz.width(),"height":xyz.height(),
        		 "left":xyz.css("marginLeft"),
        		 "top":xyz.css("marginTop")},
                success : function(data){
	            	if(data.status){
	            		$(img).attr("src",APP_URL + "/" + data.data.url);
	            		$(input).val(data.data.file);
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
            var theThis   	= this;
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
	var url	= $(obj).find(a[download]);
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