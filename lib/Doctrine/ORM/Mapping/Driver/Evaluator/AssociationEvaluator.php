<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class AssociationEvaluator implements EvaluatorInterface
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

        if (! isset($element['id'])) {
            return;
        }

        $associationIds = [];
        // Evaluate identifier settings
        foreach ($element['id'] as $idElement) {
            if (isset($idElement['associationKey']) && $idElement['associationKey'] == true) {
                $associationIds[$idElement['fieldName']] = true;
                continue;
            }

            $mapping = [
                'id' => true,
                'fieldName' => $idElement['fieldName']
            ];

            if (isset($idElement['type'])) {
                $mapping['type'] = $idElement['type'];
            }

            if (isset($idElement['column'])) {
                $mapping['columnName'] = $idElement['column'];
            }

            if (isset($idElement['length'])) {
                $mapping['length'] = $idElement['length'];
            }

            if (isset($idElement['columnDefinition'])) {
                $mapping['columnDefinition'] =
                    $idElement['columnDefinition'];
            }

            if (isset($idElement['options'])) {
                $mapping['options'] = $idElement['options'];
            }

            $metadata->mapField($mapping);

            if (isset($idElement['generator'])) {
                $metadata->setIdGeneratorType(
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_'
                        . strtoupper($idElement['generator']['strategy'])
                    )
                );
            }

            // Check for SequenceGenerator/TableGenerator definition
            if (isset($idElement['sequenceGenerator'])) {
                $metadata->setSequenceGeneratorDefinition(
                    $idElement['sequenceGenerator']
                );
                continue;
            }

            if (isset($idElement['customIdGenerator'])) {
                $customGenerator = $idElement['customIdGenerator'];
                $metadata->setCustomGeneratorDefinition(
                    [
                        'class' => (string)$customGenerator['class']
                    ]
                );
                continue;
            }

            if (isset($idElement['tableGenerator'])) {
                throw MappingException::tableIdGeneratorNotImplemented(
                    $className
                );
            }
        }
    }
}