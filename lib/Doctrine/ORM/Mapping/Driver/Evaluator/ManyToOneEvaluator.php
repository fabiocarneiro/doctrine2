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

        if (! isset($element['manyToOne'])) {
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
                        if (! isset($joinColumnElement['name'])) {
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
}
