<?php

namespace Doctrine\ORM\Mapping\Driver\Evaluator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use InvalidArgumentException;

/**
 * @user    Fábio Carneiro <fahecs@gmail.com>
 * @license MIT
 */
class IndexesEvaluator implements EvaluatorInterface
{
    /**
     * {@inheritDoc}
     * @param array             $element
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    public function evaluate(array $element, ClassMetadata $metadata)
    {
        if ( ! $metadata instanceof ClassMetadataInfo) {
            throw new InvalidArgumentException('Metadata must be a instance of ClassMetadataInfo');
        }

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
}
