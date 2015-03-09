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
    use JoinColumnToArrayTrait;
    use CacheToArrayTrait;

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

        if (! isset($element['manyToMany'])) {
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
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $manyToManyElement['fetch']
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
                        foreach ($joinTableElement['joinColumns'] as $joinColumnName => $joinColumnElement) {
                            if (! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] = $joinColumnName;
                            }

                            $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumnElement);
                        }
                    }

                    if (isset($joinTableElement['inverseJoinColumns'])) {
                        foreach ($joinTableElement['inverseJoinColumns'] as $joinColumnName => $joinColumnElement) {
                            if (! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] = $joinColumnName;
                            }

                            $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumnElement);
                        }
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
}
