<?php

require_once 'Path/Class/Entity.class.php';
require_once 'Path/Class/File.class.php';

class Path_Class_Dir extends Path_Class_Entity {
    
    public $volume;
    public $dirs;
    private $recurse_queue;
    
    function __construct ($paths=null) {
        if ( !is_array($paths) ) {
            $paths = is_null($paths) ? array() : array($paths);
        }
        
        foreach ($paths as &$i) $this->encode_in($i);
        
        $root_flag = false;
        if ( !$paths ) {
            $first = '.';
        }
        elseif ( $paths[0] === '' ) {
            $root_flag = true;
            array_shift($paths);
            $first = DIRECTORY_SEPARATOR;
        }
        else {
            $first = array_shift($paths);
            if ( preg_match('/^([A-Za-z]:)/',$first,$matches) ) {
                $root_flag = true;
                $first = ltrim($first,$matches[1]);
                $this->volume = $matches[1];
            }
            elseif ( preg_match('/^[\/\\\]/',$first) ) {
                $root_flag = true;
            }
        }
        
        array_unshift($paths,$first);
        $path = join(DIRECTORY_SEPARATOR,$paths);
        $dirs = preg_split('/[\/\\\]/',$path,0,PREG_SPLIT_NO_EMPTY);
        if ( $root_flag ) array_unshift($dirs,'');
        
        $this->dirs = $this->_paths($dirs,1);
    }
    
    function stringify () {
        $path = $this->volume.join(DIRECTORY_SEPARATOR,$this->dirs);
        $this->encode_out($path);
        return $path;
    }
    
    function file ($paths=null) {
        if ( !is_array($paths) ) {
            $paths = is_null($paths) ? array() : array($paths);
        }
        array_unshift($paths,$this->stringify());
        $class = $this->file_class();
        return new $class($paths);
    }
    
    function dir_list () {
        return $this->dirs;
    }
    
    function subdir ($paths=null) {
        if ( !is_array($paths) ) {
            $paths = is_null($paths) ? array() : array($paths);
        }
        array_unshift($paths,$this->stringify());
        $class = get_class($this);
        return new $class($paths);
    }
    
    function parent () {
        if ( $this->is_absolute() ) {
            $obj = clone $this;
            if ( count($obj->dirs) > 1 ) array_pop($obj->dirs);
            return $obj;
        }
        else {
            $class = get_class($this);
            return new $class(array($this->stringify(),'..'));
        }
    }
    
    function open () {
        return new DirectoryIterator($this->stringify());
    }
    
    function recurse ($callback,$option=array()) {
        $option = array_merge(array(
            'depthfirst' => 0,
            'preorder'   => 1,
        ),$option);
        
        $type = 'normal';
        if ( $option['depthfirst'] && $option['preorder'] ) {
            $type = 'depthorder';
        }
        elseif ( $option['preorder'] ) {
            $type = 'preorder';
        }
        
        $this->recurse_queue = array($this);
        while ($this->recurse_queue) {
            $entry = array_shift($this->recurse_queue);
            $this->_recurse_entry($entry,$callback,$type);
        }
    }
    
    private function _recurse_entry ($entry,$callback,$type) {
        if ( $entry->is_dir() ) {
            $method = '_recurse_dir_'.$type;
            $this->$method($entry,$callback);
        }
        else {
            $this->_recurse_callback($callback,$entry);
        }
    }
    
    private function _recurse_dir_normal ($dir,$callback) {
        foreach ($dir->children() as $entry) {
            $this->_recurse_entry($entry,$callback,'normal');
        }
        $this->_recurse_callback($callback,$dir);
    }
    
    private function _recurse_dir_preorder ($dir,$callback) {
        $this->_recurse_callback($callback,$dir);
        foreach ($dir->children() as $entry) {
            $this->recurse_queue []= $entry;
        }
    }
    
    private function _recurse_dir_depthorder ($dir,$callback) {
        $this->_recurse_callback($callback,$dir);
        foreach (array_reverse($dir->children()) as $entry) {
            array_unshift($this->recurse_queue,$entry);
        }
    }
    
    private function _recurse_callback ($callback,$entry) {
        if ( is_array($callback) ) {
            $method = $callback[1];
            $callback[0]->$method($entry);
        }
        else {
            $callback($entry);
        }
    }
    
    function children ($option=array()) {
        $iter = $this->open();
        
        $dir_path = $this->stringify();
        $out = array();
        $dir_class = get_class($this);
        $file_class = $this->file_class();
        
        if ( isset($option['regex']) ) {
            foreach ($iter as $fileinfo) {
                if (empty($option['all']) && $fileinfo->isDot()) continue;
                if (!preg_match($option['regex'],$fileinfo->getFilename())) continue;
                if ( $fileinfo->isDir() ) {
                    $out []= new $dir_class($dir_path.DIRECTORY_SEPARATOR.$fileinfo->getFilename());
                }
                else {
                    $out []= new $file_class($dir_path.DIRECTORY_SEPARATOR.$fileinfo->getFilename());
                }
            }
        }
        else {
            foreach ($iter as $fileinfo) {
                if (empty($option['all']) && $fileinfo->isDot()) continue;
                if ( $fileinfo->isDir() ) {
                    $out []= new $dir_class($dir_path.DIRECTORY_SEPARATOR.$fileinfo->getFilename());
                }
                else {
                    $out []= new $file_class($dir_path.DIRECTORY_SEPARATOR.$fileinfo->getFilename());
                }
            }
        }
        
        return $out;
    }
    
    function mkpath ($mode=0777) {
        return mkdir($this->stringify(),$mode,true);
    }
    
    function remove () {
        return rmdir( $this->stringify() );
    }
    
    function is_absolute () {
        return ( $this->dirs && $this->dirs[0] === '' );
    }
    
    private function _paths($paths,$rettype=0) {
        if ( !is_array($paths) ) $paths = preg_split('/[\/\\\]/',$paths);
        
        $root_flag = ( $paths && $paths[0] === '' );
        if ( $root_flag ) array_shift($paths);
        
        $out = array();
        $n = 0;
        $r = 0;
        foreach($paths as $val) {
            if ( $val === '' ) continue;
            $out_count = count($out);
            if ( $val === '.' ) {
                if ( !$root_flag && $out_count == 0 ) $out[$n] = $val;
            }
            elseif ( $val === '..' ) {
                if ( $out_count != $r ) {
                    array_pop($out);
                    $n--;
                }
                else {
                    if ( !$root_flag || $out_count !== 0 ) {
                        $out[$n] = $val;
                        $r++;
                        $n++;
                    }
                }
            }
            else {
                $out[$n] = $val;
                $n++;
            }
        }
        
        if ( !$out ) array_unshift($out,'');
        if ( $root_flag ) array_unshift($out,'');
        return $rettype ? $out : implode(DIRECTORY_SEPARATOR, $out);
    }
    
    function basename () {
        $dir = $this->dirs ? $this->dirs[count($this->dirs)-1] : '';
        $this->encode_out($dir);
        return $dir;
    }
    
    function volume() { return $this->volume; }
    
    function is_dir() { return 1; }
    
    function file_class () {
        return 'Path_Class_File';
    }
}
