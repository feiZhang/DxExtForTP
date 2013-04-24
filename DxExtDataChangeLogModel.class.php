<?php
class DxExtDataChangeLogModel extends  DxExtCommonModel{
    public $listFields = array (
            "model_name"	=> array ('title'=>'模块','type'=>'string'),
            "module_name"	=> array ('title'=>'模块','type'=>'string'),
            "action_name"	=> array ('title'=>'执行的操作','type'=>'string'),
            "create_time"	=> array ('title'=>'操作时间','width'=>130,'type'=>'date'),
            "module"	    => array ("hide"=>7,'type'=>'string'),
            "action"	    => array ('title'=>'Action','type'=>'string','renderer'	=> "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                    var v	= record['module']+'/' + value + '';
                    return v;
                }",'width'=>180,
            ),
            'user_name'     => array ('type' =>'string','size'=>1000),
            'user_id'       => array ('type' =>'int','size'=>10),
            'event'         => array ('type' =>'string','size'=>20),
            'data'          => array ('type' =>'string','size'=>1000),
            'data_str'      => array ('type' =>'string','size'=>1000),
            'options_ser'   => array ('type' =>'string','size'=>1000),
            "options"	    => array ('title'=>'参数',"width"=>"1100",'type'=>"string","hide"=>7),
    );
    protected  $_auto = array('ctreat_time','time',1,'function');
    
}