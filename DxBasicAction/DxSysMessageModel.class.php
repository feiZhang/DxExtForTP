<?php
class DxSysMessageModel extends DxExtCommonModel{
    protected $listFields = array (
            "sm_id"     => array('title'=>'操作','width'=>150, 'pk'=>true,'hide'=>06,'renderer'    => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v   = '<a class=\"btn btn-xs btn-success\" href=\"javascript:dataOpeEdit( { \'id\':' + value + '});\">修改</a>';
                                    v   += ' <a class=\"btn btn-xs btn-danger\" href=\"javascript:dataOpeDelete( { \'id\':' + value + '});\">删除</a>';
                                    return v;
                                }"),
            "title" => array('title'=>'标题',"width"=>260),
            "content" => array('title'=>'内容','type'=>'text','width'=>8000),
            "creater_user_id" => array('title'=>'创建人',"hide"=>06,"width"=>80,"valChange"=>array("model"=>"Account")),
            "create_time" => array('title'=>'创建时间','type'=>'date','hide'=>06),
    );
    protected $_validate = array(
        array('title','require','标题不能为空!'),
    );

    protected $modelInfo=array(
        "title"=>'系统公告','readOnly'=>false,"helpInfo"=>"",
        'searchHTML'=>"
                <span class='add-on'>标题:</span>
                <input size='10' placeholder='标题' type='text' class='z_input' name='%title%' id='title' value=''/>
                <span class='add-on'>内容:</span>
                <input size='20' placeholder='内容' type='text' class='z_input' name='%content%' id='content' value=''/>
                <button onclick='javascript:dataOpeSearch(\"dataListSearch\");' class='btn btn-info btn-sm' id='item_query_items'>查询</button>
                <button onclick='javascript:dataOpeSearch(\"\");' class='btn btn-info btn-sm' id='item_query_all' />全部数据</button>
            ",
    );
}

