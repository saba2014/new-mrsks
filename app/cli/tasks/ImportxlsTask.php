<?php
declare(strict_types=1);

use Phalcon\Cli\Task;

use NavikeyCore\Library\LayerRes;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use MongoDB\Driver\Manager;
use MongoDB\BSON\ObjectID;

class ImportxlsTask extends Task
{

    public function createNewResAction()
    {
        $Reader = new SpreadsheetReader('tmp/CenterRes.xls');
        $i = 0;
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $resCenters = [];
        $poCenters = [];
        $notFound = [];
        $Reader->ChangeSheet(0);
        foreach ($Reader as $Row) {
            $item = [];
            if ($i >= 4) { // < 229+4
                $item = $this->getCenterData($filials, $pos, $reses, $Row, $i);
                if (isset($item['type'])) {
                    if ($item['type'] === 'res') {
                        unset($item['type']);
                        $resCenters[] = $item;
                    } else {
                        unset($item['type']);
                        $poCenters[] = $item;
                    }
                } else {
                    $notFound[] = $Row;
                }
            }
            $i++;
        }
        $allData = array_merge($resCenters, $poCenters);
        $string = json_encode($allData);
        echo json_encode($notFound);
    }

    private function getFilials()
    {
        $manager = new Manager();
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $collection = new Collection($manager, $this->config->database->dbname, 'Filiations');
        $result = $collection->find([], $options)->toArray();
        foreach ($result as &$item){
            $item=$item['properties'];
        }
        return $result;
    }

    private function getRes()
    {
        $manager = new Manager();
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $collection = new Collection($manager, $this->config->database->dbname, 'Res');
        $result = $collection->find([], $options)->toArray();
        foreach ($result as &$item){
            $item=$item['properties'];
        }
        return $result;
    }

    private function getPo()
    {
        $manager = new Manager();
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $collection = new Collection($manager, $this->config->database->dbname, 'Po');
        $result = $collection->find([], $options)->toArray();
        foreach ($result as &$item){
            $item=$item['properties'];
        }
        return $result;
    }

    private function getCenterData($filials, $pos, $reses, $Row, $counter)
    {
        if ($counter === 6) {
            echo '1';
        }
        $filialName = trim($Row[0]);
        if ($filialName === 'ГАЭС') {
            $filialName = 'Горно-Алтайские электрические сети';
        }
        $poName = trim($Row[1]);
        $resName = trim($Row[2]);
        if ($resName === 'Биский РЭС') {
            $resName = 'Бийский РЭС';
        }
        $filialId = 0;
        $poId = 0;
        $resId = 0;
        foreach ($filials as $filial) {
            if ($filial['properties']['name'] === $filialName) {
                $filialId = $filial['properties']['id'];
            }
        }
        if ($filialId === 0) {
            return [];
        }
        if ($resName === '') {
            foreach ($pos as $po) {
                if (mb_strrpos($po['properties']['name'], $poName) !== false) {
                    $poId = $po['properties']['composite_id'];
                }
            }
            if ($poId === 0) {
                return [];
            }
            $response['type'] = 'po';
            $response['properties'] = ['filiationId' => $filialId, 'poId' => $poId, 'adress' => $Row[3], 'type' => 'po'];
            $response['geometry'] = ["type" => "Point", 'coordinates' => [(float)$Row[5], (float)$Row[4]]];
            return $response;
        } else {
            $pobranch = 0;
            foreach ($reses as $res) {
                if ($res['properties']['Label'] === $resName) {
                    $resId = $res['properties']['RES_id'];
                    $pobranch = $res['properties']['branch'];
                }
            }
            if ($pobranch == 0) {
                return [];
            }
            foreach ($pos as $po) {
                if ($po['properties']['composite_id'] === $pobranch) {
                    $response['type'] = 'res';
                    $response['properties'] = ['filiationId' => $filialId,
                        'poId' => $pobranch, 'resId' => $resId, 'adress' => $Row[3], 'type' => 'res'];
                    $response['geometry'] = ["type" => "Point", 'coordinates' => [(float)$Row[5], (float)$Row[4]]];
                    return $response;
                }
            }
        }
        return [];
    }


    public function addRiseAction()
    {
        $resIds = $this->getRes();
        $poIds = $this->getPo();
        $filials = $this->getFilials();
        $Reader = new SpreadsheetReader('tmp/Rise.xls');
        $i = 0;
        $poData = [];
        $resData = [];
        $notFound = [];
        $Reader->ChangeSheet(0);
        foreach ($Reader as $Row) {
            if ($i > 9) {
                $item = $this->findResOrPo($resIds, $poIds, $filials, $Row, $i);
                if ($item['type'] === 'po') {
                    unset($item['type']);
                    $item['properties']['type'] = 'po';
                    $poData[] = $item;
                } else if ($item['type'] === 'res') {
                    unset($item['type']);
                    $item['properties']['type'] = 'res';
                    $resData[] = $item;
                } else {
                    $notFound[] = $Row[3];
                }
            }
            $i++;
        }
        $allData = array_merge($poData, $resData);
        $dataString = $this->safe_json_encode($allData);
        echo '1';
    }


    private function findResOrPo($resIds, $poIds, $filials, $Row, $i)
    {
        $resName = $Row[3];
        // костыли
        if ($resName === 'ПО БЭС') {
            $resName = 'ПО Байкальские ЭС';
        }
        if (mb_strrpos($resName, 'Бичурский РЭС')) {
            $resName = 'Бичурский РЭС';
        }
        if (mb_strrpos($resName, 'Кяхтинский РЭС')) {
            $resName = 'Кяхтинский РЭС';
        }
        if (mb_strrpos($resName, 'Гусиноозерский РЭС')) {
            $resName = 'Гусиноозерский РЭС';
        }
        if (mb_strrpos($resName, 'Усть-Абаканский  РЭС')) {
            $resName = 'Усть-Абаканский РЭС';
        }
        if ($resName === 'ГЭС') {
            $resName = 'Городские ЭС';
        }
        if ($resName === 'ЮЗЭС') {
            $resName = 'Юго-Западные ЭС';
        }
        if ($resName === 'ВЭС') {
            $resName = 'Восточные ЭС';
        }
        if ($resName === 'ЮЭС') {
            $resName = 'Южные ЭС';
        }
        if ($resName === 'ЮВЭС') {
            $resName = 'Юго-восточные ЭС';
        }
        if ($resName === 'ЦЭС') {
            $resName = 'Центральные ЭС';
        }
        if ($resName === 'Большеуковский РЭС') {
            $resName = 'Больше-Уковский РЭС';
        }
        if ($resName === 'ТрудАрмейский РЭС') {
            $resName = 'Трудармейский РЭС';
        }
        //

        $filialName = $Row[1];
        $index = mb_strrpos($resName, 'ЭС');
        $filialId = 0;
        $poId = 0;
        $resId = 0;
        $poName = 0;

        if (mb_substr($resName, $index - 1, 1) === ' '|| mb_substr($resName, $index - 1, 1) === '') {
            foreach ($filials as $filial) {
                if ($filialName === $filial['name']) {
                    $filialId = $filial['id'];
                }
            }
            if ($filialId === 0) {
                throw new Exception('Не рэс и не эс');
            }
            foreach ($poIds as $po) {
                if ($po['branch'] === $filialId) {
                    $tempData['type'] = 'po';
                    $transportValue = $Row[8] === 'Передвижной';
                    $tempData['geometry'] = ['type' => 'Point', 'coordinates' => [(float)$Row[6], (float)$Row[5]]];
                    $tempData['properties'] = ['POId' => $po['composite_id'], 'PO' => $po['name'], 'FiliationId' => $po['branch'],
                        'Filiation' => $Row[1], 'transport' => $transportValue, 'Voltage' => (float)$Row[9]];
                    return $tempData;
                }
            }
        } else if (mb_substr($resName, $index - 1, 1) === 'Р') {
            //обрезаем имя рэса
            if (mb_substr($resName, $index + 2, 1) === ')') {
                $index = mb_strpos($resName, '(');
                $endIndex = mb_strpos($resName, ')');
                $resName = mb_substr($resName, $index + 1, $endIndex - $index - 1);
            }
            if (mb_strpos($resName, 'ПО ЦЭС') !== false) {
                $posIndex = mb_strrpos($resName, 'ПО ЦЭС');
                $resName = mb_substr($resName, 7 + $posIndex);
            }
            if (mb_strpos($resName, 'ПО БЭС') !== false) {
                $resName = mb_substr($resName, 7);
            }
            if (mb_strpos($resName, 'ПО ЮЭС') !== false) {
                $posIndex = mb_strrpos($resName, 'ПО ЮЭС');
                $resIndex = mb_strpos($resName, 'РЭС');
                $resName = mb_substr($resName, 7 + $posIndex, $resIndex - $posIndex - 4);
            }
            if (mb_strpos($resName, 'ПО СВЭС') !== false) {
                $resName = mb_substr($resName, 8);
            }
            //
            foreach ($filials as $filial) {
                if ($filialName === $filial['name']) {
                    $filialId = $filial['id'];
                }
            }
            foreach ($resIds as $res) {
                if (mb_strpos($res['Label'], $resName) !== false) {
                    $resId = $res['RES_id'];
                    $poId = $res['branch'];
                }
            }
            if ($poId === 0) {
                throw new Error('Нет ПО');
            }
            foreach ($poIds as $po) {
                if ($po['composite_id'] === $poId) {
                    $poName = $po['name'];
                }
            }
            $transportValue = $Row[8] === 'Передвижной';
            $result['properties'] = ["RESId" => $resId, "RES" => $resName, "POId" => $poId, "PO" => $poName, "FiliationId" => $filialId, "Filiation" => $filialName,
                "Transport" => $transportValue, "Voltage" => (float)$Row[9]];
            $result['geometry'] = ['type' => 'Point', 'coordinates' => [(float)$Row[6], (float)$Row[5]]];
            $result['type'] = 'res';
            return $result;
        }
        throw new Error('Объект не распознан');
    }


    public function generateReserveAction()
    {
        $reses = $this->getRes();
        $pos = $this->getPo();
        $filials = $this->getFilials();
        $filenames = array_splice(scandir('tmp/AR'), 2);
        $poData = [];
        $resData = [];
        $notFound = [];
        foreach ($filenames as $fileName) {
            $Reader = new SpreadsheetReader('tmp/AR/' . $fileName);
            $i = 0;
            $firstIndex = 0;
            if ($fileName === "AE.xls") $firstIndex = 6;
            if ($fileName === "BE.xls") $firstIndex = 3;
            if ($fileName === "ChE.xls") $firstIndex = 5;
            if ($fileName === "GAES.xls") $firstIndex = 6;
            if ($fileName === "KE.xls") {
                $firstIndex = 4;
            }
            if ($fileName === "KyE.xls") {
                $firstIndex = 7;
            }
            if ($fileName === "OE.xls") {
                $firstIndex = 6;
            }
            if ($fileName === "TE.xls") {
                //$firstIndex = 6;
                continue;
            }
            if ($fileName === "XE.xls") {
                $firstIndex = 3;
            }
            foreach ($Reader as $Row) {
                if ($i >= $firstIndex) {
                    $item = $this->generateReserveData($reses, $pos, $filials, $Row, $i);
                    if (isset($item['type'])) {
                        if ($item['type'] === 'po') {
                            unset($item['type']);
                            $item['properties']['type'] = 'po';
                            $poData[] = $item;
                        } else {
                            unset($item['type']);
                            $item['properties']['type'] = 'res';
                            $resData[] = $item;
                        }
                    } else {
                        if ($Row[3] !== 'Центральный склад аварийного запаса ИА филиала') {
                            echo '1';
                        }
                        $notFound[$Row[3]] = $Row;
                    }
                }
                $i++;
            }
        }
        $allData = array_merge($resData, $poData);
        $jsonString = $this->safe_json_encode($allData);
        echo '1';
    }

    private function generateReserveData($reses, $pos, $filials, $Row, $counter)
    {
        if ($Row[3] === "") {
            return [];
        }
        if (trim($Row[3]) === 'ПО ЮЭС') {
            $Row[3] = 'Южные ЭС';
        }
        if (trim($Row[3]) === 'ПО БЭС') {
            $Row[3] = 'Байкальские ЭС';
        }
        if (trim($Row[3]) === 'ПО ЦЭС') {
            $Row[3] = 'Центральные ЭС';
        }
        if (trim($Row[2]) === 'ГАЭС') {
            $Row[2] = 'Горно-Алтайские ЭС';
        }
        //
        $resName = trim($Row[3]);
        $filialName = trim($Row[2]);
        $poName = '';
        $filialId = 0;
        $resId = 0;
        $poId = 0;
        foreach ($filials as $filial) {
            if ($filial['name'] === $Row[2]) {
                $filialId = $filial['id'];
                break;
            }
        }
        $esIndex = mb_strrpos($resName, 'ЭС');
        if (mb_substr($resName, $esIndex - 1, 1) === ' ') {
            if ($Row[2] === 'Кузбассэнерго') {
                $Row[2] = 'Кузбассэнерго-РЭС';
                $filialName = $Row[2];
            }
            if ($Row[2] === 'Омскэнерго') {
                $resName = mb_substr($resName, 0, $esIndex + 2);
            }
            //$resName = mb_substr($resName, 0, $esIndex+1);
            foreach ($pos as $po) {
                if (mb_strrpos($po['name'], $resName) !== false) {
                    //check res id
                    $poId = $po['composite_id'];
                    break;
                }
            }
            if ($poId === 0) {
                return [];
            }
            $result['properties'] = ["POId" => $poId, "PO" => $resName, "FiliationId" => $filialId, "Filiation" => $filialName,
                "Address" => $Row[4]];
            $result['geometry'] = ['type' => 'Point', 'coordinates' => [(float)$Row[13], (float)$Row[12]]];
            $result['type'] = 'po';
            return $result;
        } else if (mb_substr($resName, $esIndex - 1, 1) === 'Р') {
            // parse name
            if (mb_substr($resName, $esIndex + 2, 1) === ')' || mb_substr($resName, $esIndex + 3, 1) === ')') {
                $index = mb_strpos($resName, '(');
                $endIndex = mb_strpos($resName, ')');
                $resName = trim(mb_substr($resName, $index + 1, $endIndex - $index - 1));
                $poName = trim(mb_substr($Row[3], 0, $index - 1));
            } else if (mb_strpos($resName, 'ПО ЦЭС') !== false && $filialName === 'Бурятэнерго') {
                $poName = 'Центральные ЭС';
                $tempIndex = mb_strpos($resName, 'ЦЭС');
                $resName = trim(mb_substr($resName, $tempIndex + 3));
            } else if (mb_strpos($resName, 'ПО БЭС') !== false && $filialName === 'Бурятэнерго') {
                $poName = 'Байкальские ЭС';
                $tempIndex = mb_strpos($resName, 'БЭС');
                $resName = trim(mb_substr($resName, $tempIndex + 3));
            } else if (mb_strpos($resName, 'ПО ЮЭС') !== false && $filialName === 'Бурятэнерго') {
                $poName = 'Южные ЭС';
                $tempIndex = mb_strpos($resName, 'ЮЭС');
                $resName = trim(mb_substr($resName, $tempIndex + 3));
            }
            //
            if (mb_strpos($poName, 'Юго-Восточные ЭС') !== false && $filialName === 'Читаэнерго') {
                $poName = 'Юго-восточные ЭС';
            }
            if ($Row[2] === 'Горно-Алтайские ЭС') {
                $poName = $Row[2];
                $Row[2] = 'Горно-Алтайские электрические сети';
            }
            if ($Row[2] === 'Кузбассэнерго') {
                $Row[2] = 'Кузбассэнерго-РЭС';
            }
            if ($poName === '' && $Row[2] === 'Омскэнерго') {
                $esIndex = mb_strpos($resName, ' ЭС');
                $poName = trim(mb_substr($resName, 0, $esIndex + 2));

                $resIndex = mb_strpos($resName, 'РЭС');
                $bracketIndex = mb_strpos($resName, '(');
                if ($bracketIndex !== false) {
                    $resName = trim(mb_substr($resName, $bracketIndex + 1, $resIndex + 2));
                } else {
                    $resName = trim(mb_substr($resName, $esIndex + 2, $resIndex + 2));
                }
            }
            if ($poName === '' && $Row[2] === 'Хакасэнерго') {
                $tempPoId = 0;
                $tempFIlialId = '19';
                foreach ($reses as $res) {
                    if (mb_strpos($res['Label'], $resName) !== false && mb_strpos($res['branch'], $tempFIlialId) !== false) {
                        $tempPoId = $res['branch'];
                        break;
                    }
                }
                foreach ($pos as $po) {
                    if ($po['composite_id'] === $tempPoId) {
                        $poName = mb_substr($po['name'], 3);
                    }
                }

            }
            if ($poName === '') {
                return [];
            }

            foreach ($filials as $filial) {
                if ($filial['name'] === $Row[2]) {
                    $filialId = $filial['id'];
                    break;
                }
            }
            if ($filialId === 0) {
                return [];
            }
            foreach ($pos as $po) {
                if (mb_strpos($po['name'], $poName) !== false && $po['branch'] === $filialId) {
                    $poId = $po['composite_id'];
                    break;
                }
            }
            if ($poId === 0) {
                return [];
            }
            foreach ($reses as $res) {
                if (mb_strpos($res['Label'], $resName) !== false && $res['branch'] === $poId) {
                    $resId = $res['RES_id'];
                    break;
                }
            }
            $result['properties'] = ["RESId" => $resId, "RES" => $resName, "POId" => $poId, "PO" => $poName, "FiliationId" => $filialId, "Filiation" => $filialName,
                "Address" => $Row[4]];
            $result['geometry'] = ['type' => 'Point', 'coordinates' => [(float)$Row[13], (float)$Row[12]]];
            $result['type'] = 'res';
            return $result;
        }
        return [];
    }


    ///// https://stackoverflow.com/questions/10199017/how-to-solve-json-error-utf8-error-in-php-json-decode
    function safe_json_encode($value, $options = 0, $depth = 512)
    {
        $encoded = json_encode($value, $options, $depth);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encoded;
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
            case JSON_ERROR_UTF8:
                $clean = $this->utf8ize($value);
                return $this->safe_json_encode($clean, $options, $depth);
            default:
                return 'Unknown error'; // or trigger_error() or throw new Exception()

        }
    }

    function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string($mixed)) {
            return utf8_encode($mixed);
        }
        return $mixed;
    }

    ///////////////////////////////////

    public function createResStaffAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $response = [];
        $filenames = array_splice(scandir('tmp/staff'), 2);
        foreach ($filenames as $fileName) {
            $Reader = new SpreadsheetReader('tmp/staff/' . $fileName);
            $i = 0;
            foreach ($Reader->Sheets() as $key => $value) {
                if ($value === 'Таблица персонал РЭС') {
                    $Reader->ChangeSheet($key);
                }
            }
            if ($fileName === "AE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Алтайэнерго';
                $filialId = '22';
            }
            if ($fileName === "BE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Бурятэнерго';
                $filialId = '03';
            }
            if ($fileName === "ChE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Читаэнерго';
                $filialId = '75';
            }
            if ($fileName === "GAES.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Горно-Алтайские электрические сети';
                $filialId = '04';
            }
            if ($fileName === "KrE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Красноярскэнерго';
                $filialId = '24';
            }
            if ($fileName === "KyE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Кузбассэнерго-РЭС';
                $filialId = '42';
            }
            if ($fileName === "OE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Омскэнерго';
                $filialId = '55';
            }
            if ($fileName === "TE.xls") {
                continue;
            }
            if ($fileName === "XE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 8;
                $currentFilial = 'Хакасэнерго';
                $filialId = '19';
            }
            $personalRes = [];
            $currentRes = '';
            $currentResId = '';
            $currentPo = '';
            $tempResPersonal = [];
            $currentPoId = '';
            foreach ($Reader as $Row) {
                $resChangeFlag = false;
                $item = [];
                if ($i >= $firstIndex) {
                    if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings + 1) == 0) {
                        if ($tempResPersonal !== []) {
                            $personalRes = array_merge($personalRes, $tempResPersonal);
                            $tempResPersonal = [];
                        }
                        if ($Row[1] === 'Улаганский РЭС') {
                            echo '1';
                        }
                        $currentResId = "";
                        foreach ($reses as $res) {
                            if (mb_strrpos(mb_strtoupper($res['Label']), mb_strtoupper(trim($Row[1]))) !== false
                                && mb_strrpos($res['branch'], $filialId) !== false) {
                                $currentResId = $res['RES_id'];
                                $currentRes = $res['Label'];
                                $currentPoId = $res['branch'];
                                foreach ($pos as $po) {
                                    if ($po['composite_id'] === $currentPoId) {
                                        $currentPo = $po['name'];
                                    }
                                }
                                break;
                            }
                        }
                        if ($currentResId == "") {
                            echo 'Не найден РЭС';
                        }
                    } else {
                        $item = $this->createResStaff($staff, $Row, 'personalRes');
                        if ($item !== []) {
                            if ($item['iconPath'][0] !== '/') {
                                echo 'no icon';
                            }
                            $item['resId'] = $currentResId;
                            $notSetFlag = true;
                            $setIndex = -1;
                            for ($j = 0; $j < count($tempResPersonal); $j++) {
                                if ($tempResPersonal[$j]['name'] === $item['name']) {
                                    $notSetFlag = false;
                                    $setIndex = $j;
                                    break;
                                }
                            }
                            if ($notSetFlag === true) {
                                $tempResPersonal[] = $item;
                            } else {
                                $tempResPersonal[$setIndex]['staff'][] = $item['staff'][0];
                                $tempResPersonal[$setIndex]['count'] += $item['count'];
                            }
                        }
                    }
                }
                $i++;
            }
            // $personalRes = array_merge($personalRes, $tempResPersonal);
            $response = array_merge($response, $personalRes);
        }
        $str = json_encode($response);
        echo '1';
    }

    private function createResStaff($staff, $Row, $dataName) //personalPo || personalRes
    {
        if ($Row[2] == 0) {
            return [];
        }
        $person = [];
        $seekingName = $Row[1];
        $realName = '';
        $attributes = [];
        ////////////////////////////////
        if ($Row[1] === 'Мастера
(в т.ч. старший мастер)') {  // не трогать там разрыв строки
            $seekingName = 'Мастер (старший мастер)';
            $personIcon = '/img/trainer/master.png';
        }
        if ($Row[1] === 'Электромонтеры РС
(без учета ОВБ)') {
            $seekingName = 'Электромонтер';
            $personIcon = '/img/trainer/electricMan.png';
        }
        if ($Row[1] === 'Водитель
(без учета электромонтеров - водителей)') {  // не трогать там разрыв строки
            $seekingName = 'Водитель';
            $seekingAttr = 'Без специальных разрешений';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $person = ["count" => $Row[2],
                        'walk' => true,
                        'name' => $seekingName,
                        'iconPath' => '/img/trainer/driver.png',
                        'typeStaff' => 'Res',
                        'staff' => [["name" => "Распредсеть",
                            "feature" => $chelik['featureSign'],
                            'count' => $Row[2],
                            "default" => [
                                "attribute" => $seekingAttr,
                                "color" => $chelik['color'],
                                "colorIcon" => "#000000"
                            ]]]];
                    return $person;
                }
            }
        }
        if ($Row[1] === 'ДЭМ ПС
(по штатному расписанию, без учета сменности)') {  // не трогать там разрыв строки
            $seekingName = 'Электромонтер';
            $seekingAttr = 'Дежурный на ПС';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $person = ["count" => $Row[2],
                        'walk' => true,
                        'name' => $seekingName,
                        'iconPath' => '/img/trainer/electricMan.png',
                        'typeStaff' => 'Res',
                        'staff' => [["name" => "Дежурный",
                            "feature" => $chelik['featureSign'],
                            'count' => $Row[2],
                            "default" => [
                                "attribute" => $seekingAttr,
                                "color" => $chelik['color'],
                                "colorIcon" => "#000000"
                            ]]]];
                    return $person;
                }
            }
        }
        if ($Row[1] === 'Электромонтеры ОВБ
(по штатному расписанию, без учета сменности)') {// не трогать там разрыв строки
            $seekingName = 'Электромонтер';
            $feature = 'ОВБ';
            $seekingAttr = 'ОВБ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $person = ["count" => $Row[2],
                        'walk' => true,
                        'name' => $seekingName,
                        'iconPath' => '/img/trainer/ovb.png',
                        'typeStaff' => 'Res',
                        'staff' => [["name" => "ОВБ",
                            "feature" => $chelik['featureSign'],
                            'count' => $Row[2],
                            "default" => [
                                "attribute" => $seekingAttr,
                                "color" => $chelik['color'],
                                "colorIcon" => "#000000"
                            ]]]];
                    return $person;
                }
            }
        }

        if ($Row[1] === 'Кладовщик') {  //
            $seekingName = 'Кладовщик';
            $seekingAttr = 'РЭС';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $person = ["count" => $Row[2],
                        'walk' => true,
                        'name' => $seekingName,
                        'iconPath' => '/img/trainer/storekeeper.png',
                        'typeStaff' => 'Res',
                        'staff' => [["name" => "Распредсеть",
                            "feature" => $chelik['featureSign'],
                            'count' => $Row[2],
                            "default" => [
                                "attribute" => $seekingAttr,
                                "color" => $chelik['color'],
                                "colorIcon" => "#000000"
                            ]]]];
                    return $person;
                }
            }
        }

        if ($Row[1] === 'Главный инженер РЭС') {  //
            $seekingName = 'Главный инженер РЭС';
            $personIcon = '/img/trainer/majorEngineerRes.png';
        }

        if ($Row[1] === 'Начальник РЭС') {
            $personIcon = '/img/trainer/chiefRes.png';
        }

        ////////////////////////////////
        if ($Row[3] != 0) {//оперативные права
            $seekingAttr = 'Оперативные права';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[3],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[4] != 0) {//право ответстенногог руководителя работ
            $seekingAttr = 'Ответственный руководитель работ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[4],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }

        }
        if ($Row[5] != 0) {//право производителя работ
            $seekingAttr = 'Производитель работ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FF0000"];
                }
            }
        }
        if ($Row[6] != 0) {//право выдачи нарядов
            $seekingAttr = 'С правом выдачи нарядов';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[6],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[7] != 0) {//право работы на высоте
            $seekingAttr = 'Работа на высоте';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[7],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[8] != 0) {//входящие в состав МАВБ
            $seekingAttr = 'МАВБ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[8],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        //find default color
        $color = 0;
        $feature = 0;
        $seekingAttr = 'Без специальных разрешений';
        foreach ($staff[$dataName] as $chelik) {
            if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                $color = $chelik['color'];
                $feature = $chelik['featureSign'];
                break;
            }
        }
        //
        if ($color === 0) {
            echo 'Ошибка';
        }
        $answer = [
            'name' => $seekingName,
            'count' => $Row[2],
            'walk' => true,
            'iconPath' => $personIcon,
            'typeStaff' => 'Res',
            'staff' => [['name' => 'Распредсеть', 'count' => $Row[2], "feature" => $feature, 'attributes' => $attributes,
                'default' => ["attribute" => "Без специальных разрешений",
                    "color" => $color,
                    "colorIcon" => "#000000"]]]
        ];
        return $answer;
    }


    public function createResTechAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $response = [];
        $filenames = array_splice(scandir('tmp/staff'), 2);
        foreach ($filenames as $fileName) {
            $Reader = new SpreadsheetReader('tmp/staff/' . $fileName);
            $i = 0;
            foreach ($Reader->Sheets() as $key => $value) {
                if ($value === 'Техника РЭС') {
                    $Reader->ChangeSheet($key);
                }
            }
            if ($fileName === "AE.xls") {
                $firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Алтайэнерго';
                $filialId = '22';
            }
            if ($fileName === "BE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Бурятэнерго';
                $filialId = '03';
            }
            if ($fileName === "ChE.xls") {
                $firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Читаэнерго';
                $filialId = '75';
            }
            if ($fileName === "GAES.xls") {
                $firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Горно-Алтайские электрические сети';
                $filialId = '04';
            }
            if ($fileName === "KrE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Красноярскэнерго';
                $filialId = '24';
            }
            if ($fileName === "KyE.xls") {
                $firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Кузбассэнерго-РЭС';
                $filialId = '42';
            }
            if ($fileName === "OE.xls") {
                $firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Омскэнерго';
                $filialId = '55';
            }
            if ($fileName === "TE.xls") {
                continue;
            }
            if ($fileName === "XE.xls") {
                $firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Хакасэнерго';
                $filialId = '19';
            }
            $personalRes = [];
            $currentRes = '';
            $currentResId = '';
            $currentPo = '';
            $currentPoId = '';
            $tempResPersonal = [];
            foreach ($Reader as $Row) {
                $resChangeFlag = false;
                $item = [];
                if ($i >= $firstIndex) {
                    if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings) == 0) {
                        if ($tempResPersonal !== []) {
                            $personalRes = array_merge($personalRes, $tempResPersonal);
                            $tempResPersonal = [];
                        }
                        $currentResId = "";
                        if (mb_strrpos($Row[2], '(') !== false) {
                            $index = mb_strrpos($Row[2], '(');
                            $Row[2] = trim(mb_substr($Row[2], 0, $index - 1));
                        }
                        foreach ($reses as $res) {
                            if (mb_strrpos(mb_strtoupper($res['Label']), mb_strtoupper(trim($Row[2]))) !== false
                                && mb_strrpos($res['branch'], $filialId) !== false) {
                                $currentResId = $res['RES_id'];
                                $currentRes = $res['Label'];
                                $currentPoId = $res['branch'];
                                foreach ($pos as $po) {
                                    if ($po['composite_id'] === $currentPoId) {
                                        $currentPo = $po['name'];
                                    }
                                }
                                break;
                            }
                        }
                        if ($currentResId == "") {
                            echo 'Не найден РЭС';
                        }
                    } else {
                        $item = $this->createResTech($staff, $Row, 'techRes');
                        if ($item !== []) {
                            if ($item['iconPath'][0] !== '/') {
                                echo 'no icon';
                            }
                            $item['resId'] = $currentResId;
                            $notSetFlag = true;
                            $setIndex = -1;
                            for ($j = 0; $j < count($tempResPersonal); $j++) {
                                if ($tempResPersonal[$j]['name'] === $item['name']) {
                                    $notSetFlag = false;
                                    $setIndex = $j;
                                    break;
                                }
                            }
                            if ($notSetFlag === true) {
                                $tempResPersonal[] = $item;
                            } else {
                                $tempResPersonal[$setIndex]['staff'][] = $item['staff'][0];
                                $tempResPersonal[$setIndex]['count'] += $item['count'];
                            }
                        }
                    }
                }
                $i++;
            }
            $personalRes = array_merge($personalRes, $tempResPersonal);
            $response = array_merge($response, $personalRes);
        }
        $str = json_encode($response);

        echo '1';
    }

    private function createResTech($staff, $Row, $dataName) //personalPo || personalRes
    {
        if ($Row[3] == 0) {
            return [];
        }
        $seekingName = $Row[2];
        $attributes = [];

        if ($Row[2] === 'Бригадный а/м') {
            $seekingName = 'Бригадный';
            $personIcon = '/img/trainer/brigadeCar.png';
            $seekingFeature = 'Распредсеть';
        }
        if ($Row[2] === 'БКМ') {
            $personIcon = '/img/trainer/bkm.png';
            $seekingFeature = 'Распредсеть';
        }
        if ($Row[2] === 'КМУ') {
            $personIcon = '/img/trainer/kmu.png';
            $seekingFeature = 'Распредсеть';
        }
        if ($Row[2] === 'Кран') {
            $personIcon = '/img/trainer/kran.png';
            $seekingFeature = 'Кран';
        }
        if ($Row[2] === 'Снегоход') {
            $personIcon = '/img/trainer/snowWalker.png';
            $seekingFeature = 'Снегоход';
        }
        if ($Row[2] === 'АГП') {
            $personIcon = '/img/trainer/agp.png';
            $seekingFeature = 'Распредсети';
        }
        if ($Row[2] === 'РИСЭ') {
            $personIcon = '/img/trainer/rise.png';
            $seekingFeature = 'РИСЭ погружной';
        }
        if ($Row[2] === 'Трал') {
            $personIcon = '/img/trainer/trall.png';
            $seekingFeature = 'Трал';
        }
        if ($Row[2] === 'Вездеход') {
            $personIcon = '/img/trainer/vezdehod.png';
            $seekingFeature = 'Гусеничный';
        }
        if ($Row[2] === 'Бульдозер') {
            $personIcon = '/img/trainer/buldozer.png';
            $seekingFeature = 'Бульдозер';
        }
        if ($Row[2] === 'Длинномер') {
            $personIcon = '/img/trainer/dlinnomer.png';
            $seekingFeature = 'Длинномер';
        }


        ////////////////////////////////
        if ($Row[4] != 0) {//Высокопроходимый
            $seekingAttr = 'Высокопроходимый';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr &&
                    $seekingFeature === $chelik['feature']) {
                    $attributes[] = ["count" => $Row[4],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[5] != 0) {//Тягач
            $seekingAttr = 'Тягач';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr &&
                    $seekingFeature === $chelik['feature']) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        //find default color
        $color = 0;
        $feature = 0;
        $seekingAttr = 'Без специальных разрешений';
        $seekingFeature = '';
        if ($seekingName === 'Снегоход') {
            $seekingAttr = 'Тягач';
        }
        if ($seekingName === 'Длинномер') {
            $seekingAttr = 'Высокопроходимый';
        }
        if ($seekingName === 'Трал') {
            $seekingAttr = 'Тягач';
        }
        foreach ($staff[$dataName] as $chelik) {
            if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                $color = $chelik['color'];
                $feature = $chelik['featureSign'];
                $featureName = $chelik['feature'];
                break;
            }
        }
        //
        if ($color === 0 || !isset($personIcon)) {
            echo 'Ошибка'; //МКМ
            return [];
        }
        $answer = [
            'name' => $seekingName,
            'count' => $Row[3],
            'walk' => false,
            'iconPath' => $personIcon,
            'typeStaff' => 'Res',
            'staff' => [['name' => $featureName, 'count' => $Row[3], "feature" => $feature, 'attributes' => $attributes,
                'default' => ["attribute" => $seekingAttr,
                    "color" => $color,
                    "colorIcon" => "#000000"]]]
        ];
        return $answer;
    }

    /////////////////////////////////////////////////////////////////////////po tech
    ///

    public function createPoTechAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $pos = $this->getPo();
        $response = [];
        $filenames = array_splice(scandir('tmp/staff'), 2);
        foreach ($filenames as $fileName) {
            $Reader = new SpreadsheetReader('tmp/staff/' . $fileName);
            $i = 0;
            foreach ($Reader->Sheets() as $key => $value) {
                if ($value === 'Техника ПО') {
                    $Reader->ChangeSheet($key);
                }
            }
            if ($fileName === "AE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Алтайэнерго';
                $filialId = '22';
            }
            if ($fileName === "BE.xls") {
                $firstIndex = 4;
                $numberOfStrings = 12;
                $currentFilial = 'Бурятэнерго';
                $filialId = '03';
            }
            if ($fileName === "ChE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Читаэнерго';
                $filialId = '75';
            }
            if ($fileName === "GAES.xls") {
                continue;
                /*$firstIndex = 2;
                $numberOfStrings = 12;
                $currentFilial = 'Горно-Алтайские электрические сети';
                $filialId = '04';*/
            }
            if ($fileName === "KrE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Красноярскэнерго';
                $filialId = '24';
            }
            if ($fileName === "KyE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Кузбассэнерго-РЭС';
                $filialId = '42';
            }
            if ($fileName === "OE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Омскэнерго';
                $filialId = '55';
            }
            if ($fileName === "TE.xls") {
                continue;
            }
            if ($fileName === "XE.xls") {
                continue;
                /*$firstIndex = 3;
                $numberOfStrings = 12;
                $currentFilial = 'Хакасэнерго';
                $filialId = '19';*/
            }
            $currentPo = '';
            $currentPoId = '';
            $personalPo = [];
            $tempPoPersonal = [];
            foreach ($Reader as $Row) {
                $resChangeFlag = false;
                $item = [];
                if ($i >= $firstIndex) {
                    if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings) == 0) {
                        if ($tempPoPersonal !== []) {
                            $personalPo = array_merge($personalPo, $tempPoPersonal);
                            $tempPoPersonal = [];
                        }
                        $currentPoId = "";
                        if (mb_strrpos($Row[1], '(') !== false) {
                            $index = mb_strrpos($Row[1], '(');
                            $Row[1] = trim(mb_substr($Row[1], 0, $index - 1));
                        }
                        //parse names
                        $esIndex = mb_strrpos($Row[1], 'ЭС');
                        $startIndex = mb_strrpos($Row[1], 'ПО');
                        $realNameShort = mb_substr($Row[1], $startIndex + 3, $esIndex - $startIndex - 3);
                        if (mb_strlen($realNameShort) === 1) {
                            $regularStr = "ПО " . $realNameShort . "[а-я]+ ЭС";
                        } else if (mb_strlen($realNameShort) === 2) {
                            $regularStr = "ПО " . mb_substr($realNameShort, 0, 1) .
                                "[а-я]+-" . mb_substr($realNameShort, 1, 1) . "[а-я]+ ЭС";
                        } else {
                            echo 'Длинное имя';
                        }

                        //
                        foreach ($pos as $po) {
                            if (mb_ereg_match($regularStr, $po['name'], 'i') !== false
                                && mb_strrpos($po['branch'], $filialId) !== false) {
                                $currentPoId = $po['composite_id'];
                                $currentPo = $po['name'];
                                break;
                            }
                        }
                        if ($currentPoId == "") {
                            echo 'Не найден ПО';
                        }
                    } else {
                        $item = $this->createPoTech($staff, $Row, 'techRes');
                        if ($item !== []) {
                            if ($item['iconPath'][0] !== '/') {
                                echo 'no icon';
                            }
                            $item['poId'] = $currentPoId;
                            $notSetFlag = true;
                            $setIndex = -1;
                            for ($j = 0; $j < count($tempPoPersonal); $j++) {
                                if ($tempPoPersonal[$j]['name'] === $item['name']) {
                                    $notSetFlag = false;
                                    $setIndex = $j;
                                    break;
                                }
                            }
                            if ($notSetFlag === true) {
                                $tempPoPersonal[] = $item;
                            } else {
                                $tempPoPersonal[$setIndex]['staff'][] = $item['staff'][0];
                            }
                        }
                    }
                }
                $i++;
            }
            $personalPo = array_merge($personalPo, $tempPoPersonal);
            $response = array_merge($response, $personalPo);
        }
        $str = json_encode($response);
        echo '1';
    }

    private function createPoTech($staff, $Row, $dataName) //personalPo || personalRes
    {
        if ($Row[2] == 0) {
            return [];
        }
        $seekingName = $Row[1];
        $attributes = [];

        if ($Row[1] === 'Бригадный а/м') {
            $seekingName = 'Бригадный';
            $personIcon = '/img/trainer/brigadeCar.png';
            $seekingFeature = 'Распредсеть';
        }
        if ($Row[1] === 'БКМ') {
            $personIcon = '/img/trainer/bkm.png';
            $seekingFeature = 'Распредсеть';
        }
        if ($Row[1] === 'КМУ') {
            $personIcon = '/img/trainer/kmu.png';
            $seekingFeature = 'Распредсеть';
        }
        if ($Row[1] === 'Кран') {
            $personIcon = '/img/trainer/kran.png';
            $seekingFeature = 'Кран';
        }
        if ($Row[1] === 'Снегоход') {
            $personIcon = '/img/trainer/snowWalker.png';
            $seekingFeature = 'Снегоход';
        }
        if ($Row[1] === 'АГП') {
            $personIcon = '/img/trainer/agp.png';
            $seekingFeature = 'Распредсети';
        }
        if ($Row[1] === 'РИСЭ') {
            $personIcon = '/img/trainer/rise.png';
            $seekingFeature = 'РИСЭ погружной';
        }
        if ($Row[1] === 'Трал') {
            $personIcon = '/img/trainer/trall.png';
            $seekingFeature = 'Трал';
        }
        if ($Row[1] === 'Вездеход') {
            $personIcon = '/img/trainer/vezdehod.png';
            $seekingFeature = 'Гусеничный';
        }
        if ($Row[1] === 'Бульдозер') {
            $personIcon = '/img/trainer/buldozer.png';
            $seekingFeature = 'Бульдозер';
        }
        if ($Row[1] === 'Длинномер') {
            $personIcon = '/img/trainer/dlinnomer.png';
            $seekingFeature = 'Длинномер';
        }


        ////////////////////////////////
        if ($Row[3] != 0) {//Высокопроходимый
            $seekingAttr = 'Высокопроходимый';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr &&
                    $seekingFeature === $chelik['feature']) {
                    $attributes[] = ["count" => $Row[3],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[4] != 0) {//Тягач
            $seekingAttr = 'Тягач';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr &&
                    $seekingFeature === $chelik['feature']) {
                    $attributes[] = ["count" => $Row[4],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        $seekingAttr = 'Без специальных разрешений';
        $seekingFeature = '';
        if ($seekingName === 'Снегоход') {
            $seekingAttr = 'Тягач';
        }
        if ($seekingName === 'Длинномер') {
            $seekingAttr = 'Высокопроходимый';
        }
        if ($seekingName === 'Трал') {
            $seekingAttr = 'Тягач';
        }
        if ($Row[5] != 0) {//СВЛ
            $seekingFeature = 'СВЛ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingFeature,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[6] != 0) {//СПС
            $seekingFeature = 'СПС';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingFeature,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[7] != 0) {//СИЗПИ
            $seekingFeature = 'СИЗП';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingFeature,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[8] != 0) {// СРЗА
            $seekingFeature = 'РЗА';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingFeature,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        //find default color
        $color = 0;
        $feature = 0;
        $seekingAttr = 'Без специальных разрешений';
        $seekingFeature = '';
        if ($seekingName === 'Снегоход') {
            $seekingAttr = 'Тягач';
        }
        if ($seekingName === 'Длинномер') {
            $seekingAttr = 'Высокопроходимый';
        }
        if ($seekingName === 'Трал') {
            $seekingAttr = 'Тягач';
        }
        foreach ($staff[$dataName] as $chelik) {
            if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr) {
                $color = $chelik['color'];
                $feature = $chelik['featureSign'];
                $featureName = $chelik['feature'];
                break;
            }
        }
        //
        if ($color === 0 || !isset($personIcon)) {
            echo 'Ошибка'; //МКМ
            return [];
        }
        $answer = [
            'name' => $seekingName,
            'count' => $Row[2],
            'walk' => false,
            'iconPath' => $personIcon,
            'typeStaff' => 'Po',
            'staff' => [['name' => $featureName, 'count' => $Row[2], "feature" => $feature, 'attributes' => $attributes,
                'default' => ["attribute" => $seekingAttr,
                    "color" => $color,
                    "colorIcon" => "#000000"]]]
        ];
        return $answer;
    }


    ///poStaff
    ///
    public function createPoStaffAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $pos = $this->getPo();
        $response = [];
        $filenames = array_splice(scandir('tmp/staff'), 2);
        foreach ($filenames as $fileName) {
            $Reader = new SpreadsheetReader('tmp/staff/' . $fileName);
            $i = 0;
            foreach ($Reader->Sheets() as $key => $value) {
                if ($value === 'Таблица персонал ПО') {
                    $Reader->ChangeSheet($key);
                }
            }
            if ($fileName === "AE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Алтайэнерго';
                $filialId = '22';
            }
            if ($fileName === "BE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Бурятэнерго';
                $filialId = '03';
            }
            if ($fileName === "ChE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Читаэнерго';
                $filialId = '75';
            }
            if ($fileName === "GAES.xls") {
                /*$firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Горно-Алтайские электрические сети';
                $filialId = '04';*/
                continue;
            }
            if ($fileName === "KrE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Красноярскэнерго';
                $filialId = '24';
            }
            if ($fileName === "KyE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Кузбассэнерго-РЭС';
                $filialId = '42';
            }
            if ($fileName === "OE.xls") {
                $firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Омскэнерго';
                $filialId = '55';
            }
            if ($fileName === "TE.xls") {
                continue;
            }
            if ($fileName === "XE.xls") {
                /*$firstIndex = 3;
                $numberOfStrings = 25;
                $currentFilial = 'Хакасэнерго';
                $filialId = '19';*/
                continue;
            }
            $currentPo = '';
            $currentPoId = '';
            $personalPo = [];
            $tempPoPersonal = [];
            foreach ($Reader as $Row) {
                $resChangeFlag = false;
                $item = [];
                if ($i >= $firstIndex) {
                    if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings) == 0) {
                        if ($tempPoPersonal !== []) {
                            $personalPo = array_merge($personalPo, $tempPoPersonal);
                            $tempPoPersonal = [];
                        }
                        $currentPoId = "";
                        if (mb_strrpos($Row[1], '(') !== false) {
                            $index = mb_strrpos($Row[1], '(');
                            $Row[1] = trim(mb_substr($Row[1], 0, $index - 1));
                        }
                        //parse names
                        $esIndex = mb_strrpos($Row[1], 'ЭС');
                        $startIndex = mb_strrpos($Row[1], 'ПО');
                        $realNameShort = mb_substr($Row[1], $startIndex + 3, $esIndex - $startIndex - 3);
                        if (mb_strlen($realNameShort) === 1) {
                            $regularStr = "ПО " . $realNameShort . "[а-я]+ ЭС";
                        } else if (mb_strlen($realNameShort) === 2) {
                            $regularStr = "ПО " . mb_substr($realNameShort, 0, 1) .
                                "[а-я]+-" . mb_substr($realNameShort, 1, 1) . "[а-я]+ ЭС";
                        } else {
                            echo 'Длинное имя';
                        }

                        //
                        foreach ($pos as $po) {
                            if (mb_ereg_match($regularStr, $po['name'], 'i') !== false
                                && mb_strrpos($po['branch'], $filialId) !== false) {
                                $currentPoId = $po['composite_id'];
                                $currentPo = $po['name'];
                                break;
                            }
                        }
                        if ($currentPoId == "") {
                            echo 'Не найден ПО';
                        }
                    } else {
                        $item = $this->createPoStaff($staff, $Row, 'personalPo');
                        if ($item !== []) {
                            if ($item['iconPath'][0] !== '/') {
                                echo 'no icon';
                            }
                            $item['poId'] = $currentPoId;
                            $notSetFlag = true;
                            $setIndex = -1;
                            for ($j = 0; $j < count($tempPoPersonal); $j++) {
                                if ($tempPoPersonal[$j]['name'] === $item['name']) {
                                    $notSetFlag = false;
                                    $setIndex = $j;
                                    break;
                                }
                            }
                            if ($notSetFlag === true) {
                                $tempPoPersonal[] = $item;
                            } else {
                                $tempPoPersonal[$setIndex]['staff'][] = $item['staff'][0];
                                $tempPoPersonal[$setIndex]['count'] += $item['count'];
                            }
                        }
                    }
                }
                $i++;
            }
            $personalPo = array_merge($personalPo, $tempPoPersonal);
            $response = array_merge($response, $personalPo);
        }
        $str = json_encode($response);
        echo '1';
    }

    private function createPoStaff($staff, $Row, $dataName) //personalPo || personalRes
    {
        if ($Row[2] == 0) {
            return [];
        }
        $seekingName = $Row[1];
        $attributes = [];

        if ($Row[1] === 'Электромонтеры СВЛ') {
            $seekingName = 'Электромонтер';
            $personIcon = '/img/trainer/electricMan.png';
            $seekingFeature = 'СВЛ';
        }
        if ($Row[1] === 'Электромонтеры (электрослесари) СПС') {
            $seekingName = 'Электромонтер';
            $personIcon = '/img/trainer/electricMan.png';
            $seekingFeature = 'СПС';
        }
        if ($Row[1] === 'Электромонтеры (электрослесари) СРЗА') {
            $seekingName = 'Электромонтер';
            $personIcon = '/img/trainer/electricMan.png';
            $seekingFeature = 'РЗА';
        }
        if ($Row[1] === 'Электромонтеры (электрослесари) СИзП') {
            /*$seekingName = 'Электромонтер'; //В таблице нет электромонтеров СИЗП
            $personIcon = '/img/trainer/electricMan.png';
            $seekingFeature = 'СИЗП';*/
            return [];
        }
        if ($Row[1] === 'Мастера СВЛ
(в т.ч. старший мастер)') {
            $seekingName = 'Мастер (старший мастер)';
            $personIcon = '/img/trainer/master.png';
            $seekingFeature = 'СВЛ';
        }
        if ($Row[1] === 'Мастера СПС
(в т.ч. старший мастер)') {
            $seekingName = 'Мастер (старший мастер)';
            $personIcon = '/img/trainer/master.png';
            $seekingFeature = 'СПС';
        }
        if ($Row[1] === 'Инженер (всех категорий) СВЛ') {
            $seekingName = 'Инженер (всех категорий)';
            $personIcon = '/img/trainer/engineer.png';
            $seekingFeature = 'СВЛ';
        }
        if ($Row[1] === 'Инженер (всех категорий) СПС') {
            $seekingName = 'Инженер (всех категорий)';
            $personIcon = '/img/trainer/engineer.png';
            $seekingFeature = 'СПС';
        }
        if ($Row[1] === 'Инженер (всех категорий) СРЗА') {
            $seekingName = 'Инженер (всех категорий)';
            $personIcon = '/img/trainer/engineer.png';
            $seekingFeature = 'РЗА';
        }
        if ($Row[1] === 'Инженер (всех категорий) СИзП') {
            $seekingName = 'Инженер (всех категорий)';
            $personIcon = '/img/trainer/engineer.png';
            $seekingFeature = 'СИЗП';
        }
        if ($Row[1] === 'Заместитель начальника СВЛ') {
            $seekingName = 'Заместитель начальника службы';
            $personIcon = '/img/trainer/deputyСhief.png';
            $seekingFeature = 'СВЛ';
        }
        if ($Row[1] === 'Заместитель начальника СПС') {
            $seekingName = 'Заместитель начальника службы';
            $personIcon = '/img/trainer/deputyСhief.png';
            $seekingFeature = 'СПС';
        }
        if ($Row[1] === 'Заместитель начальника СРЗА') {
            $seekingName = 'Заместитель начальника службы';
            $personIcon = '/img/trainer/deputyСhief.png';
            $seekingFeature = 'РЗА';
        }
        if ($Row[1] === 'Заместитель начальника СИзП') {
            $seekingName = 'Заместитель начальника службы';
            $personIcon = '/img/trainer/deputyСhief.png';
            $seekingFeature = 'СИЗП';
        }
        if ($Row[1] === 'Начальник СВЛ') {
            $seekingName = 'Начальник службы';
            $personIcon = '/img/trainer/chief.png';
            $seekingFeature = 'СВЛ';
        }
        if ($Row[1] === 'Начальник СПС') {
            $seekingName = 'Начальник службы';
            $personIcon = '/img/trainer/chief.png';
            $seekingFeature = 'СПС';
        }
        if ($Row[1] === 'Начальник СРЗА') {
            $seekingName = 'Начальник службы';
            $personIcon = '/img/trainer/chief.png';
            $seekingFeature = 'РЗА';
        }
        if ($Row[1] === 'Начальник СИзП') {
            $seekingName = 'Начальник службы';
            $personIcon = '/img/trainer/chief.png';
            $seekingFeature = 'СИЗП';
        }
        if ($Row[1] === 'Главный инженер ПО') {
            $seekingName = 'Главный инженер ПО';
            $personIcon = '/img/trainer/majorEngineer.png';
            $seekingFeature = 'Главный инженер ПО';
        }
        if ($Row[1] === 'Директор ПО') {
            $seekingName = 'Директор ПО';
            $personIcon = '/img/trainer/principal.png';
            $seekingFeature = 'Директор ПО';
        }
        if ($Row[1] === 'Водитель
(без учета электромонтеров - водителей)') {
            $seekingName = 'Водитель';
            $personIcon = '/img/trainer/driver.png';
            $seekingFeature = 'СМиТ';
        }
        if ($Row[1] === 'Кладовщик' || $Row[1] === 'Кладовщик (зав.складом и техник)') {
            $seekingName = 'Кладовщик';
            $personIcon = '/img/trainer/storekeeper.png';
            $seekingFeature = 'ПО';
        }
        if ($Row[1] === 'Электромонтеры СМУ') {
            $seekingName = 'Электромонтер';
            $personIcon = '/img/trainer/electricMan.png';
            $seekingFeature = 'СМУ';
        }
        if ($Row[1] === 'Мастера СМУ
(в т.ч. старший мастер)') {
            $seekingName = 'Мастер (старший мастер)';
            $personIcon = '/img/trainer/master.png';
            $seekingFeature = 'СМУ';
        }


        ////////////////////////////////
        if ($Row[3] != 0) {//Из ст. 2 с оперативными правами
            $seekingAttr = 'Оперативные права';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr &&
                    $seekingFeature === $chelik['feature']) {
                    $attributes[] = ["count" => $Row[3],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[4] != 0) {//Из ст. 2 с правом ответственного руководителя работ
            $seekingAttr = 'Ответственный руководитель работ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[4],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[5] != 0) {//с правом производителя работ
            $seekingAttr = 'Производитель работ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[5],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FF0000"];
                }
            }
        }
        if ($Row[6] != 0) {//с правом выдачи нарядов
            $seekingAttr = 'С правом выдачи нарядов';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[6],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[7] != 0) {//с правом работы на высоте
            $seekingAttr = 'Работа на высоте';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[7],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        if ($Row[8] != 0) {// МАВБ
            $seekingAttr = 'МАВБ';
            foreach ($staff[$dataName] as $chelik) {
                if (mb_strrpos($seekingName, $chelik['name']) !== false &&
                    $seekingFeature === $chelik['feature'] && $chelik['attribute'] === $seekingAttr) {
                    $attributes[] = ["count" => $Row[8],
                        "attribute" => $seekingAttr,
                        "color" => $chelik['color'],
                        "colorIcon" => "#FFFFFF"];
                }
            }
        }
        //find default color
        $color = 0;
        $feature = 0;
        $seekingAttr = 'Без специальных разрешений';
        foreach ($staff[$dataName] as $chelik) {
            if (mb_strrpos($seekingName, $chelik['name']) !== false && $chelik['attribute'] === $seekingAttr &&
                $seekingFeature === $chelik['feature']) {
                $color = $chelik['color'];
                $feature = $chelik['featureSign'];
                $featureName = $chelik['feature'];
                break;
            }
        }
        //
        if ($color === 0 || !isset($personIcon)) {
            echo 'Ошибка'; //МКМ
            return [];
        }
        $answer = [
            'name' => $seekingName,
            'count' => $Row[2],
            'walk' => true,
            'iconPath' => $personIcon,
            'typeStaff' => 'Po',
            'staff' => [['name' => $featureName, 'count' => $Row[2], "feature" => $feature, 'attributes' => $attributes,
                'default' => ["attribute" => $seekingAttr,
                    "color" => $color,
                    "colorIcon" => "#000000"]]]
        ];
        return $answer;
    }


    private function generateStaffCharacteristics()
    {
        $fileName = 'tmp/personazhi.xls';
        $Reader = new SpreadsheetReader($fileName);
        $Reader->ChangeSheet(1);
        $i = 0;
        $personalPo = [];
        $personalRes = [];
        $techRes = [];
        foreach ($Reader as $Row) {
            if ($i >= 2) {
                $character = [];
                $character['name'] = $Row[2];
                $character['feature'] = $Row[3];
                $character['featureSign'] = $Row[4];
                $character['attribute'] = $Row[5];
                if ($Row[6] === 'Нет') $character['color'] = '#FFFFFF';
                else {
                    $character['color'] = $Row[7];
                }
                $character['walk'] = true;
                $personalPo[] = $character;
            }
            $i++;
        }
        $Reader->ChangeSheet(2);
        $i = 0;
        foreach ($Reader as $Row) {
            if ($i >= 2) {
                $character = [];
                $character['name'] = $Row[1];
                $character['feature'] = $Row[2];
                $character['featureSign'] = $Row[3];
                $character['attribute'] = $Row[4];
                if (($Row[5] === 'см. вкладку персонал ПО' || $Row[5] === "") && ($Row[6] === '#FF0000')) {
                    $seekingName = $character['name'];
                    $seekingAttr = $character['attribute'];
                    if ($seekingAttr === 'РЭС') {
                        $seekingAttr = 'Без специальных разрешений';
                    }
                    if ($seekingName === 'Главный инженер РЭС') {
                        $seekingName = 'Инженер (всех категорий)';
                        if ($seekingAttr === 'С правом выдачи путевых листов') {
                            $seekingAttr = 'С правом выдачи нарядов';
                        }
                    }
                    if ($seekingName === 'Начальник РЭС') {
                        $seekingName = 'Начальник службы';
                        if ($seekingAttr === 'С правом выдачи путевых листов') {
                            $seekingAttr = 'С правом выдачи нарядов';
                        }
                    }
                    foreach ($personalPo as $poMan) {
                        if ($seekingName === $poMan['name'] && $seekingAttr === $poMan['attribute']) {
                            $character['color'] = $poMan['color'];
                            break;
                        }
                    }
                    if (!isset($character['color'])) {
                        echo '1';
                    }
                } else {
                    if ($Row[6] === '#FF0000' && $Row[5] === 'Нет') {
                        $character['color'] = '#FFFFFF';
                    } else {
                        $character['color'] = $Row[6];
                    }
                }
                $character['walk'] = true;
                $personalRes[] = $character;
            }
            $i++;
        }
        $Reader->ChangeSheet(3);
        $i = 0;
        foreach ($Reader as $Row) {
            if ($i >= 3) {
                $character = [];
                $character['name'] = $Row[2];
                $character['feature'] = $Row[3];
                if ($Row[4] === 'См. ранее' || $Row[4] === '') {
                    $seekingValue = $Row[3];
                    if ($Row[3] === "Распредсети") {
                        $seekingValue = "Распредсеть";
                    }
                    foreach ($personalRes as $resMan) {
                        if ($resMan['feature'] === $seekingValue) {
                            $character['featureSign'] = $resMan['featureSign'];
                            break;
                        }
                    }
                    foreach ($personalPo as $poMan) {
                        if ($poMan['feature'] === $seekingValue) {
                            $character['featureSign'] = $poMan['featureSign'];
                            break;
                        }
                    }
                } else {
                    $character['featureSign'] = $Row[4];
                }
                $character['attribute'] = $Row[5];
                $character['color'] = $Row[7];
                $character['walk'] = false;
                $techRes[] = $character;
            }
            $i++;
        }
        return ['personalPo' => $personalPo, 'personalRes' => $personalRes, 'techRes' => $techRes];
    }


    public function createCustomStaffAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $response = [];
        $Reader = new SpreadsheetReader('tmp/hakaziya/XE.xls');
        $i = 0;
        foreach ($Reader->Sheets() as $key => $value) {
            if ($value === 'Таблица персонал РЭС') {
                $Reader->ChangeSheet($key);
            }
        }
        $firstIndex = 3;
        $numberOfStrings = 8;
        $currentFilial = 'Хакасэнерго';
        $filialId = '19';
        $personalPo = [];
        $personalRes = [];
        $currentRes = '';
        $currentResId = '';
        $currentPo = '';
        $tempPoPersonal = [];
        $tempResPersonal = [];
        $currentPoId = '';
        foreach ($Reader as $Row) {
            $resChangeFlag = false;
            $item = [];
            if ($i >= $firstIndex) {
                if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings + 1) == 0) {
                    if ($tempResPersonal !== []) {
                        $personalRes = array_merge($personalRes, $tempResPersonal);
                        $tempResPersonal = [];
                    }
                    $currentResId = "";
                    foreach ($reses as $res) {
                        if (mb_strrpos(mb_strtoupper($res['Label']), mb_strtoupper(trim($Row[1]))) === 0
                            && mb_strrpos($res['branch'], $filialId) !== false) {
                            $currentResId = $res['RES_id'];
                            $currentRes = $res['Label'];
                            $currentPoId = $res['branch'];
                            foreach ($pos as $po) {
                                if ($po['composite_id'] === $currentPoId) {
                                    $currentPo = $po['name'];
                                }
                            }
                            break;
                        }
                    }
                    if ($currentResId == "") {
                        echo 'Не найден РЭС';
                    }
                } else {
                    $item = $this->createResStaff($staff, $Row, 'personalRes');
                    if ($item !== []) {
                        if ($item['iconPath'][0] !== '/') {
                            echo 'no icon';
                        }
                        $item['resId'] = $currentResId;
                        $notSetFlag = true;
                        $setIndex = -1;
                        for ($j = 0; $j < count($tempResPersonal); $j++) {
                            if ($tempResPersonal[$j]['name'] === $item['name']) {
                                $notSetFlag = false;
                                $setIndex = $j;
                                break;
                            }
                        }
                        if ($notSetFlag === true) {
                            $tempResPersonal[] = $item;
                        } else {
                            $tempResPersonal[$setIndex]['staff'][] = $item['staff'][0];
                            $tempResPersonal[$setIndex]['count'] += $item['count'];
                        }
                    }
                }
            }
            $i++;
        }
        $personalRes = array_merge($personalRes, $tempResPersonal);
        $response = array_merge($response, $personalRes);
        $str = json_encode($response);
        echo '1';
    }

    public function createCustomTechAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $response = [];
        $Reader = new SpreadsheetReader('tmp/hakaziya/XE.xls');
        $i = 0;
        foreach ($Reader->Sheets() as $key => $value) {
            if ($value === 'Техника РЭС') {
                $Reader->ChangeSheet($key);
            }
        }
        $firstIndex = 2;
        $numberOfStrings = 11;
        $currentFilial = 'Хакасэнерго';
        $filialId = '19';
        $personalPo = [];
        $personalRes = [];
        $currentRes = '';
        $currentResId = '';
        $currentPo = '';
        $tempPoPersonal = [];
        $tempResPersonal = [];
        $currentPoId = '';
        foreach ($Reader as $Row) {
            $resChangeFlag = false;
            $item = [];
            if ($i >= $firstIndex) {
                if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings + 1) == 0) {
                    if ($tempResPersonal !== []) {
                        $personalRes = array_merge($personalRes, $tempResPersonal);
                        $tempResPersonal = [];
                    }
                    $currentResId = "";
                    foreach ($reses as $res) {
                        if (mb_strrpos(mb_strtoupper($res['Label']), mb_strtoupper(trim($Row[2]))) === 0
                            && mb_strrpos($res['branch'], $filialId) !== false) {
                            $currentResId = $res['RES_id'];
                            $currentRes = $res['Label'];
                            $currentPoId = $res['branch'];
                            foreach ($pos as $po) {
                                if ($po['composite_id'] === $currentPoId) {
                                    $currentPo = $po['name'];
                                }
                            }
                            break;
                        }
                    }
                    if ($currentResId == "") {
                        echo 'Не найден РЭС';
                    }
                } else {
                    $item = $this->createResTech($staff, $Row, 'techRes');
                    if ($item !== []) {
                        if ($item['iconPath'][0] !== '/') {
                            echo 'no icon';
                        }
                        $item['resId'] = $currentResId;
                        $notSetFlag = true;
                        $setIndex = -1;
                        for ($j = 0; $j < count($tempResPersonal); $j++) {
                            if ($tempResPersonal[$j]['name'] === $item['name']) {
                                $notSetFlag = false;
                                $setIndex = $j;
                                break;
                            }
                        }
                        if ($notSetFlag === true) {
                            $tempResPersonal[] = $item;
                        } else {
                            $tempResPersonal[$setIndex]['staff'][] = $item['staff'][0];
                            $tempResPersonal[$setIndex]['count'] += $item['count'];
                        }
                    }
                }
            }
            $i++;
        }
        $personalRes = array_merge($personalRes, $tempResPersonal);
        $response = array_merge($response, $personalRes);
        $str = json_encode($response);
        echo '1';
    }


    public function createCustomTechPoAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $response = [];
        $Reader = new SpreadsheetReader('tmp/gaes/GAES.xls');
        $i = 0;
        foreach ($Reader->Sheets() as $key => $value) {
            if ($value === 'Техника ПО') {
                $Reader->ChangeSheet($key);
            }
        }
        $firstIndex = 3;
        $numberOfStrings = 12;
        $currentFilial = 'Горно-Алтайские электрические сети';
        $filialId = '04';
        $personalPo = [];
        $personalRes = [];
        $currentRes = '';
        $currentResId = '';
        $currentPo = '';
        $tempPoPersonal = [];
        $tempResPersonal = [];
        $currentPoId = '';
        foreach ($Reader as $Row) {
            $resChangeFlag = false;
            $item = [];
            if ($i >= $firstIndex) {
                if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings) == 0) {
                    if ($tempPoPersonal !== []) {
                        $personalPo = array_merge($personalPo, $tempPoPersonal);
                        $tempPoPersonal = [];
                    }
                    $currentPoId = "";
                    if (mb_strrpos($Row[1], '(') !== false) {
                        $index = mb_strrpos($Row[1], '(');
                        $Row[1] = trim(mb_substr($Row[1], 0, $index - 1));
                    }
                    //parse names
                    $esIndex = mb_strrpos($Row[1], 'ЭС');
                    $startIndex = mb_strrpos($Row[1], 'ПО');
                    $realNameShort = mb_substr($Row[1], $startIndex + 3, $esIndex - $startIndex - 3);
                    if (mb_strlen($realNameShort) === 1) {
                        $regularStr = "ПО " . $realNameShort . "[а-я]+ ЭС";
                    } else if (mb_strlen($realNameShort) === 2) {
                        $regularStr = "ПО " . mb_substr($realNameShort, 0, 1) .
                            "[а-я]+-" . mb_substr($realNameShort, 1, 1) . "[а-я]+ ЭС";
                    } else {
                        echo 'Длинное имя';
                    }

                    //
                    foreach ($pos as $po) {
                        if (mb_ereg_match($regularStr, $po['name'], 'i') !== false
                            && mb_strrpos($po['branch'], $filialId) !== false) {
                            $currentPoId = $po['composite_id'];
                            $currentPo = $po['name'];
                            break;
                        }
                    }
                    if ($currentPoId == "") {
                        echo 'Не найден ПО';
                    }
                } else {
                    $item = $this->createPoTech($staff, $Row, 'techRes');
                    if ($item !== []) {
                        if ($item['iconPath'][0] !== '/') {
                            echo 'no icon';
                        }
                        $item['poId'] = $currentPoId;
                        $notSetFlag = true;
                        $setIndex = -1;
                        for ($j = 0; $j < count($tempPoPersonal); $j++) {
                            if ($tempPoPersonal[$j]['name'] === $item['name']) {
                                $notSetFlag = false;
                                $setIndex = $j;
                                break;
                            }
                        }
                        if ($notSetFlag === true) {
                            $tempPoPersonal[] = $item;
                        } else {
                            $tempPoPersonal[$setIndex]['staff'][] = $item['staff'][0];
                        }
                    }
                }
            }
            $i++;
        }
        $personalPo = array_merge($personalPo, $tempPoPersonal);
        $response = array_merge($response, $personalPo);
        $str = json_encode($response);
        echo '1';
    }


    public function createCustomStaffPoAction()
    {
        $staff = $this->generateStaffCharacteristics();
        $filials = $this->getFilials();
        $reses = $this->getRes();
        $pos = $this->getPo();
        $response = [];
        $Reader = new SpreadsheetReader('tmp/gaes/GAES.xls');
        $i = 0;
        foreach ($Reader->Sheets() as $key => $value) {
            if ($value === 'Таблица персонал ПО') {
                $Reader->ChangeSheet($key);
            }
        }
        $firstIndex = 3;
        $numberOfStrings = 25;
        $currentFilial = 'Горно-Алтайские электрические сети';
        $filialId = '04';
        $personalPo = [];
        $personalRes = [];
        $currentRes = '';
        $currentResId = '';
        $currentPo = '';
        $tempPoPersonal = [];
        $tempResPersonal = [];
        $currentPoId = '';
        foreach ($Reader as $Row) {
            $resChangeFlag = false;
            $item = [];
            if ($i >= $firstIndex) {
                if (($Reader->key() - $firstIndex - 1) % ($numberOfStrings) == 0) {
                    if ($tempPoPersonal !== []) {
                        $personalPo = array_merge($personalPo, $tempPoPersonal);
                        $tempPoPersonal = [];
                    }
                    $currentPoId = "";
                    if (mb_strrpos($Row[1], '(') !== false) {
                        $index = mb_strrpos($Row[1], '(');
                        $Row[1] = trim(mb_substr($Row[1], 0, $index - 1));
                    }
                    //parse names
                    $esIndex = mb_strrpos($Row[1], 'ЭС');
                    $startIndex = mb_strrpos($Row[1], 'ПО');
                    $realNameShort = mb_substr($Row[1], $startIndex + 3, $esIndex - $startIndex - 3);
                    if (mb_strlen($realNameShort) === 1) {
                        $regularStr = "ПО " . $realNameShort . "[а-я]+ ЭС";
                    } else if (mb_strlen($realNameShort) === 2) {
                        $regularStr = "ПО " . mb_substr($realNameShort, 0, 1) .
                            "[а-я]+-" . mb_substr($realNameShort, 1, 1) . "[а-я]+ ЭС";
                    } else {
                        echo 'Длинное имя';
                    }

                    //
                    foreach ($pos as $po) {
                        if (mb_ereg_match($regularStr, $po['name'], 'i') !== false
                            && mb_strrpos($po['branch'], $filialId) !== false) {
                            $currentPoId = $po['composite_id'];
                            $currentPo = $po['name'];
                            break;
                        }
                    }
                    if ($currentPoId == "") {
                        echo 'Не найден ПО';
                    }
                } else {
                    $item = $this->createPoStaff($staff, $Row, 'personalPo');
                    if ($item !== []) {
                        if ($item['iconPath'][0] !== '/') {
                            echo 'no icon';
                        }
                        $item['poId'] = $currentPoId;
                        $notSetFlag = true;
                        $setIndex = -1;
                        for ($j = 0; $j < count($tempPoPersonal); $j++) {
                            if ($tempPoPersonal[$j]['name'] === $item['name']) {
                                $notSetFlag = false;
                                $setIndex = $j;
                                break;
                            }
                        }
                        if ($notSetFlag === true) {
                            $tempPoPersonal[] = $item;
                        } else {
                            $tempPoPersonal[$setIndex]['staff'][] = $item['staff'][0];
                        }
                    }
                }
            }
            $i++;
        }
        $personalPo = array_merge($personalPo, $tempPoPersonal);
        $response = array_merge($response, $personalPo);
        $str = json_encode($response);
        echo '1';
    }


}