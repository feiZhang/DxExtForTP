<?php
class DxCantonModel extends DxExtCommonModel {
    //put your code here
	protected $modelInfo=array(
			"title"=>'行政区域','readOnly'=>true,
			"dictTable"=>array("fdn","text_name")
	);
	
	public function getSelectSelectSelect($fdn=""){
		if(empty($fdn)){
			$list		= S("Cache_SELECT_SELECT_SELECT_Canton");
			if(empty($list)){
				$list = $this->field("canton_id,name title,parent_id,fdn val")->select();
				foreach($list as $kk=>$ll){
					$list[$kk]["canton_id"]	= intval($ll["canton_id"]);
					$list[$kk]["parent_id"]	= intval($ll["parent_id"]);
				}
				S("Cache_SELECT_SELECT_SELECT_Canton",$list);
			}
		}else
			$list = $this->where(array("fdn"=>array( 'like', $fdn."%")))->field("id,name title,parent_id,fdn val")->select();
		
		$data = array();		
		if($list) $data	= $list;
		
		return $data;
	} 	
 	/**
 	 * 得到所属区域的第一级子区域
 	 * @param int $canton_id
 	 * @return	array $canton_list
 	 */
  	public function getChildCanton($canton_id=""){
 		if(empty($canton_id)){
 			$canton_id = session('canton_id');
 		}
 		$canton_list = $this->where(array('parent_id'=>$canton_id))->field('id,fdn,name')->select();
 		return $canton_list ;
 	}
 	/**
 	 * 通过canton_id得到区域名称
 	 */
 	public function getCantonNameByID($canton_id){
 		return $this->where(array('id'=>$canton_id))->getField('text_name');
 	}
 	/**
 	 * $canton_uniqueno
 	 */
 	public function getCantonUniqueno($canton_fdn){
 		return $this->where(array('fdn'=>$canton_fdn))->getField('canton_uniqueno');
 	}
}

?>
