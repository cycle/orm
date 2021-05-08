<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Heap;

final class HeapCompositeKeysTest extends HeapTest
{
    protected const
        INDEX_FIELDS_1 = ['id', 'user_code'],
        INDEX_VALUES_1_1 = [42, 'ytrewq'],
        INDEX_VALUES_1_2 = [24, 'qwerty'],
        INDEX_FIND_1_1 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_1[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_1[1],
        ],
        INDEX_FIND_1_2 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_2[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_2[1],
        ],
        INDEX_FIND_1_BAD = [
            self::INDEX_FIELDS_1[0] => 404,
            self::INDEX_FIELDS_1[1] => 'none',
        ],

        INDEX_FIELDS_2 = 'email',
        INDEX_VALUES_2_1 = 'mail1@spiral',
        INDEX_VALUES_2_2 = 'mail2@spiral',
        INDEX_FIND_2_1 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1],
        INDEX_FIND_2_2 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2],

        ENTITY_SET_1 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_1[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_1[1],
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1,
        ],
        ENTITY_SET_2 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_2[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_2[1],
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2,
        ];
}
