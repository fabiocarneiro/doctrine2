<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class ManyToManyEvaluator implements EvaluatorInterface
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

        if ( ! isset($element['manyToMany'])) {
            return;
        }

        foreach ($element['manyToMany'] as $manyToManyElement) {
            $mapping = [
                'fieldName' => $manyToManyElement['fieldName'],
                'targetEntity' => $manyToManyElement['targetEntity']
            ];

            if (isset($manyToManyElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $manyToManyElement['fetch']
                    );
            }

            if (isset($manyToManyElement['mappedBy'])) {
                $mapping['mappedBy'] = $manyToManyElement['mappedBy'];
            } else {
                if (isset($manyToManyElement['joinTable'])) {
                    $joinTableElement = $manyToManyElement['joinTable'];
                    $joinTable        = [
                        'name' => $joinTableElement['name']
                    ];
                    if (isset($joinTableElement['schema'])) {
                        $joinTable['schema'] = $joinTableElement['schema'];
                    }
                    if (isset($joinTableElement['joinColumns'])) {
                        foreach ($joinTableElement['joinColumns'] as
                                 $joinColumnName => $joinColumnElement) {
                            if ( ! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] =
                                    $joinColumnName;
                            }
                        }
                        $joinTable['joinColumns'][] =
                            $this->joinColumnToArray($joinColumnElement);
                    }
                    if (isset($joinTableElement['inverseJoinColumns'])) {
                        foreach ($joinTableElement['inverseJoinColumns'] as
                                 $joinColumnName => $joinColumnElement) {
                            if ( ! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] =
                                    $joinColumnName;
                            }
                        }
                        $joinTable['inverseJoinColumns'][] =
                            $this->joinColumnToArray($joinColumnElement);
                    }
                    $mapping['joinTable'] = $joinTable;
                }
            }

            if (isset($manyToManyElement['inversedBy'])) {
                $mapping['inversedBy'] = $manyToManyElement['inversedBy'];
            }

            if (isset($manyToManyElement['cascade'])) {
                $mapping['cascade'] = $manyToManyElement['cascade'];
            }

            if (isset($manyToManyElement['orderBy'])) {
                $mapping['orderBy'] = $manyToManyElement['orderBy'];
            }

            if (isset($manyToManyElement['indexBy'])) {
                $mapping['indexBy'] = $manyToManyElement['indexBy'];
            }

            if (isset($manyToManyElement['orphanRemoval'])) {
                $mapping['orphanRemoval'] =
                    (bool)$manyToManyElement['orphanRemoval'];
            }

            $metadata->mapManyToMany($mapping);
            // Evaluate second level cache
            if (isset($manyToManyElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($manyToManyElement['cache'])
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