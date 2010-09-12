<?php

require_once 'Path/Class/Dir.class.php';
require_once 'Path/Class/File.class.php';

function cdir ($paths=null) {
    return new Path_Class_Dir($paths);
}

function cfile ($paths=null) {
    return new Path_Class_File($paths);
}
