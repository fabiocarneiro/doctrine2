<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class OneToManyEvaluator implements EvaluatorInterface
{
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

        if (! isset($element['oneToMany'])) {
            return;
        }

        foreach ($element['oneToMany'] as $oneToManyElement) {
            $mapping = [
                'fieldName' => $oneToManyElement['fieldName'],
                'targetEntity' => $oneToManyElement['targetEntity'],
                'mappedBy' => $oneToManyElement['mappedBy']
            ];

            if (isset($oneToManyElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $oneToManyElement['fetch']
                    );
            }

            if (isset($oneToManyElement['cascade'])) {
                $mapping['cascade'] = $oneToManyElement['cascade'];
            }

            if (isset($oneToManyElement['orphanRemoval'])) {
                $mapping['orphanRemoval'] = (bool)$oneToManyElement['orphanRemoval'];
            }

            if (isset($oneToManyElement['orderBy'])) {
                $mapping['orderBy'] = $oneToManyElement['orderBy'];
            }

            if (isset($oneToManyElement['indexBy'])) {
                $mapping['indexBy'] = $oneToManyElement['indexBy'];
            }

            $metadata->mapOneToMany($mapping);
            // Evaluate second level cache
            if (isset($oneToManyElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($oneToManyElement['cache'])
                );
            }
        }
    }
}
