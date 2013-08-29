<?php 
class DxAccountModel extends DxExtCommonModel{
    protected  $listFields = array (
            "account_id"     => array('title'=>'操作','width'=>120, 'pk'=>true,'hide'=>22,'renderer'    => "var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){
                                    var v   = '<a href=\"javascript:dataOpeEdit( { \'id\':' + value + '});\">修改</a>';
                                    v   += ' <a href=\"javascript:dataOpeDelete( { \'id\':' + value + '});\">删除</a>';
                                    v   += ' <a href=\"javascript:resetPasswd( { \'id\':' + value + '});\">重置密码</a>';
                                    return v;
                                }"),
            "canton_id"      => array('title'=>'所在区域','hide'=>01,'display_none'=>0777),
            "canton_fdn"     => array('title'=>'所在区域','width'=>140,'type'=>"canton","canton"=>array("id_name"=>"canton_id")),
            "username"       => array('title'=>'系统用户名',),
            "role_id"        => array('title'=>'角色','type'=>'enum','valChange'=>array('model'=>'Role')),
            "pwd"            => array('title'=>'登录密码','hide'=>01,'type'=>'password'),
            "name"           => array('title'=>'实际姓名','width'=>60),
            "tel"            => array('title'=>'联系电话','width'=>80),
            "address"        => array('title'=>'家庭地址','width'=>140),
            "shorcut_ids"    => array('title'=>'快捷操作','hide'=>07),
            "desk_ids"       => array('title'=>'桌面操作','hide'=>07),
            "menu_ids"       => array('title'=>'菜单','hide'=>077777),
            "status"         => array('title'=>"状态","width"=>30,'type'=>'enum','valChange'=>array('1'=>'正常','0'=>'未验证','-1'=>'已删除',2=>'禁用'),'COMMENT'=> '1.正常 0:未验证 -1:已删除 2:禁用'),
            "create_user_id" => array('title'=>'创建人',"hide"=>06,"type"=>"select","valChange"=>array("model"=>"Account")),
            "create_time"    => array('title'=>'创建时间','type'=>'datetime','hide'=>06),
    );
    protected $_validate = array(
            array('username','','帐号名称已经存在!',self::MUST_VALIDATE,'unique'),
            array("name", "2,15", "姓名应大于2个字符且小于15个字符!", self::MUST_VALIDATE, 'length'),
            array('pwd','require','密码不能为空!',self::MUST_VALIDATE,'',self::MODEL_INSERT),
    );

    protected $modelInfo=array(
        'leftArea' => "{:W('Menu',array('type'=>\$type,'parent_id'=>\$menu_id))}",
        "dictTable"=>"username",
        "title"=>'系统账号','readOnly'=>false,"helpInfo"=>"请谨慎删除账号，通畅禁用账号即可!",
        'searchHTML'=>"
                <span class='add-on'>登录名:</span>
                <input id='username' class='dataOpeSearch likeLeft likeRight span2' value='' type='text' />
                <span class='add-on'>真实姓名:</span>
                <input id='name' class='dataOpeSearch likeLeft likeRight span2' value='' type='text' />
                <button onclick='javascript:dataOpeSearch(true);' class='btn' id='item_query_items'>查询</button>
                <button onclick='javascript:dataOpeSearch(false);' class='btn' id='item_query_all' />全部数据</button>
            ",
    );

    protected  $_auto = array(
        array('pwd','DxFunction::authcode',Model:: MODEL_BOTH,'callback',array("ENCODE")),
    );

    protected function _before_update(&$data, $options) {
        fb::log($data);
        parent::_before_update($data, $options);
        return true;
    }
}
?>
