<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class NamedQueryEvaluator implements EvaluatorInterface
{
    /**
     * {@inheritDoc}
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if ( ! isset($element['namedQueries'])) {
            return;
        }

        foreach ($element['namedQueries'] as $name => $queryMapping) {
            if (is_string($queryMapping)) {
                $queryMapping = ['query' => $queryMapping];
            }
            if ( ! isset($queryMapping['name'])) {
                $queryMapping['name'] = $name;
            }
            $metadata->addNamedQuery($queryMapping);
        }
    }
}