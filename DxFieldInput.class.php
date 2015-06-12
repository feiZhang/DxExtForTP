<?php
class DxFieldInput{
    static function create($fieldSet,$defaultVal=""){
        if(empty($fieldSet)) return "";
        // searchPara 将字段转换为查询框的时候，需要附加的字段
        if(!empty($fieldSet["editor"])){
            return $fieldSet["editor"];
        }else{
            $validateMsg = sprintf("data-errormessage=\"%s\" regex=\"%s\"",$fieldSet["valid"]["validateMsg"],$fieldSet["valid"]["regex"]);

            $inputRV = "";
            if($fieldSet["readOnly"]!==true){
                if($fieldSet["type"]=="canton" || $fieldSet["type"]=="selectselectselect" || $fieldSet["type"]=="enum" || $fieldSet["type"]=="select"){
                    //angular生成的select多一个空的option，最新版也是有这个bug
                    $inputRV = DxFieldInput::createInputHtml($fieldSet,$defaultVal);
                }else{
                    $inputRV = DxFieldInput::createInputHtml_Angular($fieldSet,$defaultVal);
                }
            }else if(!empty($defaultVal)){
                $inputRV = sprintf("<input type='hidden' name='%1\$s' id='%1\$s' value='%2\$s' />",$fieldSet["name"],$defaultVal);
            }

            //显示视图模式的内容
            switch($fieldSet["type"]){
            case "uploadFile":
            case "cutPhoto":
                break;
            case "canton":
            case "selectselectselect":
                $inputRV .= sprintf("<span ng-hide=\"%2\$s\" ng-bind=\"dataInfo.%1\$s | fdnToText:dataFields.%1\$s.valChange\"></span>",$fieldSet["name"],$fieldSet["readOnly"]!==true?"isEdit":"");
                break;
            case "enum":
            case "select":
            case "set":
            case "dialogSelect":
                if(!empty($fieldSet["textTo"])){
                    $inputRV .= sprintf("<span ng-hide=\"%2\$s\" class=\"fieldShowValue\" ng-bind=\"dataInfo.%1\$s\"></span>",$fieldSet["textTo"],$fieldSet["readOnly"]!==true?"isEdit":"");
                }else{
                    $inputRV .= sprintf("<span ng-hide=\"%2\$s\" class=\"fieldShowValue\" ng-bind=\"dataInfo.%1\$s_textTo\"></span>",$fieldSet["name"],$fieldSet["readOnly"]!==true?"isEdit":"");
                }
                break;
            case "password":
                $inputRV .= sprintf("<span ng-hide=\"%1\$s\">******</span>",$fieldSet["readOnly"]!==true?"isEdit":"");
                break;
            default:
                $inputRV .= sprintf("<span ng-hide=\"%2\$s\" ng-bind=\"dataInfo.%1\$s\"></span>",$fieldSet["name"],$fieldSet["readOnly"]!==true?"isEdit":"");
                break;
            }
        }
        if(!empty($fieldSet["danwei"])) $inputRV .= sprintf("<span class=\"help-inline\">%s</span>",$fieldSet["danwei"]);
        return $inputRV;
    }

    //使用angular生成输入框。
    static public function createInputHtml_Angular($fieldSet,$defaultVal=""){
            switch($fieldSet["type"]){
            case "editer":
                $inputRV = sprintf('<script id="editer_%s" name="editer_%s" type="text/plain" style="width:500px;height:200px;">',$fieldSet["name"],$fieldSet["name"]);
                $inputRV .= '</script>';
                break;
            case "uploadFile":
                if(empty($fieldSet['upload']['buttonValue'])) $uploadButtonValue = "新增文件";
                else $uploadButtonValue = $fieldSet['upload']['buttonValue'];
                $sysUploadImgType = C("SysSetting.UPLOAD_IMG_FILETYPE")==""?C("UPLOAD_IMG_FILETYPE"):C("SysSetting.UPLOAD_IMG_FILETYPE");
                $uploadFileType = array_key_exists("filetype", $fieldSet["upload"])?$fieldSet["upload"]["filetype"]:$sysUploadImgType;
                $uploadFileNums = intval($fieldSet["upload"]["maxNum"])<0?1:intval($fieldSet["upload"]["maxNum"]);
                if($uploadFileNums>1){
                    $uploadButtonValue .= "最多".$uploadFileNums."个";
                }
                //默认文件最大大小为2M
                $uploadFileMaxSize = empty($fieldSet["upload"]["maxSize"])?1024*1024*2:intval($fieldSet["upload"]["maxSize"]);

                $uploadOption = array(
                        "acceptFileTypes"=>$uploadFileType,
                        "maxNumberOfFiles"=>$uploadFileNums,
                        "maxFileSize"=>$uploadFileMaxSize,
                        );

                $inputRV = sprintf('
                        <div id="%1$s">
                        <table class="table table-striped">
                            <tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody>
                        </table>
                        <span ng-show="isEdit" class="btn btn-success fileinput-button">
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
                        <input type="text" alt="uploadFile" ng-hide="true" ng-model="dataInfo.%1$s" name="old_%1$s" id="%1$s" value="" uploadOption=\'%4$s\' />
                        </div>
                        ',
                        $fieldSet["name"],$uploadButtonValue,$uploadFileType,DxFunction::escapeJson($uploadOption));
                break;
            case "cutPhoto":
                $defaultPhoto = C("CUT_PHOTO_DEFAULT_IMG");
                $inputRV = sprintf('<a href="javascript:if($(\'#dataIsEdit\').val()==\'1\'){showUploadPhoto($(\'img#%1$s\'),$(\'input#%1$s\'));}"> 
                    <img id="%1$s" src="__APP__/Basic/showImg?f={{dataInfo.%1$s}}" onerror="src=\'__DXPUBLIC__/basic/images/%2$s\'" title="点击编辑相片" alt="点击编辑相片" width="96" height="100" border=0 />
                    </a> 
                    <input type="text" ng-hide="true" name="%1$s" value="" id="%1$s"/>
                    <input type="text" ng-hide="true" name="old_%1$s" ng-model="dataInfo.%1$s" value="" />',
                    $fieldSet["name"],$defaultPhoto);
                break;
            case "canton":
            case "selectselectselect":
                if(!empty($fieldSet["textTo"])){
                    $inputAddr = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
                }else{
                    $inputAddr = "";
                }

                $inputRV = sprintf('
                    <span ng-show="isEdit" style="display:inline">
                    <select class="autowidth fdnSelectSelect%2$s" ng-show="dataFields.%1$s.fdnChange[fdnNode].length" ng-model="selectedFdn.%1$s" ng-change="selectselectselectChange(this,selectedFdn.%1$s,\'dataInfo.%1$s\',\'%3$s\')" ng-repeat="fdnNode in dataInfo.%1$s | fdnStrToArray">
                        <option ng-repeat="fdnObj in dataFields.%1$s.fdnChange[fdnNode]" ng-selected="dataInfo.%1$s|fdnOptionSelected:fdnObj.fdn" key="{{fdnObj.pkid}}" text_name="{{fdnObj.full_name}}" value="{{fdnObj.fdn}}">{{fdnObj.name}}</option>
                    </select>

                    <input type="text" ng-hide="true" name="%1$s" id="%1$s" ng-model="dataInfo.%1$s" value="" class="dataOpeSearch likeRight" />
                    </span>'
                    ,$fieldSet["name"],$inputAddr,empty($fieldSet["fdn"]["pkid_name"])?"":"dataInfo.".$fieldSet["fdn"]["pkid_name"]);
                break;
            case "dialogSelect":
                /*
                $dialogSelectTemp = sprintf('
                <div id="dialogSelectShowDialog_%s" class="modal fade bs-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h4 class="modal-title" id="myLargeModalLabel">%s</h4>
                            </div>
                            <div class="modal-body">
                                {{ dialogSelectMsg }}
                                <table class="table table-bordered">
                                    <tr ng-repeat="dataInfo in dialogSelectDataList">
                                        <td ng-repeat="field in dialogSelectShowFields">{{dataInfo.field}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>',$fieldSet["name"],$fieldSet["dialogSelect"]["title"]);
                $inputRV = sprintf('<input type="button" data-target="#dialogSelectShowDialog_%s" value="%s" class="btn btn-info btn-sm" data-toggle="modal" ng-click=\'dialogSelectField(%s,"%s");\' />',
                                            $fieldSet["name"],$fieldSet["dialogSelect"]["title"],DxFunction::escapeJson($fieldSet["dialogSelect"]),$fieldSet['textTo']);
                $inputRV .= $dialogSelectTemp;
                */
                $inputRV = sprintf('
                                    <input type="text" style="width:80px" readonly id="%1$s" ng-class="isEdit | validClass:isAdd:\'%6$s\':\'%5$s\'" />
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" style="width:140px" placeholder="%4$s" id="%1$s_select">
                                        <span class="input-group-btn">
                                            <input type="button" class="btn btn-default" onclick=\'javascript:dialogSelectField(%2$s,"%3$s",$(this),$("#%1$s_select").val());\' value="查" />
                                        </span>
                                    </div>
                                    ',
                                            $fieldSet["name"],DxFunction::escapeJson($fieldSet["dialogSelect"]),$fieldSet['textTo'],$fieldSet["dialogSelect"]["title"],
                                            $fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                break;
            case "enum":
                $inputType = "radio\" class=\"autowidth textTo";
                if(!empty($fieldSet["textTo"])) $inputType = sprintf("radio\" class=\"autowidth textTo\" textTo=\"%s",$fieldSet["textTo"]);
                $inputRV = sprintf('<span ng-show="isEdit">');
                $inputRV .= sprintf('<span ng-repeat="(kee,val) in dataFields.%s.valChange">',$fieldSet["name"]);
                $inputRV .= sprintf("<input type=\"%1\$s\" name=\"%2\$s\" id=\"%2\$s\" value=\"{{kee}}\" text=\"{{val}}\" 
                                                ng-model=\"dataInfo.%2\$s\" ng-class=\"isEdit | validClass:isAdd:'%4\$s':'%3\$s'\" />{{val}}",
                        $inputType,$fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                $inputRV .= '</span>';
                /*
                foreach($fieldSet["valChange"] as $key => $val){
                    $inputRV .= sprintf("<input type=\"%1\$s\" name=\"%2\$s\" id=\"%2\$s\" value=\"%3\$s\" text=\"%4\$s\" ng-model=\"dataInfo.%2\$s\" ng-class=\"isEdit | validClass:isAdd:'%6\$s':'%5\$s'\" />%4\$s",
                        $inputType,$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val),$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                }
                */
                $inputRV .= sprintf('</span>');
                break;
            case "set":
                $inputType = "checkbox";
                if(!empty($fieldSet["textTo"])) $inputType = sprintf("%s\" class=\"textTo\" textTo=\"%s",$inputType,$fieldSet["textTo"]);
                $inputRV = sprintf('<span ng-show="isEdit">');
                $inputRV .= sprintf('<span ng-repeat="(key,val) in dataFields.%s.valChange">',$fieldSet["name"]);
                $inputRV .= sprintf("<input type=\"%1\$s\" name=\"%2\$s[]\" id=\"%2\$s\" value=\"{{key}}\" text=\"{{val}}\" 
                                        ng-checked=\"dataInfo.%2\$s | checkBoxChecked:key\" ng-class=\"isEdit | validClass:isAdd:'%4\$s':'%3\$s'\" />{{val}}",
                        $inputType,$fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                $inputRV .= '</span>';
                /*
                foreach($fieldSet["valChange"] as $key => $val){
                    $inputRV .= sprintf("<input type=\"%1\$s\" name=\"%2\$s[]\" id=\"%2\$s\" value=\"%3\$s\" text=\"%4\$s\" ng-checked=\"dataInfo.%2\$s | checkBoxChecked:'%3\$s'\" ng-class=\"isEdit | validClass:isAdd:'%6\$s':'%5\$s'\" />%4\$s",
                        $inputType,$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val),$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                }
                */
                $inputRV .= sprintf('</span>');
                $inputRV .= sprintf("<span ng-hide=\"isEdit\" ng-bind=\"dataFields.%1\$s.valChange[dataInfo.%1\$s]\"></span>",$fieldSet["name"]);
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
                $inputRV = sprintf('<input style="width:%4$dpx" type="text" ng-show="isEdit" ng-model="dataInfo.%1$s" name="%1$s" id="%1$s" value="" placeholder="%2$s" onfocus="%3$s" 
                                            class="Wdate datepicker" ng-class="isEdit | validClass:isAdd:\'%6$s\':\'%5$s\'" />',
                    $fieldSet["name"],$fieldSet["note"],DxFunction::escapeHtmlValue("WdatePicker(".json_encode($dateFormat).")"),intval($fieldSet["width"])+15,$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                break;
            case "select":
                $inputAddr = empty($fieldSet["multiple"])?"":" multiple";
                if(!empty($fieldSet["textTo"])) $textTo = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
                else $textTo = "";
                $inputRV = sprintf('<select name="%1$s" id="%1$s" ng-class="isEdit | validClass:isAdd:\'%3$s\':\'%2$s\'" 
                                            ng-model="dataInfo.%1$s"%4$s class="autowidth %6$s %5$s" ng-show="isEdit" ng-options="key as val for (key,val) in dataFields.%1$s.valChange">',
                    $fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputAddr,$textTo,$fieldSet["styleClass"]);
                $inputRV .= sprintf('<option value="">请选择</option>');
                /*
                foreach($fieldSet["valChange"] as $key => $val){
                    $inputRV .= sprintf("<option value=\"%s\">%s</option>",$key,DxFunction::escapeHtmlValue($val));
                }
                */
                $inputRV .= sprintf('</select>');
                break;
            case "password":
                $inputRV = sprintf('<input style="width:120px" ng-model="dataInfo.%1$s" type="password" name="%1$s" id="%1$s" placeholder="%2$s" ng-show="isEdit" class="dataOpeSearch likeRight likeLeft" class_add="%3$s" class_edit="%4$s" value="" />',
                    $fieldSet["name"],$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                break;
            case "idcard"://身份证号
                $inputRV = sprintf('<input ng-model="dataInfo.%1$s" style="width:150px" type="idcard" onblur="checkIdCard(this.value,{%5$s})" 
                                            name="%1$s" id="%1$s" placeholder="%2$s" ng-show="isEdit" ng-class="isEdit | validClass:isAdd:\'%4$s\':\'%3$s\'" value="" %6$s />',
                    $fieldSet["name"],$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$fieldSet["idcard"],$validateMsg);
                if(empty($inputType)) $inputType = "idcard";
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
                    $inputRV = sprintf('<input ng-model="dataInfo.%1$s" style="width:%6$dpx" type="%5$s" name="%1$s" id="%1$s" placeholder="%2$s" ng-show="isEdit" 
                                            ng-class="isEdit | validClass:isAdd:\'%4$s\':\'%3$s\'" %7$s />',
                        $fieldSet["name"],$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType,$fieldSet["width"],$validateMsg);
                }else{
                    $inputRV = sprintf('<textarea ng-model="dataInfo.%1$s" rows="%2$d" style="width:400px" name="%1$s" id="%1$s" placeholder="%3$s" 
                                                ng-class="isEdit | validClass:isAdd:\'%5$s\':\'%4$s\'" ng-show="isEdit" %7$s></textarea>',
                        $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType,$validateMsg);
                }
                break;
            }
        return $inputRV;
    }

    //普通的输入框生成。
    static public function createInputHtml($fieldSet,$defaultVal=""){
        //生成search输入框的时候，会增加searchName，从而使id和name不同。。因为input的id一直使用name进行填充。这里只好新增searchName，以区分name（id）
        if(empty($fieldSet["searchName"])) $fieldSet["searchName"] = $fieldSet["name"];
        switch($fieldSet["type"]){
            case "select":
                $inputAddr = empty($fieldSet["multiple"])?"\"":"\" multiple";
                if(!empty($fieldSet["textTo"])) $inputAddr = sprintf(' textTo%s textTo="%s">',$inputAddr,$fieldSet['textTo']);
                $inputRV = sprintf('<select name="%3$s" id="%1$s" class="isEdit autowidth%2$s>',$fieldSet["name"],$inputAddr,$fieldSet["searchName"]);
                $inputRV .= sprintf('<option value="">请选择</option>');
                foreach($fieldSet["valChange"] as $key => $val){
                    $inputRV .= sprintf("<option value=\"%s\">%s</option>",$key,DxFunction::escapeHtmlValue($val));
                }
                $inputRV .= sprintf('</select>');
                break;
            case "enum":
                $inputType = "radio\" class=\"autowidth";
                if(!empty($fieldSet["textTo"])) $inputType = sprintf("radio\" class=\"autowidth textTo\" textTo=\"%s",$fieldSet["textTo"]);
                $inputRV = sprintf('<span class="isEdit">');
                foreach($fieldSet["valChange"] as $key => $val){
                    $inputRV .= sprintf('<input name="%3$s" id="%1$s" value="%4$s" type="%2$s" text="%5$s" ng-class="isEdit | validClass:isAdd:\'%6$s\':\'%7$s\'" />%5$s',
                        $fieldSet["name"],$inputType,$fieldSet["searchName"],$key,$val,$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE]);
                }
                $inputRV .= sprintf('</span>');
                break;
            case "canton":
            case "selectselectselect":
                $rootFdnId = $fieldSet["canton"]["rootCantonId"];
                if(!empty($fieldSet["textTo"])){
                    if(!empty($fieldSet["texttoattr"])){
                        $inputAddr = sprintf(' textTo" textTo="%s" texttoattr="%s',$fieldSet['textTo'],$fieldSet["texttoattr"]);
                    }else{
                        $inputAddr = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
                    }
                }else $inputAddr = "";
                $spanIdRandom = "selectselectselect_".mt_rand(1000,9999);
                $inputRV = sprintf('<span id="%4$s">
                    <input type="hidden" name="%3$s" id="%1$s" value="" class="autowidth%2$s" />
                    </span>'
                    ,$fieldSet["name"],$inputAddr,$fieldSet["searchName"],$spanIdRandom);
                if(empty($fieldSet["fdnChange"])) $tempSelectData = "cantonFdnTree";
                else $tempSelectData = str_replace("{","{ ",json_encode($fieldSet["fdnChange"]));
                $inputRV .= sprintf('
                                        <script>
                                        $(function(){
                                            var tempSelectData = %5$s;
                                            $.selectselectselect($("#%4$s"),tempSelectData,"%1$s","%2$s","1",function(t){
                                                $("#%1$s").attr("key",$(t).find("option:selected").attr("key"));
                                                $("#%1$s").attr("short_name",$(t).find("option:selected").attr("short_name"));
                                                $("#%1$s").attr("full_name",$(t).find("option:selected").attr("full_name"));
                                                $("#%1$s").val($(t).val());
                                            },"",false,"");
                                        });
                                        </script>
                                    ',$fieldSet["name"],$rootFdnId,$fieldSet["textTo"],$spanIdRandom,$tempSelectData);
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
                    $inputRV = sprintf('<input style="width:%6$dpx" type="%5$s" name="%8$s" id="%1$s" placeholder="%2$s" %7$s />',
                        $fieldSet["name"],$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType,$fieldSet["width"],$validateMsg,$fieldSet["searchName"]);
                }else{
                    $inputRV = sprintf('<textarea ng-model="dataInfo.%1$s" rows="%2$d" style="width:400px" name="%8$s" id="%1$s" placeholder="%3$s" 
                                                ng-class="isEdit | validClass:isAdd:\'%5$s\':\'%4$s\'" ng-show="isEdit" %7$s></textarea>',
                        $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType,$validateMsg,$fieldSet["searchName"]);
                }
                break;
        }
        return $inputRV;
    }
}




// <?php
// // searchPara 将字段转换为查询框的时候，需要附加的字段
// if(!empty($field["editor"])){
// printf($fieldSet["editor"]);
// }else{
// switch($fieldSet["type"]){
// case "uploadFile":
// if(empty($fieldSet['upload']['buttonValue'])) $uploadButtonValue = "新增文件";
// else $uploadButtonValue = $fieldSet['upload']['buttonValue'];
// $uploadFileType = empty($fieldSet["upload"]["filetype"])?C("SysSet.UPLOAD_IMG_FILETYPE"):$fieldSet["upload"]["filetype"];
// $uploadFileNums = intval($fieldSet["upload"]["maxNum"])<0?1:intval($fieldSet["upload"]["maxNum"]);
// if($uploadFileNums>1){
// $uploadButtonValue .= "最多".$uploadFileNums."个";
// }
// //默认文件最大大小为2M
// $uploadFileMaxSize = empty($fieldSet["upload"]["maxSize"])?1024*1024*2:intval($fieldSet["upload"]["maxSize"]);
// $uploadOption = array(
// "acceptFileTypes"=>$uploadFileType,
// "maxNumberOfFiles"=>$uploadFileNums,
// "maxFileSize"=>$uploadFileMaxSize,
// "inputFieldName"=>$fieldSet['name'],
// "downLoadBaseUrl"=>C("UPLOAD_BASE_URL"),
// );
// printf('
// <div id="%1$s">
// <table class="table table-striped">
// <tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody>
// </table>
// <span class="btn btn-success fileinput-button">
// <i class="icon-plus icon-white"></i>
// <span>%2$s</span>
// <input type="file" name="files[]" "%3$s">
// </span>
// <div class="span4 fileupload-progress">
// <div class="fade progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
// <div class="bar" style="width:0%;"></div>
// </div>
// <div class="progress-extended">&nbsp;</div>
// </div>
// </div>
// <script>
// var uploadFile_%1$s_options = %4$s;
// </script>
// <input type="hidden" name="old_%1$s" value="" />',
// $fieldSet["name"],$uploadButtonValue,$uploadFileType,DxFunction::escapeJson($uploadOption));
// break;
// case "cutPhoto":
// printf('<a href="javascript:showUploadPhoto($(\'#%1$s\'),$(\'#%1$s\'));">
// <img id="%1$s" src="__DXPUBLIC__/basic/images/touxiang_default_heibai.jpg" title="点击编辑相片" alt="点击编辑相片" width="96" height="100" border=0 />
// </a>
// <input type="hidden" name="%1$s" value="" id="%1$s"/>
// <input type="hidden" name="old_%1$s" value="" />',
// $fieldSet["name"]);
// break;
// case "date":
// //设置弹出的格式及限制条件
// if(empty($fieldSet["valFormat"])) $dateFormat = array("dateFmt"=>"yyyy-MM-dd");
// else $dateFormat = array("dateFmt"=>$fieldSet['valFormat']);
// if(!empty($fieldSet['maxvalue'])){
// $dateFormat['maxDate'] = $fieldSet['maxvalue'];
// }
// if(!empty($fieldSet['minvalue'])){
// $dateFormat['minDate'] = $fieldSet['minvalue'];
// }
// $inputWidth = strlen($dateFormat["dateFmt"])*8+10;
// printf('<input style="width:%4$dpx" type="text" name="%1$s" id="%1$s" value="" placeholder="%2$s" onfocus="%3$s" class="Wdate" />',
// $fieldSet["name"],$fieldSet["note"],DxFunction::escapeHtmlValue("WdatePicker(".json_encode($dateFormat).")"),$inputWidth);
// break;
// case "canton":
// if(!empty($fieldSet["textTo"])) $inputAddr = sprintf(' textTo" textTo="%s>',$fieldSet['textTo']);
// else $inputAddr = "";
// printf('<span id="selectselectselect_%1$s"></span>
// <input type="hidden" name="%1$s" id="%1$s" value="" class="dataOpeSearch likeRight%2$s" />
// ',$fieldSet["name"],$inputAddr);
// $rootCantonId = intval($listFields[$key]["canton"]["rootCantonId"]);
// if($rootCantonId<1) $rootCantonId = intval(session("canton_id"));
// if($rootCantonId<1) $rootCantonId = intval(C("SysSet.SYS_ROOT_CANTONID"));
// printf('
// <script>
// $.selectselectselect(0,"%1$s",0,"%2$s",function(t){
// $("#%1$s").attr("text",$(t).find("option:selected").attr("key"));
// $("#%1$s").val($(t).val());
// });
// </script>
// ',$fieldSet["name"],$rootCantonId);
// break;
// case "enum":
// case "set":
// switch($fieldSet["type"]){
// case "set":
// $inputType = "checkbox";
// break;
// default:
// $inputType = "radio";
// break;
// }
// if(!empty($fieldSet["textTo"])) $inputType = sprintf("%s\" class=\"textTo\" textTo=\"%s",$inputType,$fieldSet["textTo"]);
// foreach($fieldSet["valChange"] as $key => $val){
// printf("<input type=\"%s\" name=\"%s%s\" id=\"%s\" value=\"%s\" text=\"%6\$s\" />%6\$s",
// $inputType,$fieldSet["name"],$inputType=="checkbox"?"[]":"",$fieldSet["name"],$key,DxFunction::escapeHtmlValue($val));
// }
// break;
// case "select":
// $inputAddr = empty($fieldSet["multiple"])?"":" multiple";
// if(!empty($fieldSet["textTo"])) $inputAddr = sprintf('%s class="textTo" textTo="%s">',$inputAddr,$fieldSet['textTo']);
// printf('<select name="%s" id="%s" class_add="%s" class_edit="%s" class="autowidth"%s>',$fieldSet["name"],$fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputAddr);
// printf('<option value="">请选择</option>');
// foreach($fieldSet["valChange"] as $key => $val){
// printf("<option value=\"%s\">%s</option>",$key,DxFunction::escapeHtmlValue($val));
// }
// printf('</select>');
// break;
// case "password":
// $inputType = "password";
// case "string":
// $inputType = "text";
// case "text":
// default:
// $inputType = "text";
// if($fieldSet["width"]<1000){
// printf('<input style="width:%7$dpx" type="%6$s" name="%1$s" id="%1$s" placeholder="%3$s" class="dataOpeSearch likeRight likeLeft" class_add="%4$s" class_edit="%5$s" value="" />%2$s',
// $fieldSet["name"],$fieldSet["danwei"],$fieldSet["note"],$fieldSet["vaild"][MODEL::MODEL_INSERT],$fieldSet["vaild"][MODEL::MODEL_UPDATE],$inputType,$fieldSet["width"]);
// }else{
// printf('<textarea rows="%2$d" style="width:200px" name="%1$s" id="%1$s" placeholder="%3$s" class="dataOpeSearch likeRight likeLeft" class_add="%4$s" class_edit="%5$s"></textarea>',
// $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["vaild"][MODEL::MODEL_INSERT],$fieldSet["vaild"][MODEL::MODEL_UPDATE],$inputType);
// }
// break;
// }
// }
