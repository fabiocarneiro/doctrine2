<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class NamedNativeQueriesEvaluator implements EvaluatorInterface
{
    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if ( ! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if ( ! isset($element['namedNativeQueries'])) {
            return;
        }

        foreach ($element['namedNativeQueries'] as $name => $mappingElement) {
            if ( ! isset($mappingElement['name'])) {
                $mappingElement['name'] = $name;
            }

            $query            = null;
            $resultClass      = null;
            $resultSetMapping = null;

            if (isset($mappingElement['query'])) {
                $query = $mappingElement['query'];
            }

            if (isset($mappingElement['resultClass'])) {
                $resultClass = $mappingElement['resultClass'];
            }

            if (isset($mappingElement['resultSetMapping'])) {
                $resultSetMapping = $mappingElement['resultSetMapping'];
            }

            $metadata->addNamedNativeQuery(
                [
                    'name' => $mappingElement['name'],
                    'query' => $query,
                    'resultClass' => $resultClass,
                    'resultSetMapping' => $resultSetMapping,
                ]
            );
        }
    }
}