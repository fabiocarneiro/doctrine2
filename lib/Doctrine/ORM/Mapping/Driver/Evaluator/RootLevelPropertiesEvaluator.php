<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class RootLevelPropertiesEvaluator implements EvaluatorInterface
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

        $primaryTable = [];

        if (isset($element['table'])) {
            $primaryTable['name'] = $element['table'];
        }

        if (isset($element['schema'])) {
            $primaryTable['schema'] = $element['schema'];
        }

        $metadata->setPrimaryTable($primaryTable);
    }
}
