<?php


declare(strict_types = 1);

use NavikeyCore\Library\Layer;

class Message {

    private $dbname;
    
    public function __construct($dbname) {
        $this->dbname = $dbname;
    }

    public function load(array $text_message, array $list_hrefs, string $dir) {
        $test=1;
        $properties = ['deviceId' => $text_message["deviceId"], 'title' => base64_decode($text_message['title']),
            'message' => base64_decode($text_message['message']), "vis" => false, "dir" => $dir,
            "hrefs" => $list_hrefs];
        $coordinates = $text_message['coordinates'];
        $layer = new Layer($this->dbname, "Message");
        $layer->insertOne($properties, $coordinates, "Point");
        unset($layer);
    }

}
