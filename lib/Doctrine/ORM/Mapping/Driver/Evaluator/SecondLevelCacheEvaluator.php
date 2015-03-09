<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @user    Fábio Carneiro <fahecs@gmail.com>
 * @license MIT
 */
class SecondLevelCacheEvaluator implements EvaluatorInterface
{
    use CacheToArrayTrait;
    
    /**
     * {@inheritDoc}
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if (! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

        if (! isset($element['cache'])) {
            return;
        }

        $metadata->enableCache($this->cacheToArray($element['cache']));
    }
}
