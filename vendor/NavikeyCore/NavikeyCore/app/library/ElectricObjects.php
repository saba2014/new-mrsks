<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

/**
 * Абстрактный класс электрических объектов
 * @author alex
 */
abstract class ElectricObjects {

    abstract public function load(\SimpleXMLElement $xmllines, \Ds\Map &$points);
}
