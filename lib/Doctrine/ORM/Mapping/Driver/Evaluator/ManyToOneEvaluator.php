<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class ManyToOneEvaluator implements EvaluatorInterface
{
    /**
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if ( ! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if ( ! isset($element['manyToOne'])) {
            return;
        }

        foreach ($element['manyToOne'] as $manyToOneElement) {
            $mapping = [
                'fieldName' => $manyToOneElement['fieldName'],
                'targetEntity' => $manyToOneElement['targetEntity']
            ];

            if (isset($associationIds[$mapping['fieldName']])) {
                $mapping['id'] = true;
            }

            if (isset($manyToOneElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $manyToOneElement['fetch']
                    );
            }

            if (isset($manyToOneElement['inversedBy'])) {
                $mapping['inversedBy'] = $manyToOneElement['inversedBy'];
            }

            $joinColumns = [];

            if (isset($manyToOneElement['joinColumn'])) {
                $joinColumns[] = $this->joinColumnToArray(
                    $manyToOneElement['joinColumn']
                );
            } else {
                if (isset($manyToOneElement['joinColumns'])) {
                    foreach ($manyToOneElement['joinColumns'] as
                             $joinColumnName => $joinColumnElement) {
                        if ( ! isset($joinColumnElement['name'])) {
                            $joinColumnElement['name'] = $joinColumnName;
                        }
                        $joinColumns[] =
                            $this->joinColumnToArray($joinColumnElement);
                    }
                }
            }

            $mapping['joinColumns'] = $joinColumns;

            if (isset($manyToOneElement['cascade'])) {
                $mapping['cascade'] = $manyToOneElement['cascade'];
            }

            $metadata->mapManyToOne($mapping);

            // Evaluate second level cache
            if (isset($manyToOneElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($manyToOneElement['cache'])
                );
            }
        }
    }

    /**
     * Constructs a joinColumn mapping array based on the information
     * found in the given join column element.
     *
     * @param array $joinColumnElement The array join column element.
     *
     * @return array The mapping array.
     */
    private function joinColumnToArray(
        array $joinColumnElement
    ) {
        $joinColumn = [];

        if (isset($joinColumnElement['referencedColumnName'])) {
            $joinColumn['referencedColumnName'] =
                (string)$joinColumnElement['referencedColumnName'];
        }

        if (isset($joinColumnElement['name'])) {
            $joinColumn['name'] = (string)$joinColumnElement['name'];
        }

        if (isset($joinColumnElement['fieldName'])) {
            $joinColumn['fieldName'] = (string)$joinColumnElement['fieldName'];
        }

        if (isset($joinColumnElement['unique'])) {
            $joinColumn['unique'] = (bool)$joinColumnElement['unique'];
        }

        if (isset($joinColumnElement['nullable'])) {
            $joinColumn['nullable'] = (bool)$joinColumnElement['nullable'];
        }

        if (isset($joinColumnElement['onDelete'])) {
            $joinColumn['onDelete'] = $joinColumnElement['onDelete'];
        }

        if (isset($joinColumnElement['columnDefinition'])) {
            $joinColumn['columnDefinition'] =
                $joinColumnElement['columnDefinition'];
        }

        return $joinColumn;
    }

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
            $usage = (string)$cacheMapping['usage'];
        }

        if ($usage
            && ! defined(
                'Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_'
                . $usage
            )
        ) {
            throw new \InvalidArgumentException(
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