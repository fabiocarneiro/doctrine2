<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class OneToOneEvaluator implements EvaluatorInterface
{
    /**
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if (! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if (! isset($element['oneToOne'])) {
            return;
        }

        foreach ($element['oneToOne'] as $oneToOneElement) {
            $mapping = [
                'fieldName' => $oneToOneElement['fieldName'],
                'targetEntity' => $oneToOneElement['targetEntity']
            ];

            if (isset($associationIds[$mapping['fieldName']])) {
                $mapping['id'] = true;
            }

            if (isset($oneToOneElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $oneToOneElement['fetch']
                    );
            }

            if (isset($oneToOneElement['mappedBy'])) {
                $mapping['mappedBy'] = $oneToOneElement['mappedBy'];
            } else {
                if (isset($oneToOneElement['inversedBy'])) {
                    $mapping['inversedBy'] = $oneToOneElement['inversedBy'];
                }
                $joinColumns = [];
                if (isset($oneToOneElement['joinColumn'])) {
                    $joinColumns[] = $this->joinColumnToArray(
                        $oneToOneElement['joinColumn']
                    );
                } else {
                    if (isset($oneToOneElement['joinColumns'])) {
                        foreach ($oneToOneElement['joinColumns'] as $joinColumnName => $joinColumnElement) {
                            if (! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] = $joinColumnName;
                            }

                            $joinColumns[] = $this->joinColumnToArray(
                                $joinColumnElement
                            );
                        }
                    }
                }
                $mapping['joinColumns'] = $joinColumns;
            }

            if (isset($oneToOneElement['cascade'])) {
                $mapping['cascade'] = $oneToOneElement['cascade'];
            }

            if (isset($oneToOneElement['orphanRemoval'])) {
                $mapping['orphanRemoval'] =
                    (bool)$oneToOneElement['orphanRemoval'];
            }

            $metadata->mapOneToOne($mapping);
            // Evaluate second level cache
            if (isset($oneToOneElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($oneToOneElement['cache'])
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

        if ($usage && ! defined(
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
