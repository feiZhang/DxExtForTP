<?php
/*
 * 默认字段名，fdn和full_name
 * 默认ID是5位的数字。
 */
class DxFdnModel extends DxExtCommonModel {
    protected $fdnFieldFdn     = "fdn";
    protected $fdnFieldName    = "name";
    protected $fdnFieldFullName= "full_name";
    protected $fdnFieldParentId= "parent_id";
    protected $fdnFenGeFu      = "";

    public function _before_update(&$data,$option){
        parent::_before_update($data,$option);
    }
    public function _after_insert($data,$option){
        $newData = $this->update_fdn_name($data,$option);
        $fieldPkId = $this->getPk();
        $this->where(array($fieldPkId=>$data[$fieldPkId]))->save($newData);
        parent::_after_insert($data,$option);
    }
    public function update_fdn_name($data,$option){
        $newData = array();
        $fieldPkId = $this->getPk();
        if(!empty($data[$this->fdnFieldParentId])){
            $map[$fieldPkId]    = $data[$this->fdnFieldParentId];
            $p_info = $this->where($map)->field($this->fdnFieldFdn.",".$this->fdnFieldFullName)->find();
            if($p_info){
                $newData[$this->fdnFieldFdn] = $p_info[$this->fdnFieldFdn];
                $newData[$this->fdnFieldFullName] = sprintf('%s%s%s',$p_info[$fdnFieldFullName],$this->fdnFenGeFu,$data[$fdnFieldName]);
            }
        }else {
            $newData[$this->fdnFieldFdn] = "";
            $newData[$this->fdnFieldFullName] = $data[$this->fdnFieldName];
        }

        $newData[$this->fdnFieldFdn] = sprintf('%s%5d.',$newData[$this->fdnFieldFdn],$data[$fieldPkId]);
        return $newData;
    }
}

