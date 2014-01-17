/**
 * angularJs的代码
 */
var dxAngularM = angular.module("dxApp",[]);
//为了controller中传递数据建造的顶层controll
dxAngularM.controller("rootController",function($scope){
    $scope.$on("dialogSelectChange",
               function (event, msg) {
                   $scope.$broadcast("dialogSelectChangeFromParrent", msg);
               });
});

dxAngularM.controller("dataEditCtrl",function($scope,$http,$rootScope){
    //为了独立增加修改页面的需要。。增加此判定。
    if(dataIsEdit!=""){
        $scope.isEdit = dataIsEdit;
    }
    if(recordDataInfo.id == undefined || recordDataInfo.id == "0")
        $scope.isAdd = true;  //要区分新增和修改，使用不同的js数据验证规则
    $scope.cantonTree = cantonFdnTree;
    $scope.dataInfo = recordDataInfo;
    $scope.dataFields = recordDataFields;

    //设置文件上传组件
    angular.element("input[alt='uploadFile']").each(function(i,input){
        obj = angular.element(input);
        var opt = angular.fromJson(obj.attr("uploadOption"));
        eval("var oldValue = recordDataInfo."+obj.attr("id"));
        if(oldValue==undefined || oldValue=='') oldValue = { };
        else oldValue = angular.fromJson(oldValue);
        opt  = $.extend(opt,{
            url:APP_URL + "/Basic/upload_file",
            dataType : 'json',
            autoUpload : true,
            fileInput:angular.element("div#"+obj.attr("id")).find("input[type='file']"),
            singleFileUploads : true,
            initValue:oldValue,
            forceIframeTransport:true,
            inputFieldName:obj.attr("id"),
            uploadTemplateId: 'template-upload',
            downloadTemplateId: 'template-download'
        });
        angular.element("div#"+obj.attr("id")).fileupload(opt);
    });

    //angular的ngBlur老是不触发事件，只能在html中书写了。
    /*
    $scope.idcardCheck = function(thisValue){
        checkIdCard(thisValue,{'birthday':'birthday','sex':'sex','id_reg_addr':'id_reg_addr'});
    }
    */

    $("#itemAddForm").validationEngine({
        //ajaxFormValidationMethod: 'post',
        onValidationComplete:formSubmitComplete
    });

    $scope.cantonChange = function(selectCanton,cantonFdn,textTo){
        if(selectCanton!=undefined && selectCanton!=null && selectCanton!=0) eval("$scope." + cantonFdn + "= selectCanton;");
    };
});

dxAngularM.filter('cantonFdnToArray', function() {
    return function(fdn) {
        if(fdn==undefined || fdn==null || fdn==0){
            return new Array();
        }
        var ta = fdn.split(".");
        ta.pop();
        angular.forEach(ta,function(val,key){ta[key]=parseInt(val,10);});
        return ta;
    }
});
//将fdn转换为中文，因为cantonTree只存放的id数据，所以，还需要数据解析
dxAngularM.filter('cantonFdnToText', function() {
    return function(fdn) {
        if(fdn==undefined || fdn==null || fdn==0 || fdn==""){
            return '';
        }
        var ta = fdn.split(".");
        ta.pop();
        ta = ta.pop();
        if(ta==undefined || ta==null || ta==0 || ta=="") return '';
        return cantonIdValChange[parseInt(ta,10)].text_name;
    }
});

dxAngularM.filter('cantonOptionSelected', function() {
    return function(selectedFdn,optionFdn) {
        if(selectedFdn==undefined) return false;
        return selectedFdn.substr(0,optionFdn.length)==optionFdn;
    }
});

dxAngularM.filter('checkBoxChecked', function() {
    return function(selectVal,theVal) {
        if(selectVal==0 || selectVal=="" || selectVal==undefined) return false;
        selectVal = "," + selectVal + ",";
        return selectVal.indexOf(","+theVal+",")!=-1;
    }
});

dxAngularM.filter('validClass', function() {
    return function(isEdit,isAdd,editClass,addClass) {
        if(isAdd) return addClass;
        else if(isEdit) return editClass;
    }
});

