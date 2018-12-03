<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

/*
 * Класс предназанченный для создания sap - фаилов
 */

class Sap{

    public function __construct($name)
    {
        $this->config = parse_ini_file($name);
    }

    public function createSap($tplnr){
        $text="System";
        /*$text.$this->config->Name."\n";
        $text.$this->config->Description."\n";
        $text.$this->config->Client."\n";
        $text="[Function]"."\n";
        $text.$this->config->Title."\n";
        $text.$tplnr;
       // $dom = new \DOMElement('1.0', 'UTF-8');
        //$node = $dom->createElementNS('http://www.opengis.net/kml/2.2', 'txt');*/
        //$file = fopen ("sap.txt","w");
        //fwrite($file,$text);
        //fclose($file);
        return $text;
    }

}