<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Interface EvaluatorInterface
 */
interface EvaluatorInterface
{
    /**
     * @param array         $element
     * @param ClassMetadata $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata);
}