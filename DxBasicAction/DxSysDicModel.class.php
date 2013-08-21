<?php
class DxSysDicModel extends DxExtCommonModel {
	public $listFields = array (
		array("name"=>"id","pk"=>true,'title'=>'操作',"hide"=>22,
					'renderer'	=> "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
										var v	= '<a href=\"javascript:dataOpeEdit(' + value + ');\">修改</a>';
										v	+= ' <a href=\"javascript:dataOpeDelete(' + value + ');\">删除</a>';
										return v;
									}",
			),
		"type"	=> array ('title'=>'类型','readOnly'=>3,'hide'=>1,"valChange"=>array("model"=>"SysDic","type"=>"basic_data_type"),"type"=>"enum"),
		array (
			'name' 		=> 'name','title' 	=> '类别'
		),
		array (
			'name' 		=> 'memo','title' 	=> '备注',"width"=>"300",
		),
	);
	
    protected $modelInfo=array(
    	"title"=>'数据字典','readOnly'=>false,"enablePrint"=>true,
  		"dictTable"=>"type,code,name","helpInfo"=>"请勿轻易删除此界面数据，删除将影响养老院软件字典数据!",
        'leftArea' => "{:W('Menu',array('type'=>\$type,'parent_id'=>\$menu_id))}",
    	//'searchHTML'=>"类型:<input name='type' value='Sex' type='radio' />性别<input name='type' value='SubsidyRank' type='radio' />补贴标准 <input type='button' class='d-button d-state-highlight' value='查询' id='item_query_items' /> <input type='button' class='d-button d-state-highlight' value='全部数据' id='item_query_all' />",
    );
    public function _after_insert($data, $options){
    	$this->where(array('id'=>$data['id']))->save(array('code'=>$data['id']));
    }
}

