<?php 
class DxExtAccountModel extends DxExtCommonModel{
    public  $listFields = array (
            "account_id"    => array('type'=>'int','size'=>10,'title'=>'操作', 'pk'=>true,'hide'=>22,'renderer'	=> "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
									var v	= '<a href=\"javascript:dataOpeEdit(' + value + ');\">修改</a>';
									v	+= ' <a href=\"javascript:dataOpeDelete(' + value + ');\">删除</a>';
									v	+= ' <a href=\"javascript:resetPasswd(' + value + ');\">重置密码</a>';
									return v;
								}"),
            "username"      => array( 'type'=>'varchar','size'=>45 ,'title'=>'系统登录账号名'),
            "pwd"           => array( 'type'=>'varchar','size'=>200,'title'=>'登录密码','type'=>'password','hide'=>'4'),
            "real_name"     => array('type'=>'varchar','size'=>45,'title'=>'用户本人实际姓名'),
            "tel"           => array( 'type'=>'varchar','size'=>12,'title'=>'联系电话'),
            "email"         => array('type'=>'varchar','size'=>60,'title'=>'EMail'),
            "address"       => array('type'=>'varchar','size'=>45,'title'=>'家庭地址'),
            "role_id"       => array('type'=>' int','size'=>10,'title'=>'角色id','type'=>'enum','valChange'=>array('model'=>'Role')),
            "dept_id"       => array('type'=>' int','size'=>10,'title'=>'部门ID'),
            "canton_id"     => array('type'=>' int','size'=>10,'title'=>'所在区域'),
            "canton_fdn"    => array('type'=>' varchar','size'=>45,'title'=>'区域串'),
            "create_id"     => array( 'type'=>'int','size'=>10,'title'=>'创建人'),
            "create_time"   => array( 'type'=>'timestamp','title'=>'创建时间','hide'=>22),
            "status"        => array('type'=>'tinyint','size'=>1,'title'=>"状态",'type'=>'select','valChange'=>array('1'=>'正常','0'=>'未验证','-1'=>'已删除',2=>'禁用'),'COMMENT'=> '1.正常 0:未验证 -1:已删除 2:禁用'),
    );
    protected $_validate = array(
            array('username','','帐号名称已经存在!',self::MUST_VALIDATE,'unique'),
            array("real_name", "2,15", "姓名应大于2个字符且小于15个字符!", self::MUST_VALIDATE, 'length'),
            array('pwd','require','密码不能为空!',self::MUST_VALIDATE,'',self::MODEL_INSERT),
    );
    
    protected $modelInfo=array(
            "title"=>'系统账号','readOnly'=>false,"helpInfo"=>"1.删除账号并不影响机构信息",
            'searchHTML'=>"
            登录名:<input id='username' size='10' class='dataOpeSearch' value='' />
            真实姓名:<input id='name' size='10' class='dataOpeSearch' value='' />
            <input onclick='javascript:dataOpeSearch(true);' type='button' class='d-button d-state-highlight' value='查询' id='item_query_items' />
            <input onclick='javascript:dataOpeSearch(false);' type='button' class='d-button d-state-highlight' value='全部数据' id='item_query_all' />",
    );
    
    protected function _before_insert(&$data, $options) {
        parent::_before_insert($data, $options);
        $data['pwd'] = authcode($data['pwd'],"ENCODE");
        return true;
    }
    
    protected function _before_update(&$data, $options) {
        parent::_before_update($data, $options);
        $data['pwd'] = authcode($data['pwd'],"ENCODE");
        return true;
    }
    
}
?>