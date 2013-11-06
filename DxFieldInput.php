<?php
// searchPara 将字段转换为查询框的时候，需要附加的字段
if(!empty($field["editor"])){
    printf($fieldSet["editor"]);
}else{
    switch($fieldSet["type"]){
    case "uploadFile":
        if(empty($fieldSet['upload']['buttonValue'])) $uploadButtonValue = "新增文件";
        else $uploadButtonValue = $fieldSet['upload']['buttonValue'];
        $uploadFileType = empty($fieldSet["upload"]["filetype"])?C("SysSetting.UPLOAD_IMG_FILETYPE"):$fieldSet["upload"]["filetype"];
        $uploadFileNums	= intval($fieldSet["upload"]["maxNum"])<0?1:intval($fieldSet["upload"]["maxNum"]);
        if($uploadFileNums>1){
            $uploadButtonValue .= "最多".$uploadFileNums."个";
        }
        //默认文件最大大小为2M
        $uploadFileMaxSize = empty($fieldSet["upload"]["maxSize"])?1024*1024*2:intval($fieldSet["upload"]["maxSize"]);

        $uploadOption = array(
                "acceptFileTypes"=>$uploadFileType,
                "maxNumberOfFiles"=>$uploadFileNums,
                "maxFileSize"=>$uploadFileMaxSize,
                "downLoadBaseUrl"=>C("UPLOAD_BASE_URL"),
                );

        printf('
<div id="%1$s">
<table class="table table-striped">
    <tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody>
</table>
<span class="btn btn-success fileinput-button">
    <i class="icon-plus icon-white"></i>
    <span>%2$s</span>
    <input type="file" name="files[]" multiple />
</span>
<div class="span4 fileupload-progress">
    <div class="fade progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
        <div class="bar" style="width:0%;"></div>
    </div>
    <div class="progress-extended">&nbsp;</div>
</div>
<input type="text" alt="uploadFile" ng-hide="true" ng-model="dataInfo.%1$s" name="old_%1$s" id="%1$s" value="" uploadOption="%4$s" />
</div>
',
$fieldSet["name"],$uploadButtonValue,$uploadFileType,DxFunction::escapeJson($uploadOption));

        break;
    case "cutPhoto":
        printf('<a href="javascript:showUploadPhoto($(\'#%1$s\'),$(\'#%1$s\'));"> 
            <img id="%1$s" src="__DXPUBLIC__/basic/images/touxiang_default_heibai.jpg" title="点击编辑相片" alt="点击编辑相片" width="96" height="100" border=0 />
            </a> 
            <input type="text" ng-hide="true" name="%1$s" value="" id="%1$s"/>
            <input type="text" ng-hide="true" name="old_%1$s" value="" />',
            $fieldSet["name"]);
        break;
    case "canton":
        if(!empty($fieldSet["textTo"])) $inputAddr = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
        else $inputAddr = "";

        printf('
<select ng-disabled="!isEdit" class="autowidth cantonSelect%2$s" ng-show="cantonTree[canton_id].length" ng-model="selectedCanton.%1$s" ng-change="cantonChange(selectedCanton.%1$s,\'dataInfo.%1$s\')" ng-repeat="canton_id in dataInfo.%1$s | cantonFdnToArray">
    <option ng-repeat="canton in cantonTree[canton_id]" ng-selected="dataInfo.%1$s|cantonOptionSelected:canton.val" key="{{canton.canton_id}}" value="{{canton.val}}">{{canton.title}}</option>
</select>
<input type="text" ng-hide="true" name="%1$s" id="%1$s" ng-model="dataInfo.%1$s" value="" class="dataOpeSearch likeRight" />'
            ,$fieldSet["name"],$inputAddr);

        /* jQuery格式
        printf('<span id="selectselectselect_%1$s" class="canton">
                <input type="hidden" name="%1$s" id="%1$s" ng-model="dataInfo.%1$s" value="" class="dataOpeSearch likeRight%2$s" />
                </span>
                ',$fieldSet["name"],$inputAddr);

        printf('
            <script>
            $.selectselectselect(0,"%1$s",0,"%2$s",function(t){
                $("#%1$s").attr("text",$(t).find("option:selected").attr("key"));
                $("#%1$s").val($(t).val());
            });
            </script>
            ',$fieldSet["name"],$rootCantonId);
         */
        break;
    case "enum":
        $inputType = "radio";
        if(!empty($fieldSet["textTo"])) $inputType = sprintf("%s\" class=\"textTo\" textTo=\"%s",$inputType,$fieldSet["textTo"]);
        printf('<span ng-show="isEdit">');
        foreach($fieldSet["valChange"] as $key => $val){
            printf("<input type=\"%1\$s\" name=\"%2\$s\" id=\"%2\$s\" value=\"%3\$s\" text=\"%4\$s\" ng-model=\"dataInfo.%2\$s\" ng-class=\"{ '%6\$s':isEdit,'%5\$s':isAdd}\" />%4\$s",
                $inputType,$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val),$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
        }
        printf('</span>');
        break;
    case "set":
        $inputType = "checkbox";
        if(!empty($fieldSet["textTo"])) $inputType = sprintf("%s\" class=\"textTo\" textTo=\"%s",$inputType,$fieldSet["textTo"]);
        printf('<span ng-show="isEdit">');
        foreach($fieldSet["valChange"] as $key => $val){
            printf("<input type=\"%1\$s\" name=\"%2\$s[]\" id=\"%2\$s\" value=\"%3\$s\" text=\"%4\$s\" ng-checked=\"dataInfo.%2\$s | checkBoxChecked:'%3\$s'\" ng-class=\"{ '%6\$s':isEdit,'%5\$s':isAdd}\" />%4\$s",
                $inputType,$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val),$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
;
        }
        printf('</span>');
        printf("<span ng-hide=\"isEdit\" ng-bind=\"dataFields.%1\$s.valChange[dataInfo.%1\$s]\"></span>",$fieldSet["name"]);
        break;
    case "date":
        //设置弹出的格式及限制条件
        $dateFormat = array("dateFmt"=>$fieldSet['valFormat']);
        $fieldSet["width"] = intval($fieldSet["width"]) + strlen($fieldSet['valFormat']);//由于列表页面和修改页面的字体大小不同，所以这里要作个微调
        if(!empty($fieldSet['maxvalue'])){
            $dateFormat['maxDate'] = $fieldSet['maxvalue'];
        }
        if(!empty($fieldSet['minvalue'])){
            $dateFormat['minDate'] = $fieldSet['minvalue'];
        }
        printf('<input style="width:%4$dpx" type="text" ng-show="isEdit" ng-model="dataInfo.%1$s" name="%1$s" id="%1$s" value="" placeholder="%2$s" onfocus="%3$s" class="Wdate" ng-class="{ \'%6$s\':isEdit,\'%5$s\':isAdd}" />',
            $fieldSet["name"],$fieldSet["note"],DxFunction::escapeHtmlValue("WdatePicker(".json_encode($dateFormat).")"),$fieldSet["width"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
        printf("<span ng-hide=\"isEdit\" ng-bind=\"dataInfo.%1\$s\"></span>",$fieldSet["name"]);
        break;
    case "select":
        $inputAddr = empty($fieldSet["multiple"])?"":" multiple";
        if(!empty($fieldSet["textTo"])) $textTo = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
        else $textTo = "";
        printf('<select ng-disabled="!isEdit" name="%1$s" id="%1%s" ng-class="{ \'%3$s\':isEdit,\'%2$s\':isAdd}" ng-model="dataInfo.%1$s"%4$s class="autowidth%5$s" ng-show="isEdit">',
            $fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputAddr,$textTo);
        printf('<option value="">请选择</option>');
        foreach($fieldSet["valChange"] as $key => $val){
            printf("<option value=\"%s\">%s</option>",$key,DxFunction::escapeHtmlValue($val));
        }
        printf('</select>');
        break;
    case "password":
        printf('<input style="width:120px" type="password" name="%1$s" id="%1$s" placeholder="%2$s" ng-show="isEdit" class="dataOpeSearch likeRight likeLeft" class_add="%3$s" class_edit="%4$s" value="" />',
            $fieldSet["name"],$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
        printf("<span ng-hide=\"isEdit\">******</span>");
        break;
    case "string":
    case "text":
    case "tel":
        if(empty($inputType)) $inputType = "text";
    case "int":
        if(empty($inputType)) $inputType = "number";
    case "email":
        if(empty($inputType)) $inputType = "email";
    case "url":
        if(empty($inputType)) $inputType = "url";
    default:
        if(empty($inputType)) $inputType = "text";
        if($fieldSet["width"]<1000){
            printf('<input ng-model="dataInfo.%1$s" style="width:%6$dpx" type="%5$s" name="%1$s" id="%1$s" placeholder="%2$s" ng-show="isEdit" ng-class="isEdit | validClass:isAdd:\'%4$s\':\'%3$s\'" value="" />',
                $fieldSet["name"],$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType,$fieldSet["width"]);
        }else{
            printf('<textarea ng-model="dataInfo.%1$s" rows="%2$d" style="width:200px" name="%1$s" id="%1$s" placeholder="%3$s" ng-class="{ \'%5$s\':isEdit,\'%4$s\':isAdd}" ng-show="isEdit"></textarea>',
                $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType);
        }
        printf("<span ng-hide=\"isEdit\" ng-bind=\"dataInfo.%1\$s\"></span>",$fieldSet["name"]);
        break;
    }
}
if(!empty($fieldSet["danwei"])) printf("<span class=\"help-inline\">%s</span>",$fieldSet["danwei"]);

