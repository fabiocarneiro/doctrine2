<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
trait JoinColumnToArrayTrait
{
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
