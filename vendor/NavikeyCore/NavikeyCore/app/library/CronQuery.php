<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

class CronQuery {

    /**
     * @$xml xml запрос
     * @$date нужная дата
     * return bool возвращает совпадают ли даты
     * Проверка xml запроса с временем date
     */
    public function dateCheack(\SimpleXMLElement &$xml, array $date): bool {
        $time_come = true;
        $minute = $this->getInfo((string) $xml->attributes()["minute"]);
        $hour = $this->getInfo((string) $xml->attributes()["hour"]);
        $day = $this->getInfo((string) $xml->attributes()["day"]);
        $month = $this->getInfo((string) $xml->attributes()["month"]);
        $year = $this->getInfo((string) $xml->attributes()["year"]);

        $this->chek($minute, $date["minutes"], $time_come);
        $this->chek($hour, $date["hours"], $time_come);
        $this->chek($day, $date["mday"], $time_come);
        $this->chek($month, $date["mon"], $time_come);
        $this->chek($year, $date["year"], $time_come);
        return $time_come;
    }

    /**
     * @$value значение в формате крона
     * return int значение в integer >=0 выполнять точно в это время <0 выполнять каждые $temp
     * Функция получает конвертирует значение крона "*" в +value и -value если нужно выполнять переодично 
     */
    private function getInfo(string $value): int {
        if (!isset($value)) {
            return -1;
        }
        $temp = 1;
        $evrey = mb_strimwidth($value, 0, 1);
        if ($evrey === '*') {
            $temp *= -1;
            if (strlen($value) > 1) {
                $temp *= mb_strimwidth($value, 2, strlen($value) - 2);
            }
        } else {
            $temp *= (integer) $value;
        }
        return $temp;
    }

    /**
     * @$xm время xml
     * @$m время с которым сравнивают
     * @&$t флаг
     * Проверка $xm времени с временем $m, изменяет флаг $t на false если неподходит
     */
    private function chek($xm, $m, bool &$t): void {
        if (!((($xm < 0) && !($m % -$xm)) || (($xm >= 0) && ($xm === $m)))) {
            $t = false;
        }
    }

}
