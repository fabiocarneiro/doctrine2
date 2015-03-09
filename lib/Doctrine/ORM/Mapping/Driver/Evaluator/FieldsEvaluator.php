<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use InvalidArgumentException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\Mapping\MappingException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class FieldsEvaluator implements EvaluatorInterface
{
    use ColumnToArrayTrait;

    /**
     * {@inheritDoc}
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @throws MappingException
     * @throws InvalidArgumentException
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if (! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if (! isset($element['fields'])) {
            return;
        }

        foreach ($element['fields'] as $name => $fieldMapping) {
            $mapping = $this->columnToArray($name, $fieldMapping);

            if (isset($fieldMapping['id'])) {
                $mapping['id'] = true;
                if (isset($fieldMapping['generator']['strategy'])) {
                    $metadata->setIdGeneratorType(
                        constant(
                            'Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_'
                            . strtoupper(
                                $fieldMapping['generator']['strategy']
                            )
                        )
                    );
                }
            }

            if (isset($mapping['version'])) {
                $metadata->setVersionMapping($mapping);
                unset($mapping['version']);
            }

            $metadata->mapField($mapping);
        }
    }
}
