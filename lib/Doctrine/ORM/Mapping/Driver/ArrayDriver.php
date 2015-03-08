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

        if (! isset($element['type'])) {
            throw MappingException::invalidMapping('type');
        }

        foreach ($this->evaluators as $evaluator) {
            $evaluator->evaluate($element, $metadata);
        }

        if (isset($element['options'])) {
            $metadata->table['options'] = $element['options'];
        }
    }
}
