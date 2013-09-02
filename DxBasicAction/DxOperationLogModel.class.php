<?php
class DxOperationLogModel extends DxExtCommonModel {
	protected $listFields = array (
		"creater_user_name" => array ('title'=>'操作人'),
		"create_time" => array ('title'=>'操作时间','width'=>130),
		'ip' => array ('title'=>'操作人IP',"width"=>"130",),
		"action_name" => array ('title'=>'执行的操作',),
		"module" => array ("hide"=>7),
        "action" => array ('title'=>'Action','hide'=>077777,
                'renderer'	=> "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
									var v	= record['module']+'/' + value + '';
									return v;
									}",'width'=>180,
								),
		"options" => array ('name'=> 'options','title'=>'参数',"width"=>"1100",'type'=>"list","hide"=>7),
		"over_pri" => array ('title'=>'是否越权',"width"=>"80",'valChange'=> array(0=>"是",1=>"否")),
		"other_info" => array ('title'=>'其他信息',"width"=>"80"),
	);
	protected $modelInfo=array(
		"title"=>'系统操作日志','readOnly'=>true,"order"=>"create_time DESC",
	    'leftArea' => "{:W('Menu',array('type'=>\$type,'parent_id'=>\$menu_id))}",
		'searchHTML'=>"
        <span class='add-on'>操作人</span>
	      <input id='create_user_name' size='10' class='dataOpeSearch likeLeft likeRight span2' value='' type='text' />
        <span class='add-on'>从</span>
	      <input id='egt_create_time' class='dataOpeSearch span2' value='' type='text' />
        <span class='add-on'>到</span>
	      <input id='elt_create_time' class='dataOpeSearch span2' value='' type='text' />
		  <button onclick='javascript:dataOpeSearch(true);' class='btn' id='item_query_items'>查询</button>
		  <button onclick='javascript:dataOpeSearch(false);' class='btn' id='item_query_all' />全部数据</button>
        "
	);	
}

