<?php
class DxOperationLogModel extends DxExtCommonModel {
    protected $defaultWhere = array("action_name"=>array("neq",""));
    protected $DP_POWER_FIELDS = array(
        array ('field_name' => 'creater_user_id','auto_type' => 0,'type' => 1,'session_field'=>'login_user_id',"operator"=>"eq"),
    );
    protected $listFields = array (
        "creater_user_name" => array ('title'=>'操作人'),
        "creater_user_id" => array ('hide'=>07777),
        "create_time" => array ('title'=>'操作时间','width'=>130),
        'ip' => array ('title'=>'操作人IP',"width"=>"130",),
        "action_name" => array ('title'=>'执行的操作',),
        "module" => array ("hide"=>7),
        "action" => array ('title'=>'Action','hide'=>077777,
                'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v   = record['module']+'/' + value + '';
                                    return v;
                                    }",'width'=>180,
                                ),
        "options" => array ('name'=> 'options','title'=>'参数',"width"=>"1100",'type'=>"list","hide"=>7),
        "over_pri" => array ('title'=>'是否越权',"width"=>"80",'valChange'=> array(1=>"是",0=>"否")),
        "other_info" => array ('title'=>'其他信息',"width"=>"80"),
    );
    protected $modelInfo=array(
        "title"=>'系统操作日志','readOnly'=>true,"order"=>"create_time DESC",
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

