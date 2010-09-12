<?php

require_once 'Path/Class/Dir.class.php';

// エンコード用グローバル変数
$GLOBALS['PATH_CLASS_ENCODE_IN']  = null; // ext 'UTF-8,SJIS,auto'
$GLOBALS['PATH_CLASS_ENCODE_OUT'] = null; // ext 'SJIS'

class Path_Class_Entity {
    
    function __clone() {}
    function copy() {
        return clone $this;
    }
    
    function __toString () {
        return $this->stringify();
    }
    
    function exists () {
        return file_exists($this->stringify());
    }
    
    function stat () { return stat($this->stringify()); }
    function lstat () { return lstat($this->stringify()); }
    function is_dir() { return 0; }
    
    // newで渡された値をUTF8で保持するためにエンコード
    function encode_in (&$data) {
        if ( $GLOBALS['PATH_CLASS_ENCODE_IN'] ) 
            $data = mb_convert_encoding($data,'UTF-8',$GLOBALS['PATH_CLASS_ENCODE_IN']);
    }
    
    // stringifyやbasenameで出力する際のエンコード
    function encode_out (&$data) {
        if ( $GLOBALS['PATH_CLASS_ENCODE_OUT'] ) 
            $data = mb_convert_encoding($data,$GLOBALS['PATH_CLASS_ENCODE_OUT'],'UTF-8');
    }
}
