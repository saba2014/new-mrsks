<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

class FileLoader {

    public function __construct() {
        
    }

    public function saveFiles(string $path, $files, $names):array {
        $arr = [];
        $name = time();
        if (is_array($files)){
            $i=0;
            foreach ($files as $file){
                if ($names[$i])
                    $name = $names[$i];
                else $name = $file->getTempName();
                $arr[]=$this->saveFile($path,$file,$name);
            }
        }
        else{
            if ($names)
                $name = $names;
            $name = $files->getTempName();
            $arr[]=$this->saveFile($path,$files,$name);
        }
        return $arr;
    }

    public function saveFile(string $path, $file,string $name = ""){
        ($file->moveTo($path."/".$name)) ? $isUploaded = true : $isUploaded = false;
        if ($isUploaded){
            $res = $path."/".$name;
            return $res;
        }
        return false;
    }

    public function addFileZip($file) {
        
    }

}
