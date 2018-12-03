<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Cluster;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Math\Distance;

// Дистанция вычесляется по формуле "гуверсинуса"

class GeoMetric implements Distance
{
    private const earthR = 6371;
    /**
     * @throws InvalidArgumentException
     */
    public function distance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw InvalidArgumentException::arraySizeNotMatch();
        }
        $lat1 = deg2rad($a[1]);
        $lon1 = deg2rad($a[0]);
        $lat2 = deg2rad($b[1]);
        $lon2 = deg2rad($b[0]);

        $sin1 = sin(($lat1-$lat2)/2)**2;
        $sin2 = sin(($lon1-$lon2)/2)**2*cos($lat1)*cos($lat2);
        $asin = asin(sqrt($sin1+$sin2));

        $distance = 2*GeoMetric::earthR*$asin;
        return $distance;
    }
}
