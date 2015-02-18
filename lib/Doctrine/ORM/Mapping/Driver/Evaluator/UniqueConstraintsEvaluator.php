<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @author    Fábio Carneiro <fahecs@gmail.com>
 * @license   MIT
 */
class UniqueConstraintsEvaluator implements EvaluatorInterface
{

    /**
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if ( ! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

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

            $metadata->table['uniqueConstraints'][$uniqueYml['name']] = $unique;
        }
    }
}