<?php
class DxSysDicModel extends DxExtCommonModel {
    public $listFields = array (
        "dic_id" => array("pk"=>true,'title'=>'操作',"hide"=>22,"width"=>100,
                    'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                        var v   = '<a class=\"btn btn-xs btn-success\" href=\"javascript:dataOpeEdit({ \'id\':' + value + ',\'isEdit\':true});\">修改</a>';
                                        v   += ' <a class=\"btn btn-xs btn-danger\" href=\"javascript:dataOpeDelete({ \'id\':' + value + '});\">删除</a>';
                                        return v;
                                    }",
            ),
        "parent_id" => array("title"=>"父类型","valChange"=>array("model"=>"SysDic"),'hide'=>07777),
        "type" => array ('title' => '类型','readOnly'=>04,'hide'=>01,'display_none'=>06),
        "name" => array ('title' => '类别',"width"=>"100"),
        "memo" => array ('title' => '备注',"width"=>"300"),
    );

    protected $modelInfo=array(
        "title"=>'数据字典','readOnly'=>false,"enablePrint"=>true,
        "dictTable"=>"type,dic_id,name","enablePage"=>false,
        "helpInfo"=>"<div class='alert alert-warning'>请勿轻易删除此界面数据，删除将影响系统的正常使用!</div>",
    );
}

