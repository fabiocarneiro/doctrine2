<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class EntityListenersEvaluator implements EvaluatorInterface
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

        if (! isset($element['entityListeners'])) {
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
}
