<?php
class ShareFileModel extends DxExtCommonModel {
    protected $listFields = array (
        "sf_id" => array("pk"=>true,'title'=>'操作',"hide"=>22,
                'renderer'  => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v   = '<a href=\"javascript:dataOpeEdit({ \'id\':' + value + '});\">修改</a>';
                                    v   += ' <a href=\"javascript:dataOpeDelete({ \'id\':' + value + '});\">删除</a>';
                                    return v;
                                }","width"=>"80"
                //v += ' <a href=\"javascript:downLoadAllFile(d);\">全部下载</a>';
        ),
        "title" => array ('title'=>'主题',"width"=>"130",'frozen'=> true),
        "file_name" => array(
            "title"=>"文件名","width"=>2000,"type"=>"uploadFile",
            "upload"=>array("filetype"=>".gif、.jpeg、.jpg、.png、.pdf、.doc、.docx、.xls、.mp4、.mov",
            "maxNum"=>0,"buttonValue"=>"文件上传","maxSize"=>50000000)
        ),
        "notes" => array ('title'=>'描述',"width"=>"2000"),
        "creater_user_name" => array ('title'=>'创建者姓名','hide'=>6,"width"=>"80"),
        "create_time"       => array ('title'=>'创建时间','hide'=>6),
        "create_public"     => array('title'=>'是否公开','hide'=>7,'default'=>'不公开','valChange'=>array(1=>'公开',''=>'不公开',0=>'不公开'))
    );

    protected $_validate = array(
        array('title','require','主题不能为空!',self::MUST_VALIDATE),
    );
    protected $modelInfo=array(
        "title"=>'共享文件','readOnly'=>false,'data_change'=>array("file_name"=>"uploadFilesToGrid"),"order"=>"sf_id DESC",
        "helpInfo"=>'<div class="alert alert-warning">一条记录多个文件时：将鼠标停留在文件内容框中，会显示其余的文件，点击后可下载。弹出框挡住下面文件框时，点击非文件框的其他区域，将隐藏弹出框。</div>',
        'searchHTML'=>"
          <span class='add-on'>主题:</span>
          <input id='title' size='10' class='dataOpeSearch likeLeft likeRight' value='' type='text' />
          <button onclick='javascript:dataOpeSearch(true);' class='btn' id='item_query_items'>查询</button>
          <button onclick='javascript:dataOpeSearch(false);' class='btn' id='item_query_all' />全部数据</button>
        ",
    );
}
