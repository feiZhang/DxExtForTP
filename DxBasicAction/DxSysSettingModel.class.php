<?php
class DxSysSettingModel extends DxExtCommonModel {
    protected $listFields = array (
        "name" => array ('title'=>'参数','frozen'=>true,'readOnly'=>06,"hide"=>07),
        "memo" => array('title' => '参数说明',"width"=>"400",'readOnly'=>06,),
        "val"  => array ('title' => '参数值','width'=>"8000",),
        "type" => array ('title' => '类型','hide'=>0767),
        "set_id" => array("pk"=>true,'title'=>'操作',"hide"=>22,
            'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                var v = '';
                                if(record['type']=='user'){
                                    v   = '<a class=\"btn btn-xs btn-success\" href=\"javascript:dataOpeEdit({ \'id\':' + value + ',\'isEdit\':true});\">修改</a>';
                                }
                                return v;
                            }",
        ),
    );

    protected $modelInfo=array(
        "title"=>'系统设置','readOnly'=>true,"enablePage"=>false,
        'searchHTML'=>"
                <span class='add-on'>参数说明:</span>
                <input placeholder='参数说明' type='text' class='z_input' name='%memo%' id='memo' value=''/>
                <button onclick='javascript:dataOpeSearch(\"dataListSearch\");' class='btn btn-info btn-sm' id='item_query_items'>查询</button>
                <button onclick='javascript:dataOpeSearch(\"\");' class='btn btn-info btn-sm' id='item_query_all' />全部数据</button>
        ",
        "helpInfo"=>"<div class='alert alert-warning'>请勿轻易修改此数据，不了解的参数修改前，请咨询开发商!</div>",
    );

    protected function _after_update($data, $options){
        parent::_after_update($data, $options);
        $this->cacheData(true);
    }

    protected function _after_insert($data, $options){
        parent::_after_insert($data, $options);
        $this->cacheData(true);
    }

    protected function _after_delete($data, $options){
        parent::_after_delete($data, $options);
        $this->cacheData(true);
    }
}

