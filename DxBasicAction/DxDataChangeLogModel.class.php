<?php
class DxDataChangeLogModel extends  DxExtCommonModel{
    public $listFields = array (
        "model_name_cn" => array ('title'=>'操作对象','type'=>'string'),
        'event'         => array ('type' =>'string','width'=>80,'title'=>"操作类型","valChange"=>array("insert"=>"新增","update"=>"更新","delete"=>"删除")),
        "module_name"   => array ('title'=>'模块','type'=>'string','hide'=>07767),
        "action_name"   => array ('title'=>'用户操作','type'=>'string','renderer' => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                    var v  = record['module_name']+'/' + value + '';
                    return v;
                }",'width'=>180,
            ),
        "create_time"   => array ('title'=>'操作时间','type'=>'date'),
        'creater_user_name'     => array ('title'=>'操作人','type' =>'string','width'=>80),
        'creater_user_id'       => array ('type' =>'int','width'=>10,'hide'=>07777),
        'data'          => array ('title'=>'数据','type' =>'string','width'=>2000),
        'data_str'      => array ('type' =>'string','hide'=>07777),
        'options_ser'   => array ('type' =>'string','hide'=>07777),
        "options"       => array ('title'=>'操作范围',"width"=>"2100",'type'=>"string"),
    );
    protected $modelInfo=array(
        "title"=>'数据变更记录','readOnly'=>true,"order"=>"create_time DESC",
        'searchHTML'=>"
                <span class='add-on'>操作人:</span>
                <input size='5' placeholder='操作人' type='text' class='z_input' name='%creater_user_name%' id='creater_user_name' value=''/>
                <span class='add-on'>从:</span>
                <input size='18' placeholder='开始时间' type='text' class='Wdate' name='egt_create_time' id='egt_create_time' value='' onfocus='WdatePicker({\"dateFmt\":\"yyyy-MM-dd HH:mm\"})' />
                <span class='add-on'>到:</span>
                <input size='18' placeholder='结束时间' type='text' class='Wdate' name='elt_create_time' id='elt_create_time' value='' onfocus='WdatePicker({\"dateFmt\":\"yyyy-MM-dd HH:mm\"})' />
                <button onclick='javascript:dataOpeSearch(\"dataListSearch\");' class='btn btn-info btn-sm' id='item_query_items'>查询</button>
                <button onclick='javascript:dataOpeSearch(\"\");' class='btn btn-info btn-sm' id='item_query_all' />全部数据</button>
        "
    );
}

