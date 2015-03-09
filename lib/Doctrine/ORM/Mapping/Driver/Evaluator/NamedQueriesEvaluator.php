<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class NamedQueriesEvaluator implements EvaluatorInterface
{
    /**
     * {@inheritDoc}
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if (! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if (! isset($element['namedQueries'])) {
            return;
        }

        foreach ($element['namedQueries'] as $name => $queryMapping) {
            if (is_string($queryMapping)) {
                $queryMapping = ['query' => $queryMapping];
            }

            if (! isset($queryMapping['name'])) {
                $queryMapping['name'] = $name;
            }

            $metadata->addNamedQuery($queryMapping);
        }
    }
}
