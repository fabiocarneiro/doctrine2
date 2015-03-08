<?php

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ORM\Mapping\Driver\Evaluator\EvaluatorInterface;
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
     * @var EvaluatorInterface[]
     */
    private $evaluators;

    /**
     * {@inheritDoc}
     * @param EvaluatorInterface[] $evaluators
     */
    public function __construct(
        $locator,
        $fileExtension = self::DEFAULT_FILE_EXTENSION,
        array $evaluators
    ) {
        $this->evaluators = $evaluators;
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

        foreach ($this->evaluators as $evaluator) {
            $evaluator->evaluate($element, $metadata);
        }

        if (isset($element['options'])) {
            $metadata->table['options'] = $element['options'];
        }

        // Evaluate attributeOverride
        $this->evaluateAttributeOverride($element, $metadata);

        // Evaluate entityListeners
        $this->evaluateEntityListeners($element, $metadata);
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
        $region = null;
        $usage  = null;

        if (isset($cacheMapping['region'])) {
            $region = (string)$cacheMapping['region'];
        }

        if (isset($cacheMapping['usage'])) {
            $usage = (string)$cacheMapping['usage'];
        }

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
