<?php
declare(strict_types=1);

use Phalcon\Cli\Task;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use MongoDB\Driver\Manager;

class ExcelTask extends Task
{
    public function mainAction()
    {
        echo "This is the default task and the default action" . PHP_EOL;
    }


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

    private function getRes()
    {
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, 'Res');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $collection->find([], $options)->toArray();
        $response = [];
        foreach ($result as $res) {
            $response[] = $res['properties'];
        }
        return $response;
    }

    private function getPo()
    {
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, 'Po');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $collection->find([], $options)->toArray();
        $response = [];
        foreach ($result as $res) {
            $response[] = $res['properties'];
        }
        return $response;
    }

    private function getFilials()
    {
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, 'Filiations');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $collection->find([], $options)->toArray();
        $response = [];
        foreach ($result as $res) {
            $response[] = $res['properties'];
        }
        return $response;
    }

    public function changeTPAction()
    {
        $Reader = new SpreadsheetReader('temp/changeTp.xlsx');
        $Reader->ChangeSheet(2);
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, 'Ps');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];

        foreach ($Reader as $Row) {
            if (is_numeric($Row[0])) {
                $collection->updateOne(["properties.d_name" => "Дагестан {$Row[0]}"],
                    ['$set' => ["properties.addr" => $Row[3], "properties.brigade" => $Row[9], "properties.name0" => $Row[10], "properties.name1" => $Row[11],
                        "properties.name2" => $Row[12], "properties.d_name" => $Row[1]]], ["upsert" => true]);
            }
        }
    }

}