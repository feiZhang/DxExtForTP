<?php
class DxBasicAction extends Action {
    function now(){
        $this->ajaxReturn(1,time(),0);
    }
    function clearCache(){
        //很奇怪的现象，如果success放在之后，则会生成的临时文件Temp权限为000，导致无法写入。。
        $this->success("清除完成!",__ROOT__);
        DxFunction::deleteDir(RUNTIME_PATH,false);
    }

    /**
     * 下载文件
     */
    function download() {
        $f  = $_REQUEST["f"];
        if (!empty($_REQUEST["f"])) {
            $f  = C("UPLOAD_BASE_PATH").$f;
            //ob_clean(); //清空缓冲区，防止文件内容下载多了
            if (file_exists($f)) {
                $n = empty($_REQUEST["n"])?basename($_REQUEST["f"]):$_REQUEST["n"];
                $n = DxFunction::get_filename_bybrowser($n);
                require_once (DXINFO_PATH."/Vendor/Http.class.php");
                Http::download($f, $n, $content = '', $expire = 180);
            } else {
                $this->error("文件不存在");
            }
        }
    }
    function showImg(){
        $f  = $_REQUEST["f"];
        if (!empty($_REQUEST["f"])) {
            if($_REQUEST["p"]=="tmp")
                readfile(C("TEMP_FILE_PATH").$f);
            else
                readfile(C("UPLOAD_BASE_PATH").$f);
        }
    }
    /**
     * 上传并剪切头像
     * */
    public function upload_photo(){
        $this->display("DxBasic:upload_photo");
    }

    /**
     * 上传文件
     * */
    public function upload_file(){
        require_once (DXINFO_PATH."/Vendor/UploadHandler.class.php");
        $upload_handler = new UploadHandler(array(
            "validate"      => '/\.(gif|jpe?g|png)$/i',
            "upload_dir"    => C('TEMP_FILE_PATH')."/",
            "script_url"    => __ACTION__,
            "download_via_php" => true,
            'image_versions' => array(
                'thumbnail' => array(
                    'max_width'     => 350,
                    'max_height'    => 150
                )
            )
        ));
    }
    
    //裁剪图片
    public function cut_img(){
        if(empty($_REQUEST) || empty($_REQUEST["img"]) || empty($_REQUEST["img"]) || empty($_REQUEST["width"]) || empty($_REQUEST["height"]) || empty($_REQUEST["left"]) || empty($_REQUEST["top"])){
            $this->ajaxReturn(0,"非法数据请求!",0);
        }
        $targ_w = intval($_REQUEST["width"]);
        $targ_h = intval($_REQUEST["height"]);
        
        $file_path  = C("TEMP_FILE_PATH")."/".$_REQUEST["img"];
        $dst_r      = imagecreatetruecolor( $targ_w, $targ_h );
        $ext_file_name  = strtolower(substr(strrchr($file_path, '.'), 1));
        switch ($ext_file_name) {
            case 'jpg':
            case 'jpeg':
                $src_img = @imagecreatefromjpeg($file_path);
                $write_image = 'imagejpeg';
                break;
            case 'gif':
                @imagecolortransparent($dst_r, @imagecolorallocate($dst_r, 0, 0, 0));
                $src_img = @imagecreatefromgif($file_path);
                $write_image = 'imagegif';
                break;
            case 'png':
                @imagecolortransparent($dst_r, @imagecolorallocate($dst_r, 0, 0, 0));
                @imagealphablending($dst_r, false);
                @imagesavealpha($dst_r, true);
                $src_img = imagecreatefrompng($file_path);
                $write_image = 'imagepng';
                break;
            default:
                $src_img = null;
        }
        if(empty($src_img)){
            $this->ajaxReturn(0,"文件格式不符!",0);
        }
        if(!imagecopyresampled($dst_r,$src_img,0,0,intval($_POST['left']),intval($_POST['top']),$targ_w,$targ_h,$targ_w,$targ_h)){
            $this->ajaxReturn(0,"创建缩略图失败!",0);
        }
        
        $newFile    = date("YmdHis",time())."-".intval(mt_rand()*1000).".".$ext_file_name;
        if($write_image($dst_r,C("TEMP_FILE_PATH")."/".$newFile)){
            unlink($file_path);
            $rv = array("url"=>"/".$newFile,"file"=>$newFile);
            $this->ajaxReturn($rv,"文件上传成功!",1);
        }else
            $this->ajaxReturn(0,"创建缩略图失败!",0);
    }

    public function canton_fdn(){
        $m  = D("Canton");
//      $m->execute("UPDATE canton SET fdn=CONCAT(id,'.') WHERE parent_id=0");
//      for($i=1;$i<4;++$i){
//          $info = $m->where(array('layer'=>$i))->field('id,fdn')->select();
//          foreach($info as $one){
//              //修改子节点的fdn
//              $sql    = "UPDATE canton SET fdn=CONCAT('".$one["fdn"]."',id,'.') WHERE parent_id=".$one["id"];
//              $m->execute($sql);
//              //die($m->getLastSQL());
//          }
//      }
        $sql    = "SELECT c.`TABLE_NAME` FROM information_schema.`COLUMNS` c,information_schema.`TABLES` t WHERE c.TABLE_NAME=t.TABLE_NAME AND c.`TABLE_SCHEMA`='".C("DB_NAME")."' AND t.`TABLE_SCHEMA`='".C("DB_NAME")."' AND c.COLUMN_NAME='canton_fdn' AND t.TABLE_TYPE='BASE TABLE'";
        $tables = $m->query($sql);
        
        $m->execute("UPDATE organization t SET t.canton_fdn=(SELECT c.fdn FROM canton c WHERE (c.old_fdn=t.canton_fdn OR c.fdn=t.canton_fdn))");
        $m->execute("UPDATE older_info t SET resident_address_code=(SELECT fdn FROM canton c WHERE (c.old_fdn=t.resident_address_code OR c.fdn=t.resident_address_code))");
        if($tables){
            foreach($tables as $v){
                if($v["TABLE_NAME"]=="account" || $v["TABLE_NAME"]=="org_check_order" || $v["TABLE_NAME"]=="stat_org_info" || $v["TABLE_NAME"]=="subsidy" || $v["TABLE_NAME"]=="subsidy_info")
                    $sql    = sprintf("UPDATE %s t SET canton_fdn=(SELECT fdn FROM canton c WHERE (c.old_fdn=t.canton_fdn OR c.fdn=t.canton_fdn))",$v["TABLE_NAME"]);
                else if($v["TABLE_NAME"]!="organization")
                    $sql    = sprintf("UPDATE %s t SET canton_fdn=(SELECT canton_fdn FROM organization c WHERE c.id=t.org_id)",$v["TABLE_NAME"]);
                $m->execute($sql);
                printf("SELECT canton_fdn FROM ".$v["TABLE_NAME"].";<br \>");
                flush();
            }
        }
    }


    /**
     * ajax表单验证方法,对应model的callback 和 function验证
     * 返回值定义array('field_id', true, [msg])
     */
    public function remoteValidataField(){
        $ret = array($_REQUEST['fieldId'],false,'数据不可用!');
        if(array_key_exists("modelName",$_REQUEST)){
            $m = D($_REQUEST["modelName"]);
            if(empty($m)) die(json_encode($ret));
            $data = array($m->getPk()=>$_REQUEST["pkId"],$_REQUEST["fieldId"]=>$_REQUEST["fieldValue"]);
            if($m->validataField($data,$_REQUEST["fieldId"])){
                $ret = array($_REQUEST['fieldId'],true,'数据可用');
            }else{
                $ret = array($_REQUEST['fieldId'],false,$m->getError());
            }
        }
        die(json_encode($ret));
    }

    /**
     * 初始化地区数据名称
     */
    public function canton_textname(){
        $m  = D("Canton");
        $m->execute("UPDATE canton SET text_name=name WHERE layer=1");
        $m->execute("UPDATE canton SET text_name=name WHERE layer=2");
        for($i=2;$i<4;++$i){
            $info = $m->where(array('layer'=>$i))->field('id,text_name')->select();
            foreach($info as $one){
                //修改子节点的fdn
                $sql    = "UPDATE canton SET text_name=CONCAT('".$one["text_name"]."','|',name) WHERE parent_id=".$one["id"];
                $m->execute($sql);
                //die($m->getLastSQL());
            }
        }
    }

    /**
     * 显示数据选择对话框，比如：新增投诉，选择投诉人、被投诉人的对话框
     */
    public function dialogSelect(){
        if(empty($_REQUEST["model"])){
            $this->ajaxReturn(array("msg"=>"错误的请求！","status"=>0));
        }else{
            $this->model  = D($_REQUEST["model"]);
            $v  = $this->model->field($_REQUEST["fields"])->where($this->_search())->select();
            $this->ajaxReturn(array("dataList"=>$v,"status"=>1,"showFields"=>$showFields));
        }
    }

    /**
     * 将首页得区域js内容整合到一个文件中，否则首页太大，看代码不方便
     */
    public function globalJs(){
        //Canton的缓存数据,用于生成 selectselectselect 
        $cantonInfo = D("Canton")->getSelectSelectSelect();
        $this->assign("CantonData",str_replace("{","{ ",json_encode($cantonInfo)));
        $this->display("global");
    }

    /**
     * 备份数据库
     */
    public function backDb(){
        $backFile = C("DB_BACK_PATH").C("DB_NAME").date("d").".sql";
        $backSQL = sprintf("mysqldump --opt -u%s -p%s -h%s %s>%s",C("DB_USER"),C("DB_PWD"),C("DB_HOST"),C("DB_NAME"),$backFile);
        //die($backSQL);
        system($backSQL);
        die("beifen chenggong");
    }
}

