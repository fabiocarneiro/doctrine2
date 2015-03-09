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
}
