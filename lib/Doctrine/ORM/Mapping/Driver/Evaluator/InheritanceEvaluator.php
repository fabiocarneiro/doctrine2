<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class InheritanceEvaluator implements EvaluatorInterface
{
    /**
     * {@inheritDoc}
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @throws InvalidArgumentException
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if ( ! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if ( ! isset($element['inheritanceType'])) {
            return;
        }

        $metadata->setInheritanceType(
            constant(
                'Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($element['inheritanceType'])
            )
        );

        if ($metadata->inheritanceType != \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
            // Evaluate discriminatorColumn
            if (isset($element['discriminatorColumn'])) {
                $discrColumn = $element['discriminatorColumn'];
                $metadata->setDiscriminatorColumn(
                    [
                        'name' => isset($discrColumn['name'])
                            ? (string)$discrColumn['name'] : null,
                        'type' => isset($discrColumn['type'])
                            ? (string)$discrColumn['type'] : null,
                        'length' => isset($discrColumn['length'])
                            ? (string)$discrColumn['length'] : null,
                        'columnDefinition' => isset($discrColumn['columnDefinition'])
                            ? (string)$discrColumn['columnDefinition']
                            : null
                    ]
                );
            } else {
                $metadata->setDiscriminatorColumn(
                    [
                        'name' => 'dtype',
                        'type' => 'string',
                        'length' => 255
                    ]
                );
            }
            // Evaluate discriminatorMap
            if (isset($element['discriminatorMap'])) {
                $metadata->setDiscriminatorMap(
                    $element['discriminatorMap']
                );
            }
        }
    }
}