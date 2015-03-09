<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
trait CacheToArrayTrait
{
    /**
     * Parse / Normalize the cache configuration
     *
     * @param array $cacheMapping
     *
     * @return array
     */
    private function cacheToArray(
        $cacheMapping
    ) {
        $region = null;
        $usage  = null;

        if (isset($cacheMapping['region'])) {
            $region = (string)$cacheMapping['region'];
        }

        if (isset($cacheMapping['usage'])) {
            $usage = strtoupper($cacheMapping['usage']);
        }

        if ($usage && ! defined('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $usage)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid cache usage "%s"',
                    $usage
                )
            );
        }

        if ($usage) {
            $usage = constant(
                'Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_'
                . $usage
            );
        }

        return [
            'usage' => $usage,
            'region' => $region,
        ];
    }
}
