<?php
class DxFieldInput{
    static function create($fieldSet){
        // searchPara 将字段转换为查询框的时候，需要附加的字段
        if(!empty($fieldSet["editor"])){
            return $fieldSet["editor"];
        }else{
            $validateMsg = sprintf("data-errormessage=\"%s\" regex=\"%s\"",$fieldSet["valid"]["validateMsg"],$fieldSet["valid"]["regex"]);

            $inputRV = "";
            if($fieldSet["readOnly"]!==true){
                $inputRV = DxFieldInput::createInputHtml($fieldSet);
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
                if(!empty($fieldSet["textTo"]))
                    $inputRV .= sprintf("<span ng-hide=\"%2\$s\" ng-bind=\"dataInfo.%1\$s\"></span>",$fieldSet["textTo"],$fieldSet["readOnly"]!==true?"isEdit":"");
                else
                    $inputRV .= sprintf("<span ng-hide=\"%2\$s\" ng-bind=\"dataInfo.%1\$s_textTo\"></span>",$fieldSet["name"],$fieldSet["readOnly"]!==true?"isEdit":"");

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

    static private function createInputHtml($fieldSet){
            switch($fieldSet["type"]){
            case "uploadFile":
                if(empty($fieldSet['upload']['buttonValue'])) $uploadButtonValue = "新增文件";
                else $uploadButtonValue = $fieldSet['upload']['buttonValue'];
                $sysUploadImgType = C("SysSetting.UPLOAD_IMG_FILETYPE")==""?C("UPLOAD_IMG_FILETYPE"):C("SysSetting.UPLOAD_IMG_FILETYPE");
                $uploadFileType = empty($fieldSet["upload"]["filetype"])?$sysUploadImgType:$fieldSet["upload"]["filetype"];
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
                        "downLoadBaseUrl"=>C("UPLOAD_BASE_URL"),
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
                if(!empty($fieldSet["textTo"])) $inputAddr = sprintf(' textTo" textTo="%s',$fieldSet['textTo']);
                else $inputAddr = "";

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
                $inputType = "radio";
                if(!empty($fieldSet["textTo"])) $inputType = sprintf("%s\" class=\"textTo\" textTo=\"%s",$inputType,$fieldSet["textTo"]);
                $inputRV = sprintf('<span ng-show="isEdit">');
                $inputRV .= sprintf('<span ng-repeat="(key,val) in dataFields.%s.valChange">',$fieldSet["name"]);
                $inputRV .= sprintf("<input type=\"%1\$s\" name=\"%2\$s\" id=\"%2\$s\" value=\"{{key}}\" text=\"{{val}}\" 
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
                                            ng-model="dataInfo.%1$s"%4$s class="autowidth%5$s" ng-show="isEdit" ng-options="key as val for (key,val) in dataFields.%1$s.valChange">',
                    $fieldSet["name"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputAddr,$textTo);
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
                    $inputRV = sprintf('<textarea ng-model="dataInfo.%1$s" rows="%2$d" style="width:200px" name="%1$s" id="%1$s" placeholder="%3$s" 
                                                ng-class="isEdit | validClass:isAdd:\'%5$s\':\'%4$s\'" ng-show="isEdit" %7$s></textarea>',
                        $fieldSet["name"],round(intval($fieldSet["width"])/1000),$fieldSet["note"],$fieldSet["valid"][MODEL::MODEL_INSERT],$fieldSet["valid"][MODEL::MODEL_UPDATE],$inputType,$validateMsg);
                }
                break;
            }
        return $inputRV;
    }
}

