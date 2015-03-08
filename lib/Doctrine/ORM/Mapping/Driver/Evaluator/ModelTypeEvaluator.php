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
class ModelTypeEvaluator implements EvaluatorInterface
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
}
