<?php

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ORM\Mapping\MappingException;

/**
 * @author  Fábio Carneiro <fahecs@gmail.com>
 * @license MIT
 */
class ArrayDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.mapping.php';

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @var array
     */
    private $evaluators = [
        'Doctrine\ORM\Mapping\Driver\Evaluator\FieldsEvaluator',
        'Doctrine\ORM\Mapping\Driver\Evaluator\SecondLevelCacheEvaluator'
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(
        $locator,
        $fileExtension = self::DEFAULT_FILE_EXTENSION
    ) {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        return require $file;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        $element = $this->getElement($className);

        if ( ! isset($element['type'])) {
            throw MappingException::invalidMapping('type');
        }

        foreach($this->evaluators as $evaluatorClassName) {
            $evaluator = new $evaluatorClassName;
            $evaluator->evaluate($element, $metadata);
        }

        // Evaluate model type
        $this->evaluateModelType($element, $metadata, $className);

        // Evaluate root level properties
        $this->evaluateRootLevelProperties($element, $metadata);

        // Evaluate named queries
        $this->evaluateNamedQueries($element, $metadata);

        // Evaluate named native queries
        $this->evaluateNamedNativeQueries($element, $metadata);

        // Evaluate sql result set mappings
        $this->evaluateSqlResultSetMappings($element, $metadata);

        // Evaluate inheritance
        $this->evaluateInheritance($element, $metadata);

        // Evaluate changeTrackingPolicy
        $this->evaluateTrackingPolicy($element, $metadata);

        // Evaluate indexes
        $this->evaluateIndexes($element, $metadata);

        // Evaluate uniqueConstraints
        $this->evaluateUniqueConstraints($element, $metadata);


        if (isset($element['options'])) {
            $metadata->table['options'] = $element['options'];
        }

        $this->evaluateAssociations($element, $metadata, $className);

        // Evaluate embeddeds
        $this->evaluateEmbeddeds($element, $metadata);

        // Evaluate oneToOne relationships
        $this->evaluateOneToOne($element, $metadata);

        // Evaluate oneToMany relationships
        $this->evaluateOneToMany($element, $metadata);

        // Evaluate manyToOne relationships
        $this->evaluateManyToOne($element, $metadata);

        // Evaluate manyToMany relationships
        $this->evaluateManyToMany($element, $metadata);

        // Evaluate associationOverride
        $this->evaluateAssociationOverride($element, $metadata);

        // Evaluate attributeOverride
        $this->evaluateAttributeOverride($element, $metadata);

        // Evaluate lifeCycleCallbacks
        $this->evaluateLifecycleCallbacks($element, $metadata);

        // Evaluate entityListeners
        $this->evaluateEntityListeners($element, $metadata);
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @param string        $className
     * @return void
     */
    private function evaluateModelType(
        array $element,
        ClassMetadata $metadata,
        $className
    ) {
        if ($element['type'] == 'entity') {
            if (isset($element['repositoryClass'])) {
                $metadata->setCustomRepositoryClass(
                    $element['repositoryClass']
                );
            }
            if (isset($element['readOnly']) && $element['readOnly'] == true) {
                $metadata->markReadOnly();
            }

            return;
        }

        if ($element['type'] == 'mappedSuperclass') {
            $repositoryClass = null;

            if (isset($element['repositoryClass'])) {
                $repositoryClass = $element['repositoryClass'];
            }

            $metadata->setCustomRepositoryClass($repositoryClass);
            $metadata->isMappedSuperclass = true;

            return;
        }

        if ($element['type'] == 'embeddable') {
            $metadata->isEmbeddedClass = true;

            return;
        }

        throw MappingException::classIsNotAValidEntityOrMappedSuperClass(
            $className
        );
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateRootLevelProperties(
        array $element,
        ClassMetadata $metadata
    ) {
        $primaryTable = [];

        if (isset($element['table'])) {
            $primaryTable['name'] = $element['table'];
        }

        if (isset($element['schema'])) {
            $primaryTable['schema'] = $element['schema'];
        }
        $metadata->setPrimaryTable($primaryTable);
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateNamedQueries(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['namedQueries'])) {
            return;
        }

        foreach ($element['namedQueries'] as $name => $queryMapping) {
            if (is_string($queryMapping)) {
                $queryMapping = ['query' => $queryMapping];
            }
            if ( ! isset($queryMapping['name'])) {
                $queryMapping['name'] = $name;
            }
            $metadata->addNamedQuery($queryMapping);
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateNamedNativeQueries(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['namedNativeQueries'])) {
            return;
        }

        foreach ($element['namedNativeQueries'] as $name => $mappingElement) {
            if ( ! isset($mappingElement['name'])) {
                $mappingElement['name'] = $name;
            }

            $query            = null;
            $resultClass      = null;
            $resultSetMapping = null;

            if (isset($mappingElement['query'])) {
                $query = $mappingElement['query'];
            }

            if (isset($mappingElement['resultClass'])) {
                $resultClass = $mappingElement['resultClass'];
            }

            if (isset($mappingElement['resultSetMapping'])) {
                $resultSetMapping = $mappingElement['resultSetMapping'];
            }

            $metadata->addNamedNativeQuery(
                [
                    'name' => $mappingElement['name'],
                    'query' => $query,
                    'resultClass' => $resultClass,
                    'resultSetMapping' => $resultSetMapping,
                ]
            );
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateSqlResultSetMappings(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['sqlResultSetMappings'])) {
            return;
        }

        foreach ($element['sqlResultSetMappings'] as $name => $resultSetMapping) {
            if ( ! isset($resultSetMapping['name'])) {
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

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateInheritance(
        array $element,
        ClassMetadata $metadata
    ) {
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

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateTrackingPolicy(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['changeTrackingPolicy'])) {
            return;
        }

        $metadata->setChangeTrackingPolicy(
            constant(
                'Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_'
                . strtoupper($element['changeTrackingPolicy'])
            )
        );
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateIndexes(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['indexes'])) {
            return;
        }

        foreach ($element['indexes'] as $name => $indexYml) {
            if ( ! isset($indexYml['name'])) {
                $indexYml['name'] = $name;
            }
            if (is_string($indexYml['columns'])) {
                $index = [
                    'columns' => array_map(
                        'trim',
                        explode(',', $indexYml['columns'])
                    )
                ];
            } else {
                $index = ['columns' => $indexYml['columns']];
            }
            if (isset($indexYml['flags'])) {
                if (is_string($indexYml['flags'])) {
                    $index['flags'] =
                        array_map('trim', explode(',', $indexYml['flags']));
                } else {
                    $index['flags'] = $indexYml['flags'];
                }
            }
            if (isset($indexYml['options'])) {
                $index['options'] = $indexYml['options'];
            }
            $metadata->table['indexes'][$indexYml['name']] = $index;
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateUniqueConstraints(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['uniqueConstraints'])) {
            return;
        }

        foreach ($element['uniqueConstraints'] as $name => $uniqueYml) {
            if ( ! isset($uniqueYml['name'])) {
                $uniqueYml['name'] = $name;
            }

            if (is_string($uniqueYml['columns'])) {
                $unique = [
                    'columns' => array_map(
                        'trim',
                        explode(',', $uniqueYml['columns'])
                    )
                ];
            } else {
                $unique = ['columns' => $uniqueYml['columns']];
            }

            if (isset($uniqueYml['options'])) {
                $unique['options'] = $uniqueYml['options'];
            }

            $metadata->table['uniqueConstraints'][$uniqueYml['name']] =
                $unique;
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @param string        $className
     * @throws MappingException
     * @return void
     */
    private function evaluateAssociations(
        array $element,
        ClassMetadata $metadata,
        $className
    ) {
        if ( ! isset($element['id'])) {
            return;
        }

        $associationIds = [];
        // Evaluate identifier settings
        foreach ($element['id'] as $name => $idElement) {
            if (isset($idElement['associationKey'])
                && $idElement['associationKey'] == true
            ) {
                $associationIds[$name] = true;
                continue;
            }
            $mapping = [
                'id' => true,
                'fieldName' => $name
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
            } else {
                if (isset($idElement['customIdGenerator'])) {
                    $customGenerator = $idElement['customIdGenerator'];
                    $metadata->setCustomGeneratorDefinition(
                        [
                            'class' => (string)$customGenerator['class']
                        ]
                    );
                } else {
                    if (isset($idElement['tableGenerator'])) {
                        throw MappingException::tableIdGeneratorNotImplemented(
                            $className
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateEmbeddeds(
        array $element,
        ClassMetadata $metadata
    ) {
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

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateOneToOne(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['oneToOne'])) {
            return;
        }

        foreach ($element['oneToOne'] as $name => $oneToOneElement) {
            $mapping = [
                'fieldName' => $name,
                'targetEntity' => $oneToOneElement['targetEntity']
            ];
            if (isset($associationIds[$mapping['fieldName']])) {
                $mapping['id'] = true;
            }
            if (isset($oneToOneElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $oneToOneElement['fetch']
                    );
            }
            if (isset($oneToOneElement['mappedBy'])) {
                $mapping['mappedBy'] = $oneToOneElement['mappedBy'];
            } else {
                if (isset($oneToOneElement['inversedBy'])) {
                    $mapping['inversedBy'] = $oneToOneElement['inversedBy'];
                }
                $joinColumns = [];
                if (isset($oneToOneElement['joinColumn'])) {
                    $joinColumns[] =
                        $this->joinColumnToArray(
                            $oneToOneElement['joinColumn']
                        );
                } else {
                    if (isset($oneToOneElement['joinColumns'])) {
                        foreach ($oneToOneElement['joinColumns'] as
                                 $joinColumnName => $joinColumnElement) {
                            if ( ! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] =
                                    $joinColumnName;
                            }
                            $joinColumns[] =
                                $this->joinColumnToArray(
                                    $joinColumnElement
                                );
                        }
                    }
                }
                $mapping['joinColumns'] = $joinColumns;
            }
            if (isset($oneToOneElement['cascade'])) {
                $mapping['cascade'] = $oneToOneElement['cascade'];
            }
            if (isset($oneToOneElement['orphanRemoval'])) {
                $mapping['orphanRemoval'] =
                    (bool)$oneToOneElement['orphanRemoval'];
            }
            $metadata->mapOneToOne($mapping);
            // Evaluate second level cache
            if (isset($oneToOneElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($oneToOneElement['cache'])
                );
            }
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateOneToMany(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['oneToMany'])) {
            return;
        }

        foreach ($element['oneToMany'] as $name => $oneToManyElement) {
            $mapping = [
                'fieldName' => $name,
                'targetEntity' => $oneToManyElement['targetEntity'],
                'mappedBy' => $oneToManyElement['mappedBy']
            ];
            if (isset($oneToManyElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $oneToManyElement['fetch']
                    );
            }
            if (isset($oneToManyElement['cascade'])) {
                $mapping['cascade'] = $oneToManyElement['cascade'];
            }
            if (isset($oneToManyElement['orphanRemoval'])) {
                $mapping['orphanRemoval'] =
                    (bool)$oneToManyElement['orphanRemoval'];
            }
            if (isset($oneToManyElement['orderBy'])) {
                $mapping['orderBy'] = $oneToManyElement['orderBy'];
            }
            if (isset($oneToManyElement['indexBy'])) {
                $mapping['indexBy'] = $oneToManyElement['indexBy'];
            }
            $metadata->mapOneToMany($mapping);
            // Evaluate second level cache
            if (isset($oneToManyElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($oneToManyElement['cache'])
                );
            }
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateManyToOne(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['manyToOne'])) {
            return;
        }

        foreach ($element['manyToOne'] as $name => $manyToOneElement) {
            $mapping = [
                'fieldName' => $name,
                'targetEntity' => $manyToOneElement['targetEntity']
            ];
            if (isset($associationIds[$mapping['fieldName']])) {
                $mapping['id'] = true;
            }
            if (isset($manyToOneElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $manyToOneElement['fetch']
                    );
            }
            if (isset($manyToOneElement['inversedBy'])) {
                $mapping['inversedBy'] = $manyToOneElement['inversedBy'];
            }
            $joinColumns = [];
            if (isset($manyToOneElement['joinColumn'])) {
                $joinColumns[] =
                    $this->joinColumnToArray(
                        $manyToOneElement['joinColumn']
                    );
            } else {
                if (isset($manyToOneElement['joinColumns'])) {
                    foreach ($manyToOneElement['joinColumns'] as
                             $joinColumnName => $joinColumnElement) {
                        if ( ! isset($joinColumnElement['name'])) {
                            $joinColumnElement['name'] = $joinColumnName;
                        }
                        $joinColumns[] =
                            $this->joinColumnToArray($joinColumnElement);
                    }
                }
            }
            $mapping['joinColumns'] = $joinColumns;
            if (isset($manyToOneElement['cascade'])) {
                $mapping['cascade'] = $manyToOneElement['cascade'];
            }
            $metadata->mapManyToOne($mapping);
            // Evaluate second level cache
            if (isset($manyToOneElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($manyToOneElement['cache'])
                );
            }
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateManyToMany(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['manyToMany'])) {
            return;
        }

        foreach ($element['manyToMany'] as $name => $manyToManyElement) {
            $mapping = [
                'fieldName' => $name,
                'targetEntity' => $manyToManyElement['targetEntity']
            ];
            if (isset($manyToManyElement['fetch'])) {
                $mapping['fetch'] =
                    constant(
                        'Doctrine\ORM\Mapping\ClassMetadata::FETCH_'
                        . $manyToManyElement['fetch']
                    );
            }
            if (isset($manyToManyElement['mappedBy'])) {
                $mapping['mappedBy'] = $manyToManyElement['mappedBy'];
            } else {
                if (isset($manyToManyElement['joinTable'])) {
                    $joinTableElement = $manyToManyElement['joinTable'];
                    $joinTable        = [
                        'name' => $joinTableElement['name']
                    ];
                    if (isset($joinTableElement['schema'])) {
                        $joinTable['schema'] = $joinTableElement['schema'];
                    }
                    if (isset($joinTableElement['joinColumns'])) {
                        foreach ($joinTableElement['joinColumns'] as
                                 $joinColumnName => $joinColumnElement) {
                            if ( ! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] =
                                    $joinColumnName;
                            }
                        }
                        $joinTable['joinColumns'][] =
                            $this->joinColumnToArray($joinColumnElement);
                    }
                    if (isset($joinTableElement['inverseJoinColumns'])) {
                        foreach ($joinTableElement['inverseJoinColumns'] as
                                 $joinColumnName => $joinColumnElement) {
                            if ( ! isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] =
                                    $joinColumnName;
                            }
                        }
                        $joinTable['inverseJoinColumns'][] =
                            $this->joinColumnToArray($joinColumnElement);
                    }
                    $mapping['joinTable'] = $joinTable;
                }
            }
            if (isset($manyToManyElement['inversedBy'])) {
                $mapping['inversedBy'] = $manyToManyElement['inversedBy'];
            }
            if (isset($manyToManyElement['cascade'])) {
                $mapping['cascade'] = $manyToManyElement['cascade'];
            }
            if (isset($manyToManyElement['orderBy'])) {
                $mapping['orderBy'] = $manyToManyElement['orderBy'];
            }
            if (isset($manyToManyElement['indexBy'])) {
                $mapping['indexBy'] = $manyToManyElement['indexBy'];
            }
            if (isset($manyToManyElement['orphanRemoval'])) {
                $mapping['orphanRemoval'] =
                    (bool)$manyToManyElement['orphanRemoval'];
            }
            $metadata->mapManyToMany($mapping);
            // Evaluate second level cache
            if (isset($manyToManyElement['cache'])) {
                $metadata->enableAssociationCache(
                    $mapping['fieldName'],
                    $this->cacheToArray($manyToManyElement['cache'])
                );
            }
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateAssociationOverride(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['associationOverride'])
            || ! is_array($element['associationOverride'])
        ) {
            return;
        }

        foreach ($element['associationOverride'] as $fieldName =>
                 $associationOverrideElement) {
            $override = [];
            // Check for joinColumn
            if (isset($associationOverrideElement['joinColumn'])) {
                $joinColumns = [];
                foreach ($associationOverrideElement['joinColumn'] as $name =>
                         $joinColumnElement) {
                    if ( ! isset($joinColumnElement['name'])) {
                        $joinColumnElement['name'] = $name;
                    }
                    $joinColumns[] =
                        $this->joinColumnToArray($joinColumnElement);
                }
                $override['joinColumns'] = $joinColumns;
            }
            // Check for joinTable
            if (isset($associationOverrideElement['joinTable'])) {
                $joinTableElement =
                    $associationOverrideElement['joinTable'];
                $joinTable        = [
                    'name' => $joinTableElement['name']
                ];
                if (isset($joinTableElement['schema'])) {
                    $joinTable['schema'] = $joinTableElement['schema'];
                }
                foreach ($joinTableElement['joinColumns'] as $name =>
                         $joinColumnElement) {
                    if ( ! isset($joinColumnElement['name'])) {
                        $joinColumnElement['name'] = $name;
                    }
                    $joinTable['joinColumns'][] =
                        $this->joinColumnToArray($joinColumnElement);
                }
                foreach ($joinTableElement['inverseJoinColumns'] as $name =>
                         $joinColumnElement) {
                    if ( ! isset($joinColumnElement['name'])) {
                        $joinColumnElement['name'] = $name;
                    }
                    $joinTable['inverseJoinColumns'][] =
                        $this->joinColumnToArray($joinColumnElement);
                }
                $override['joinTable'] = $joinTable;
            }
            $metadata->setAssociationOverride($fieldName, $override);
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateAttributeOverride(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['attributeOverride'])
            || ! is_array($element['attributeOverride'])
        ) {
            return;
        }

        foreach ($element['attributeOverride'] as $fieldName =>
                 $attributeOverrideElement) {
            $mapping =
                $this->columnToArray($fieldName, $attributeOverrideElement);
            $metadata->setAttributeOverride($fieldName, $mapping);
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateLifecycleCallbacks(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['lifecycleCallbacks'])) {
            return;
        }

        foreach ($element['lifecycleCallbacks'] as $type => $methods) {
            foreach ($methods as $method) {
                $metadata->addLifecycleCallback(
                    $method,
                    constant('Doctrine\ORM\Events::' . $type)
                );
            }
        }
    }

    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    private function evaluateEntityListeners(
        array $element,
        ClassMetadata $metadata
    ) {
        if ( ! isset($element['entityListeners'])) {
            return;
        }

        foreach ($element['entityListeners'] as $className => $entityListener) {
            // Evaluate the listener using naming convention.
            if (empty($entityListener)) {
                EntityListenerBuilder::bindEntityListener(
                    $metadata,
                    $className
                );
                continue;
            }
            foreach ($entityListener as $eventName => $callbackElement) {
                foreach ($callbackElement as $methodName) {
                    $metadata->addEntityListener(
                        $eventName,
                        $className,
                        $methodName
                    );
                }
            }
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

    /**
     * Parse / Normalize the cache configuration
     *
     * @param array $cacheMapping
     *
     * @return array
     */
    private function cacheToArray(
        $cacheMapping
    ) {
        $region =
            isset($cacheMapping['region']) ? (string)$cacheMapping['region']
                : null;
        $usage  =
            isset($cacheMapping['usage']) ? strtoupper($cacheMapping['usage'])
                : null;
        if ($usage
            && ! defined(
                'Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_'
                . $usage
            )
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid cache usage "%s"',
                    $usage
                )
            );
        }
        if ($usage) {
            $usage = constant(
                'Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_'
                . $usage
            );
        }
        return [
            'usage' => $usage,
            'region' => $region,
        ];
    }
}
