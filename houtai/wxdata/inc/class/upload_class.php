<?php 
require CLASS_PATH . "upyun.class.php";


class upload{
    public $save_dir = '';
    public $file = null;
    public $ext = array('jpg','gif','png');
    public $size = '';
    public $err = '';
    
    function __construct($save_dir,$ext=null){
        $this->size = 8 * 1024 * 1024;
        $this->save_dir = $save_dir;
        if($ext){
            $this->ext = $ext;
        }
    }
    
    function init_file($file){
        if(!$file){
            $this->err = '文件不能为空';
            return false;
        }
        
        if($file['error']){
            $this->err = '上传错误:' . $file['error'];
            return false;
        }
        
        $this->file = $file;
        $info = pathinfo($this->file['name']);
        $fext = $info['extension'];
        $this->file['ext'] = $fext;
        return true;
    }

    function checkdir(){
        if (!is_dir($this->save_dir)){
            $ret = mkdir($this->save_dir);
            if(!$ret){
                $this->err = '创建目录出错';
                return false;
            }
        }
        return true;
    }
    
    function checkext(){
        $ext = $this->file['ext'];
        if(!in_array($ext, $this->ext)){
            $this->err = '无效的上传类型';
            return false;
        }
        return true;
    }
    
    function checksize(){
        $size = $this->file['size'];
        if($size>$this->size){
            $this->err = '文件太大';
            return false;
        }
        return true;
    }
    
    
    function move($file){
        $ret = $this->init_file($file);
        if(!$ret){
            return false;
        }
        
        $ret = $this->checkdir();
        if(!$ret){
            return false;
        }
        
        $ret = $this->checkext();
        if(!$ret){
            return false;
        }
        
        $ret = $this->checksize();
        if(!$ret){
            return false;
        }
        
        $fname = date("his").rand(100,999);
        $fname .= '.'.$this->file['ext'];
        $path_and_fname = $this->save_dir.$fname;
        $result = move_uploaded_file($this->file['tmp_name'],$path_and_fname);
        if($result){
            return $path_and_fname;
        }else{
            $this->err = '上传失败';
            return false;
        }
    }
    
    function up_yun($ret){
        require ROOT . "wxdata/upyun.config.php";
        $upyun = new UpYun($img_bucket, $img_username, $img_passwd);
        $yun_file = substr($ret, strpos($ret,'upload'));
        
        try {
            $opts = array( UpYun::CONTENT_MD5 => md5(file_get_contents($ret)) );
            $fh = fopen($ret, "rb");
            $rsp = $upyun->writeFile("/" . $yun_file, $fh, True, $opts); //上传图片，自动创建目录
            fclose($fh);
            $yp_url  = 'http://'.$img_bucket.'.b0.upaiyun.com/'.$yun_file;
        } catch(Exception $e) {
            $this->err = '上传失败:上传至云时失败';
            return false;
        }
        
        return  $yp_url;
    }
    
}