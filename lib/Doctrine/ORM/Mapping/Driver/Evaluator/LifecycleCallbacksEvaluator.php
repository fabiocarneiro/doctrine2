<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;


/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class LifecycleCallbacksEvaluator implements EvaluatorInterface
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

        if (! isset($element['lifecycleCallbacks'])) {
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
}
