<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class AttributeOverrideEvaluator implements EvaluatorInterface
{
    use ColumnToArrayTrait;

    /**
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if (! isset($element['attributeOverride']) || ! is_array($element['attributeOverride'])) {
            return;
        }

        foreach ($element['attributeOverride'] as $fieldName => $attributeOverrideElement) {
            $mapping = $this->columnToArray($fieldName, $attributeOverrideElement);
            $metadata->setAttributeOverride($fieldName, $mapping);
        }
    }
}
