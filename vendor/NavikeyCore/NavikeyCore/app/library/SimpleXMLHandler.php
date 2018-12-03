<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

class SimpleXMLHandler {

    public function __construct() {
        
    }

    /**
     * Загружает xml объект из файла
     * @param string $path Путь к файлу 
     * @return SimpleXMLElement Объект xml
     */
    public function getXml(string $path): \SimpleXMLElement {
        if (!file_exists($path)) {
            throw new \Exception('File not found or permission denied ' . $path);
        }
        $xmlstr = file_get_contents($path);
        libxml_use_internal_errors(true);
        $xmlinfo = simplexml_load_string($xmlstr);
        if ($xmlinfo === false) {
            $errors = libxml_get_errors();
            $xml = explode("\n", $xmlstr);
            $return = "";
            foreach ($errors as $error) {
                $return = $return . $this->displayErrorXML($error, $xml);
            }
            libxml_clear_errors();
            throw new \Exception($return);
        }
        return $xmlinfo;
    }

    private function displayErrorXML(\libXMLError $error, array $xml): string {
        $return = $xml[$error->line - 1] . "<br>";
        $return .= str_repeat('-', $error->column) . "<br>";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
                "<br>  Line: $error->line" .
                "<br>  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return "$return<br>" . str_repeat('-', $error->column) . "<br><br>";
    }

}
