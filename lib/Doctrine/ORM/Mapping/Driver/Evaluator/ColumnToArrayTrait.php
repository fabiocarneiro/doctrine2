<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
trait ColumnToArrayTrait
{
    /**
     * Parses the given column as array.
     *
     * @param string $fieldName
     * @param array  $column
     *
     * @return  array
     */
    private function columnToArray(
        $fieldName,
        $column
    ) {
        $mapping = [
            'fieldName' => $fieldName
        ];

        if (isset($column['type'])) {
            $params          = explode('(', $column['type']);
            $column['type']  = $params[0];
            $mapping['type'] = $column['type'];
            if (isset($params[1])) {
                $column['length'] =
                    (integer)substr($params[1], 0, strlen($params[1]) - 1);
            }
        }

        if (isset($column['column'])) {
            $mapping['columnName'] = $column['column'];
        }

        if (isset($column['length'])) {
            $mapping['length'] = $column['length'];
        }

        if (isset($column['precision'])) {
            $mapping['precision'] = $column['precision'];
        }

        if (isset($column['scale'])) {
            $mapping['scale'] = $column['scale'];
        }

        if (isset($column['unique'])) {
            $mapping['unique'] = (bool)$column['unique'];
        }

        if (isset($column['options'])) {
            $mapping['options'] = $column['options'];
        }

        if (isset($column['nullable'])) {
            $mapping['nullable'] = $column['nullable'];
        }

        if (isset($column['version']) && $column['version']) {
            $mapping['version'] = $column['version'];
        }

        if (isset($column['columnDefinition'])) {
            $mapping['columnDefinition'] = $column['columnDefinition'];
        }

        return $mapping;
    }
}
