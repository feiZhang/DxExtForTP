<?php
class DxNewsModel extends DxExtCommonModel {
    protected $listFields = array (
        "nid" => array("pk"=>true,'title'=>'操作',"hide"=>22,
                'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v = '';
                                    if(record.status==0){
                                        v   += '<a href=\"javascript:dataOpeEdit({ \'id\':' + value + '});\">修改</a>';
                                        v   += ' <a href=\"javascript:dataOpeDelete({ \'id\':' + value + '});\">删除</a>';
                                    }
                                    return v;
                                }","width"=>"80"
        ),
        "type" => array("title"=>"类型",
            "type"=>"enum",
            "valChange"=>array("1"=>"系统公告","2"=>"行业新闻")),
        "content" => array("title"=>"内容","width"=>8000),
        "creater_user_id"   => array("title" => "创建人","hide"=>06,"width" => 70,"valChange"=>array("model"=>"Account")),
        "create_time"       => array ('title'=>'创建时间','hide'=>06,'type'=>'date',"valFormat"=>"yyyy-MM-dd HH:mm:ss"),
        "update_time"       => array ('title'=>'更新时间','hide'=>06,'type'=>'date',"valFormat"=>"yyyy-MM-dd HH:mm:ss"),
    );

    protected $_validate = array(
        array('type','require','类型不能为空!',self::MUST_VALIDATE),
        array('content','require','内容不能为空!',self::MUST_VALIDATE),
    );

    protected $modelInfo=array(
        "title"=>'公告新闻','readOnly'=>false,
        "enableImport"=>false,"enableExport"=>false,"enableDeleteSelected"=>false,
        "helpInfo"=>"",
        'searchHTML'=>"
          类型:
          <?php
          \$zygqField = \$listFields['type'];
          \$zygqField['searchName'] = 'type';
          echo DxFieldInput::createInputHtml(\$zygqField,1);
          ?>
          创建时间:
          从<input id='egt_create_time' name='egt_create_time' value='' size='10' placeholder='' type='text' class='z_input Wdate' onfocus='WdatePicker({\"dateFmt\":\"yyyy-MM-dd\"})' />
          到<input id='elt_create_time' name='elt_create_time' value='' size='10' placeholder='' type='text' class='z_input Wdate' onfocus='WdatePicker({\"dateFmt\":\"yyyy-MM-dd\"})' />
          <button onclick='javascript:dataOpeSearch(\"dataListSearch\");' class='btn btn-info btn-sm' id='item_query_items'>查询</button>
          <button onclick='javascript:dataOpeSearch(\"\");' class='btn btn-info btn-sm' id='item_query_all' />全部数据</button>
          <input onclick='javascript:dataOpeExport(\"dataListSearch\");' type='button' class='btn btn-success btn-sm' value='导出' id='item_export_all' />
        ",
    );
}

