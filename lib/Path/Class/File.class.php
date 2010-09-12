<?php

require_once 'Path/Class/Entity.class.php';
require_once 'Path/Class/Dir.class.php';

class Path_Class_File extends Path_Class_Entity {
    
    public $dir;
    public $file;
    
    function __construct ($paths=null) {
        if ( !is_array($paths) ) {
            $paths = is_null($paths) ? array() : array($paths);
        }
        foreach ($paths as &$i) $this->encode_in($i);
        
        $path = join(DIRECTORY_SEPARATOR,$paths);
        
        $pinfo = array(
            'dirname'  => '',
            'basename' => '',
        );
        if ( preg_match('/^(.*[\/\\\])([^\/\\\]+)?$/',$path,$matches) ) {
            $pinfo['dirname'] = $matches[1];
            if ( isset($matches[2]) ) $pinfo['basename'] = $matches[2];
        }
        else {
            $pinfo['basename'] = $path;
        }
        
        $this->dir = null;
        $this->file = $pinfo['basename'];
        if ( strlen($pinfo['dirname']) ) {
            $class = $this->dir_class();
            $this->dir = new $class($pinfo['dirname']);
        }
    }
    
    function stringify () {
        if ( is_null($this->dir) ) return $this->basename();
        return join(DIRECTORY_SEPARATOR,array($this->dir->stringify(),$this->basename()));
    }
    
    function dir () {
        $class = $this->dir_class();
        if ( is_null($this->dir) ) return new $class('.');
        return $this->dir;
    }
    
    // dirのエイリアス
    function parent () {
        $class = $this->dir_class();
        if ( is_null($this->dir) ) return new $class('.');
        return $this->dir;
    }
    
    function volume () {
        if ( is_null($this->dir) ) return '';
        return $this->dir->volume();
    }
    
    function open ($mode='r',$use_include_path=false) {
        return new SplFileObject($this->stringify(),$mode,$use_include_path);
    }
    
    function openr ($use_include_path=false) {
        return $this->open('r',$use_include_path);
    }
    
    function openw ($use_include_path=false) {
        return $this->open('w',$use_include_path);
    }
    
    function slurp () {
        return file_get_contents($this->stringify());
    }
    
    function slurp_array () {
        return file($this->stringify());
    }
    
    // file_get_contentsの引数互換
    function get_contents (/* 可変変数 */) {
        $param = func_get_args();
        array_unshift($param,$this->stringify());
        return call_user_func_array('file_get_contents',$param);
    }
    
    // file_put_contentsの引数互換
    function put_contents (/* 可変変数 */) {
        $param = func_get_args();
        array_unshift($param,$this->stringify());
        return call_user_func_array('file_put_contents',$param);
    }
    
    function remove () {
        return unlink($this->stringify());
    }
    
    function basename () {
        $file = $this->file;
        $this->encode_out($file);
        return $file;
    }
    
    function dir_class () {
        return 'Path_Class_Dir';
    }
}
