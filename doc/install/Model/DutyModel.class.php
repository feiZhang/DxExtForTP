<?php
class DutyModel extends DxExtCommonModel {
    protected $listFields = array (
        "duty_id" => array("pk"=>true,'title'=>'操作',"hide"=>22,
                'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v   = '<a href=\"javascript:dataOpeEdit({ \'id\':' + value + '});\">修改</a>';
                                    v   += ' <a href=\"javascript:dataOpeDelete({ \'id\':' + value + '});\">删除</a>';
                                    return v;
                                }","width"=>"80"
        ),
        "name" => array ('title'=>'职务名称',"width"=>"230",),
        "creater_user_id"   => array("title" => "创建人","hide"=>06,"width" => 70,"valChange"=>array("model"=>"Account")),
        "create_time"       => array ('title'=>'创建时间','hide'=>6,'type'=>'date'),
    );
    protected $modelInfo=array(
        "dictTable"=>"duty_id,name",
        "title"=>'职务管理','readOnly'=>false,
        'searchHTML'=>"
          <span class='add-on'>职务名称:</span>
          <input id='title' name='%name%' size='10' class='z-input' value='' type='text' />
          <button onclick='javascript:dataOpeSearch(\"dataListSearch\");' class='btn btn-info btn-sm' id='item_query_items'>查询</button>
          <button onclick='javascript:dataOpeSearch(\"\");' class='btn btn-info btn-sm' id='item_query_all' />全部数据</button>
        ",
    );
 
}

