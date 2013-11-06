/**
 * angularJs的代码
 */
var dxAngularM = angular.module("dxApp",[]);

dxAngularM.controller("dataEditCtrl",function($scope){
    $scope.isEdit = true;//新增页面也是修改页面，只不过id=0而已
    if(recordDataInfo.id == "0")
        $scope.isAdd = true;  //要区分新增和修改，使用不同的js数据验证规则
    $scope.cantonTree = cantonFdnTree;
    $scope.dataInfo = recordDataInfo;
    $scope.dataFields = recordDataFields;

    //设置文件上传组件
    angular.element("input[alt='uploadFile']").each(function(i,input){
        obj = angular.element(input);
        var opt = angular.fromJson(obj.attr("uploadOption"));
        eval("var oldValue = recordDataInfo."+obj.attr("id"));
        if(oldValue==undefined) oldValue = { };
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

    $("#itemAddForm").validationEngine({
        //ajaxFormValidationMethod: 'post',
        onValidationComplete:formSubmitComplete
    });

    $scope.cantonChange = function(selectCanton,cantonFdn,textTo){
        if(selectCanton!=undefined && selectCanton!=null && selectCanton!=0) eval("$scope." + cantonFdn + "= selectCanton;");
    };
    $scope.editTheData = function(){
        $scope.isEdit = !$scope.isEdit;
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
        if(isEdit) return editClass;
        if(isAdd) return addClass;
    }
});

