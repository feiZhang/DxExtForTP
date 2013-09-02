<?php
class DxSysSettingModel extends DxExtCommonModel {
    protected $listFields = array (
        "set_id" => array("pk"=>true,'title'=>'操作',"hide"=>22,
            'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                var v   = '<a href=\"javascript:dataOpeEdit({ \'id\':' + value + '});\">修改</a>';
                                return v;
                            }",
        ),
        "name" => array ('title'=>'参数','frozen'=>true,'readOnly'=>true,"hide"=>07),
        "memo" => array('title' => '参数说明',"width"=>"3000",),
        "val" => array ('title' => '参数值','width'=>"3000",),
    );
    
    protected $modelInfo=array(
        "title"=>'系统设置','readOnly'=>true,"enablePage"=>false,
        'leftArea' => "{:W('Menu',array('type'=>\$type,'parent_id'=>\$menu_id))}",
        'searchHTML'=>"
                <span class='add-on'>参数说明:</span>
                <input id='memo' size='20' class='dataOpeSearch likeLeft likeRight' value='' type='text' />
                <button onclick='javascript:dataOpeSearch(true);' class='btn' id='item_query_items'>查询</button>
                <button onclick='javascript:dataOpeSearch(false);' class='btn' id='item_query_all' />全部数据</button>
        ",
        "helpInfo"=>"请勿轻易修改此数据，不了解的参数修改前，请咨询开发商!",
    );
    
    protected function _after_update($data, $options){
        parent::_after_update($data, $options);
        $this->reSetSetingCache();
    }
    
    protected function _after_insert($data, $options){
        parent::_after_insert($data, $options);
        $this->reSetSetingCache();
    }
    
    protected function _after_delete($data, $options){
        parent::_after_delete($data, $options);
        $this->reSetSetingCache();
    }
    private function reSetSetingCache(){
        $sysSetData = $this->select();
        S("Cache_Global_SysSeting",$sysSetData);
    }
}

