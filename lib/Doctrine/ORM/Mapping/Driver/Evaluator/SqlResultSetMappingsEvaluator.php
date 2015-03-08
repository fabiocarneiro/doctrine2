<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class SqlResultSetMappingsEvaluator implements EvaluatorInterface
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

        if (! isset($element['sqlResultSetMappings'])) {
            return;
        }

        foreach ($element['sqlResultSetMappings'] as $name => $resultSetMapping) {
            if (! isset($resultSetMapping['name'])) {
                $resultSetMapping['name'] = $name;
            }

            $entities = [];
            $columns  = [];

            if (isset($resultSetMapping['entityResult'])) {
                foreach ($resultSetMapping['entityResult'] as $entityResultElement) {

                    $entityClass         = null;
                    $discriminatorColumn = null;


                    if (isset($entityResultElement['entityClass'])) {
                        $entityClass = $entityResultElement['entityClass'];
                    }

                    if (isset($entityResultElement['discriminatorColumn'])) {
                        $discriminatorColumn = $entityResultElement['discriminatorColumn'];
                    }

                    $entityResult = [
                        'fields' => [],
                        'entityClass' => $entityClass,
                        'discriminatorColumn' => $discriminatorColumn,
                    ];

                    if (isset($entityResultElement['fieldResult'])) {
                        foreach ($entityResultElement['fieldResult'] as $fieldResultElement) {
                            $name   = null;
                            $column = null;

                            if (isset($fieldResultElement['name'])) {
                                $name = $fieldResultElement['name'];
                            }

                            if (isset($fieldResultElement['column'])) {
                                $column = $fieldResultElement['column'];
                            }

                            $entityResult['fields'][] = [
                                'name' => $name,
                                'column' => $column
                            ];
                        }
                    }

                    $entities[] = $entityResult;
                }
            }

            if (isset($resultSetMapping['columnResult'])) {
                foreach ($resultSetMapping['columnResult'] as $columnResultAnnot) {
                    $name = null;

                    if (isset($columnResultAnnot['name'])) {
                        $name = $columnResultAnnot['name'];
                    }

                    $columns[] = [
                        'name' => $name,
                    ];
                }
            }

            $metadata->addSqlResultSetMapping(
                [
                    'name' => $resultSetMapping['name'],
                    'entities' => $entities,
                    'columns' => $columns
                ]
            );
        }
    }
}
