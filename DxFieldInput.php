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
                "inputFieldName"=>$fieldSet['name'],
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
    <input type="file" name="files[]" "%3$s">
</span>
<div class="span4 fileupload-progress">
    <div class="fade progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
        <div class="bar" style="width:0%;"></div>
    </div>
    <div class="progress-extended">&nbsp;</div>
</div>
</div>
<script>
var uploadFile_%1$s_options = %4$s;
</script>
<input type="hidden" name="old_%1$s" value="" />',
$fieldSet["name"],$uploadButtonValue,$uploadFileType,DxFunction::escapeJson($uploadOption));

        break;
    case "cutPhoto":
        printf('<a href="javascript:showUploadPhoto($(\'#%1$s\'),$(\'#%1$s\'));"> 
            <img id="%1$s" src="__DXPUBLIC__/basic/images/touxiang_default_heibai.jpg" title="点击编辑相片" alt="点击编辑相片" width="96" height="100" border=0 />
            </a> 
            <input type="hidden" name="%1$s" value="" id="%1$s"/>
            <input type="hidden" name="old_%1$s" value="" />',
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
        printf('<input style="width:%4$dpx" type="text" name="%1$s" id="%1$s" value="" placeholder="%2$s" onfocus="%3$s" class="Wdate" />',
            $fieldSet["name"],$fieldSet["note"],DxFunction::escapeHtmlValue("WdatePicker(".json_encode($dateFormat).")"),$inputWidth);
        break;
    case "canton":
        if(!empty($fieldSet["textTo"])) $inputAddr = sprintf(' textTo" textTo="%s>',$fieldSet['textTo']);
        else $inputAddr = "";
        printf('<span id="selectselectselect_%1$s"></span>
                <input type="hidden" name="%1$s" id="%1$s" value="" class="dataOpeSearch likeRight%2$s" />
                ',$fieldSet["name"],$inputAddr);

        $rootCantonId = intval($listFields[$key]["canton"]["rootCantonId"]);
        if($rootCantonId<1) $rootCantonId = intval(session("canton_id"));
        if($rootCantonId<1) $rootCantonId = intval(C("SysSet.SYS_ROOT_CANTONID"));
        printf('
            <script>
            $.selectselectselect(0,"%1$s",0,"%2$s",function(t){
                $("#%1$s").attr("text",$(t).find("option:selected").attr("key"));
                $("#%1$s").val($(t).val());
            });
            </script>
            ',$fieldSet["name"],$rootCantonId);
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
            printf("<input type=\"%s\" name=\"%s%s\" id=\"%s\" value=\"%s\" text=\"%6\$s\" />%6\$s",
                $inputType,$fieldSet["name"],$inputType=="checkbox"?"[]":"",$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val));
        }

        break;
        case "select":
            $inputAddr = empty($fieldSet["multiple"])?"":" multiple";
            if(!empty($fieldSet["textTo"])) $inputAddr = sprintf('%s class="textTo" textTo="%s">',$inputAddr,$fieldSet['textTo']);
            printf('<select name="%s" id="%s" class_add="%s" class_edit="%s" class="autowidth"%s>',$fieldSet["name"],$fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputAddr);
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
                printf('<input style="width:%7$dpx" type="%6$s" name="%1$s" id="%1$s" placeholder="%3$s" class="dataOpeSearch likeRight likeLeft" class_add="%4$s" class_edit="%5$s" value="" />%2$s',
                    $fieldSet["name"],$fieldSet["danwei"],$fieldSet["note"],$fieldSet["vaild"][MODEL::MODEL_INSERT],$fieldSet["vaild"][MODEL::MODEL_UPDATE],$inputType,$fieldSet["width"]);
            }else{
                printf('<textarea rows="%2$d" style="width:200px" name="%1$s" id="%1$s" placeholder="%3$s" class="dataOpeSearch likeRight likeLeft" class_add="%4$s" class_edit="%5$s"></textarea>',
                    $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["vaild"][MODEL::MODEL_INSERT],$fieldSet["vaild"][MODEL::MODEL_UPDATE],$inputType);
            }
            break;
    }
}

