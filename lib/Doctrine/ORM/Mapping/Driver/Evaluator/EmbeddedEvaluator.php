<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class EmbeddedEvaluator implements EvaluatorInterface
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

        if ( ! isset($element['embedded'])) {
            return;
        }

        foreach ($element['embedded'] as $name => $embeddedMapping) {
            $mapping = [
                'fieldName' => $name,
                'class' => $embeddedMapping['class'],
                'columnPrefix' => isset($embeddedMapping['columnPrefix'])
                    ? $embeddedMapping['columnPrefix'] : null,
            ];

            $metadata->mapEmbedded($mapping);
        }
    }
}