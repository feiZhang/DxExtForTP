<?php
class DxSysDicModel extends DxExtCommonModel {
    public $listFields = array (
        "dic_id" => array("pk"=>true,'title'=>'操作',"hide"=>22,
                    'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                        var v   = '<a href=\"javascript:dataOpeEdit({ \'id\':' + value + '});\">修改</a>';
                                        v   += ' <a href=\"javascript:dataOpeDelete({ \'id\':' + value + '});\">删除</a>';
                                        return v;
                                    }",
            ),
        "type" => array ('title' =>'类型','readOnly'=>06,'hide'=>01,'display_none'=>06),
        "code" => array ('title' => '类别值','hide'=>07),
        "name" => array ('title' => '类别',"width"=>"100"),
        "memo" => array ('title' => '备注',"width"=>"300"),
    );

    protected $modelInfo=array(
        "title"=>'数据字典','readOnly'=>false,"enablePrint"=>true,
        "dictTable"=>"type,code,name",
        "helpInfo"=>"请勿轻易删除此界面数据，删除将影响养老院软件字典数据!",
    );
    public function _after_insert($data, $options){
        $this->where(array('dic_id'=>$data['dic_id']))->save(array('code'=>$data['dic_id']));
    }
}

