/**
 * angularJs的代码
 */
var dxAngularM = angular.module("dxApp",[]);
var cantonFdnTree = false;
if(cantonFdnTree===false){
    cantonFdnTree = new Array();
    $.get(APP_URL+"/Canton/getSelectSelectSelect",function(data){
        var cantonLength = data.length;
        for (i=0;i<cantonLength;++i) {
            if (undefined == cantonFdnTree[data[i].parent_id]) {
                cantonFdnTree[data[i].parent_id] = new Array();
            }
            cantonFdnTree[data[i].parent_id].push(data[i]);
        };
    },"json");
}

dxAngularM.controller("dataEditCtrl",function($scope){
    $scope.cantonTree = cantonFdnTree;
    $scope.dataInfo = recordDataInfo;

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

    $scope.cantonChange = function(selectCanton,cantonFdn,textTo){
        if(selectCanton!=undefined && selectCanton!=null && selectCanton!=0) eval("$scope." + cantonFdn + "= selectCanton;");
    };
});

dxAngularM.filter('cantonFdnToArray', function() {
    return function(fdn,cantonFdnName) {
        if(fdn==undefined || fdn==0){
            return new Array();
        }
        var ta = fdn.split(".");
        ta.pop();
        angular.forEach(ta,function(val,key){ta[key]=parseInt(val);});
        return ta;
    }
});

dxAngularM.filter('cantonOptionSelected', function() {
    return function(selectedFdn,optionFdn) {
        if(selectedFdn==undefined) return false;
        return selectedFdn.substr(0,optionFdn.length)==optionFdn;
    }
});

