<?php
// searchPara 将字段转换为查询框的时候，需要附加的字段
if(!empty($field["editor"])){
    printf($fieldSet["editor"]);
}else{
    switch($fieldSet["type"]){
    case "uploadFile":
        if(empty($fieldSet['upload']['buttonValue'])) $uploadButtonValue = "新增文件";
        else $uploadButtonValue = $fieldSet['upload']['buttonValue'];
        $uploadFileType = empty($fieldSet["upload"]["filetype"])?C("SysSet.UPLOAD_IMG_FILETYPE"):$fieldSet["upload"]["filetype"];
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
    case "date":
        //设置弹出的格式及限制条件
        if(empty($fieldSet["valFormat"])) $dateFormat = array("dateFmt"=>"yyyy-MM-dd");
        else $dateFormat = array("dateFmt"=>$fieldSet['valFormat']);
        if(!empty($fieldSet['maxvalue'])){
            $dateFormat['maxDate'] = $fieldSet['maxvalue'];
        }
        if(!empty($fieldSet['minvalue'])){
            $dateFormat['minDate'] = $fieldSet['minvalue'];
        }
        $inputWidth = strlen($dateFormat["dateFmt"])*8+10;
        printf('<input style="width:%4$dpx" type="text" ng-model="dataInfo.%1$s" name="%1$s" id="%1$s" value="" placeholder="%2$s" onfocus="%3$s" class="Wdate" />%5$s',
            $fieldSet["name"],$fieldSet["note"],DxFunction::escapeHtmlValue("WdatePicker(".json_encode($dateFormat).")"),$inputWidth,$fieldSet["danwei"]);
        break;
    case "canton":
        if(!empty($fieldSet["textTo"])) $inputAddr = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
        else $inputAddr = "";

        printf('
<select class="autowidth cantonSelect%2$s" ng-show="cantonTree[canton_id].length" ng-model="selectedCanton.%1$s" ng-change="cantonChange(selectedCanton.%1$s,\'dataInfo.%1$s\')" ng-repeat="canton_id in dataInfo.%1$s | cantonFdnToArray:\'dataInfo.%1$s\'">
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
    case "set":
        switch($fieldSet["type"]){
        case "set":
            $inputType = "checkbox";
            break;
        default:
            $inputType = "radio";
            break;
        }
        if(!empty($fieldSet["textTo"])) $inputType = sprintf("%s\" class=\"textTo\" textTo=\"%s",$inputType,$fieldSet["textTo"]);
        foreach($fieldSet["valChange"] as $key => $val){
            printf("<input type=\"%s\" name=\"%s%s\" id=\"%s\" value=\"%s\" text=\"%6\$s\" ng-model=\"dataInfo.%2\$s\" />%6\$s",
                $inputType,$fieldSet["name"],$inputType=="checkbox"?"[]":"",$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val));
        }

        break;
        case "select":
            $inputAddr = empty($fieldSet["multiple"])?"":" multiple";
            if(!empty($fieldSet["textTo"])) $inputAddr = sprintf('%s class="textTo" textTo="%s">',$inputAddr,$fieldSet['textTo']);
            printf('<select name="%s" id="%s" class_add="%s" class_edit="%s" class="autowidth" ng-model="dataInfo.%1$s"%s>',$fieldSet["name"],$fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputAddr);
            printf('<option value="">请选择</option>');
            foreach($fieldSet["valChange"] as $key => $val){
                printf("<option value=\"%s\">%s</option>",$key,DxFunction::escapeHtmlValue($val));
            }
            printf('</select>');
            break;
        case "password":
            $inputType = "password";
        case "string":
            $inputType = "text";
        case "text":
        default:
            $inputType = "text";
            if($fieldSet["width"]<1000){
                printf('<input ng-model="dataInfo.%1$s" style="width:%7$dpx" type="%6$s" name="%1$s" id="%1$s" placeholder="%3$s" class="dataOpeSearch likeRight likeLeft" class_add="%4$s" class_edit="%5$s" value="" />%2$s',
                    $fieldSet["name"],$fieldSet["danwei"],$fieldSet["note"],$fieldSet["vaild"][MODEL::MODEL_INSERT],$fieldSet["vaild"][MODEL::MODEL_UPDATE],$inputType,$fieldSet["width"]);
            }else{
                printf('<textarea ng-model="dataInfo.%1$s" rows="%2$d" style="width:200px" name="%1$s" id="%1$s" placeholder="%3$s" class="dataOpeSearch likeRight likeLeft" class_add="%4$s" class_edit="%5$s"></textarea>',
                    $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["vaild"][MODEL::MODEL_INSERT],$fieldSet["vaild"][MODEL::MODEL_UPDATE],$inputType);
            }
            break;
    }
}

