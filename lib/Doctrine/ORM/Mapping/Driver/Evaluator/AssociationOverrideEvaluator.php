<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class AssociationOverrideEvaluator implements EvaluatorInterface
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

        if (! isset($element['associationOverride'])
            || ! is_array($element['associationOverride'])
        ) {
            return;
        }

        foreach ($element['associationOverride'] as $fieldName => $associationOverrideElement) {
            $override = [];

            // Check for joinColumn
            if (isset($associationOverrideElement['joinColumn'])) {
                $joinColumns = [];

                foreach ($associationOverrideElement['joinColumn'] as $name => $joinColumnElement) {
                    if (! isset($joinColumnElement['name'])) {
                        $joinColumnElement['name'] = $name;
                    }

                    $joinColumns[] = $this->joinColumnToArray($joinColumnElement);
                }

                $override['joinColumns'] = $joinColumns;
            }

            // Check for joinTable
            if (isset($associationOverrideElement['joinTable'])) {
                $joinTableElement = $associationOverrideElement['joinTable'];
                $joinTable        = [
                    'name' => $joinTableElement['name']
                ];

                if (isset($joinTableElement['schema'])) {
                    $joinTable['schema'] = $joinTableElement['schema'];
                }

                foreach ($joinTableElement['joinColumns'] as $name => $joinColumnElement) {
                    if (! isset($joinColumnElement['name'])) {
                        $joinColumnElement['name'] = $name;
                    }

                    $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumnElement);
                }

                foreach ($joinTableElement['inverseJoinColumns'] as $name => $joinColumnElement) {
                    if (! isset($joinColumnElement['name'])) {
                        $joinColumnElement['name'] = $name;
                    }

                    $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumnElement);
                }

                $override['joinTable'] = $joinTable;
            }

            $metadata->setAssociationOverride($fieldName, $override);
        }
    }

    /**
     * Constructs a joinColumn mapping array based on the information
     * found in the given join column element.
     *
     * @param array $joinColumnElement The array join column element.
     *
     * @return array The mapping array.
     */
    private function joinColumnToArray(
        array $joinColumnElement
    ) {
        $joinColumn = [];

        if (isset($joinColumnElement['referencedColumnName'])) {
            $joinColumn['referencedColumnName'] =
                (string)$joinColumnElement['referencedColumnName'];
        }

        if (isset($joinColumnElement['name'])) {
            $joinColumn['name'] = (string)$joinColumnElement['name'];
        }

        if (isset($joinColumnElement['fieldName'])) {
            $joinColumn['fieldName'] = (string)$joinColumnElement['fieldName'];
        }

        if (isset($joinColumnElement['unique'])) {
            $joinColumn['unique'] = (bool)$joinColumnElement['unique'];
        }

        if (isset($joinColumnElement['nullable'])) {
            $joinColumn['nullable'] = (bool)$joinColumnElement['nullable'];
        }

        if (isset($joinColumnElement['onDelete'])) {
            $joinColumn['onDelete'] = $joinColumnElement['onDelete'];
        }

        if (isset($joinColumnElement['columnDefinition'])) {
            $joinColumn['columnDefinition'] =
                $joinColumnElement['columnDefinition'];
        }

        return $joinColumn;
    }
}
