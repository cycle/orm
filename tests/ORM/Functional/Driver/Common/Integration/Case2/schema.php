<?php

declare(strict_types=1);

use Cycle\ORM\Select\Repository;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2;

return [
    'markCriterionResult' => [
        Schema::ENTITY => Case2\Entity\MarkCriterionResult::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        // Schema::REPOSITORY => 'App\\Repository\\MarkCriterionResult',
        Schema::DATABASE => 'default',
        Schema::TABLE => 'mark_criterion_results',
        Schema::PRIMARY_KEY => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'resultObjective' => 'result_objective',
            'student_id' => 'student_id',
            // 'calculatedAt' => 'calculated_at',
            // 'resultTotal' => 'result_total',
            // 'resultJudgment' => 'result_judgment',
            // 'deletedAt' => 'deleted_at',
            // 'criterion_id' => 'criterion_id',
            // 'exam_id' => 'exam_id',
        ],
        Schema::RELATIONS => [
            'student' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'student',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'student_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'markSubcriterionResults' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'markSubcriterionResult',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [
                        // 'subcriterion.order' => 'asc',
                    ],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['mark_criterion_result_id'],
                ],
            ],
        ],
        // Schema::SCOPE => 'App\\Database\\Scope\\NotDeleted',
        Schema::TYPECAST => [
            'resultObjective' => 'int',
            // 'calculatedAt' => 'datetime',
            // 'id' => [Uuid::class, 'fromString'],
            // 'resultTotal' => 'int',
            // 'resultJudgment' => 'int',
            // 'deletedAt' => 'datetime',
        ],
        Schema::LISTENERS => [
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Uuid\\Listener\\Uuid6',
            //     [
            //         'field' => 'id',
            //         'node' => null,
            //         'clockSeq' => null,
            //     ],
            // ],
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Listener\\SoftDelete',
            //     [
            //         'field' => 'deletedAt',
            //     ],
            // ],
        ],
        Schema::TYPECAST_HANDLER => [Typecast::class],
    ],
    'student' => [
        Schema::ENTITY => Case2\Entity\Student::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        // Schema::REPOSITORY => 'App\\Repository\\Student',
        Schema::DATABASE => 'default',
        Schema::TABLE => 'students',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'firstName' => 'first_name',
            // 'skillsPassportNumber' => 'skills_passport_number',
            // 'email' => 'email',
            // 'mobile' => 'mobile',
            // 'externalId' => 'external_id',
            // 'lastName' => 'last_name',
            // 'middleName' => 'middle_name',
            // 'birthday' => 'birthday',
            // 'photoUrl' => 'photo_url',
            // 'createdAt' => 'created_at',
            // 'study_group_id' => 'study_group_id',
            // 'region_id' => 'region_id',
            // 'exam_id' => 'exam_id',
        ],
        Schema::RELATIONS => [
            'markAspectResults' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'markAspectResult',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [
                        // 'aspect.order' => 'asc',
                    ],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'student_id',
                    Relation::INVERSION => 'student',
                ],
            ],
            'markAspectResultsWhoRequiresAttention' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'markAspectResult',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [
                        'marks_requires_attention' => true,
                    ],
                    Relation::ORDER_BY => [
                        // 'aspect.order' => 'asc',
                    ],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => 'student_id',
                ],
            ],
            'studentProgresses' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'studentProgress',
                Relation::LOAD => Relation::LOAD_EAGER,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['student_id'],
                ],
            ],
        ],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            // 'id' => [Uuid::class, 'fromString'],
            // 'birthday' => 'datetime',
            // 'createdAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::LISTENERS => [
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Uuid\\Listener\\Uuid6',
            //     [
            //         'field' => 'id',
            //         'node' => null,
            //         'clockSeq' => null,
            //     ],
            // ],
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
            //     [
            //         'field' => 'createdAt',
            //     ],
            // ],
        ],
        Schema::TYPECAST_HANDLER => [Typecast::class],
    ],
    'studentProgress' => [
        Schema::ENTITY => Case2\Entity\StudentProgress::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        Schema::DATABASE => 'default',
        Schema::TABLE => 'student_progresses',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'student_id' => 'student_id',
            'aspectsEnteredCount' => 'aspects_entered_count',
            // 'marksEnteredPercentage' => 'marks_entered_percentage',
            // 'createdAt' => 'created_at',
            // 'updatedAt' => 'updated_at',
            // 'exam_expert_id' => 'exam_expert_id',
            // 'exam_id' => 'exam_id',
        ],
        Schema::RELATIONS => [],
        Schema::SCOPE => null,
        Schema::TYPECAST => [
            // 'marksEnteredPercentage' => 'float',
            'aspectsEnteredCount' => 'int',
            // 'id' => [Uuid::class, 'fromString'],
            // 'createdAt' => 'datetime',
            // 'updatedAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::LISTENERS => [
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Uuid\\Listener\\Uuid6',
            //     [
            //         'field' => 'id',
            //         'node' => null,
            //         'clockSeq' => null,
            //     ],
            // ],
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
            //     [
            //         'field' => 'createdAt',
            //     ],
            // ],
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
            //     [
            //         'field' => 'updatedAt',
            //         'nullable' => false,
            //     ],
            // ],
        ],
        Schema::TYPECAST_HANDLER => [Typecast::class],
    ],
    'markAspectResult' => [
        Schema::ENTITY => Case2\Entity\MarkAspectResult::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        // Schema::REPOSITORY => 'App\\Repository\\MarkAspectResult',
        Schema::DATABASE => 'default',
        Schema::TABLE => 'mark_aspect_results',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            // 'resultObjective' => 'result_objective',
            'student_id' => 'student_id',
            'mark_subcriterion_result_id' => 'mark_subcriterion_result_id',
            'marksRequiresAttention' => 'marks_requires_attention',
            // 'calculatedAt' => 'calculated_at',
            // 'resultTotal' => 'result_total',
            // 'resultJudgment' => 'result_judgment',
            // 'aspect_id' => 'aspect_id',
            // 'subcriterion_id' => 'subcriterion_id',
            // 'exam_id' => 'exam_id',
            // 'deletedAt' => 'deleted_at',
        ],
        Schema::RELATIONS => [
            'student' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'student',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'student_id',
                    Relation::OUTER_KEY => ['id'],
                    Relation::INVERSION => 'markAspectResults',
                ],
            ],
            'markSubcriterionResult' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'markSubcriterionResult',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => ['mark_subcriterion_result_id'],
                    Relation::OUTER_KEY => ['id'],
                    Relation::INVERSION => 'markAspectResults',
                ],
            ],
        ],
        // Schema::SCOPE => 'App\\Database\\Scope\\NotDeleted',
        Schema::TYPECAST => [
            // 'calculatedAt' => 'datetime',
            'marksRequiresAttention' => 'bool',
            // 'id' => ['Ramsey\\Uuid\\Uuid', 'fromString'],
            // 'resultTotal' => 'int',
            // 'resultJudgment' => 'int',
            // 'resultObjective' => 'int',
            // 'deletedAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::LISTENERS => [
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Uuid\\Listener\\Uuid6',
            //     [
            //         'field' => 'id',
            //         'node' => null,
            //         'clockSeq' => null,
            //     ],
            // ],
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Listener\\SoftDelete',
            //     [
            //         'field' => 'deletedAt',
            //     ],
            // ],
        ],
        Schema::TYPECAST_HANDLER => [Typecast::class],
    ],
    'markSubcriterionResult' => [
        Schema::ENTITY => Case2\Entity\MarkSubcriterionResult::class,
        Schema::MAPPER => Mapper::class,
        Schema::SOURCE => Source::class,
        Schema::REPOSITORY => Repository::class,
        // Schema::REPOSITORY => 'App\\Repository\\MarkSubcriterionResult',
        Schema::DATABASE => 'default',
        Schema::TABLE => 'mark_subcriterion_results',
        Schema::PRIMARY_KEY => ['id'],
        Schema::FIND_BY_KEYS => ['id'],
        Schema::COLUMNS => [
            'id' => 'id',
            'resultObjective' => 'result_objective',
            'mark_criterion_result_id' => 'mark_criterion_result_id',
            'student_id' => 'student_id',
            // 'calculatedAt' => 'calculated_at',
            // 'resultTotal' => 'result_total',
            // 'resultJudgment' => 'result_judgment',
            // 'deletedAt' => 'deleted_at',
            // 'subcriterion_id' => 'subcriterion_id',
            // 'exam_id' => 'exam_id',
        ],
        Schema::RELATIONS => [
            'student' => [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => 'student',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::INNER_KEY => 'student_id',
                    Relation::OUTER_KEY => ['id'],
                ],
            ],
            'markAspectResults' => [
                Relation::TYPE => Relation::HAS_MANY,
                Relation::TARGET => 'markAspectResult',
                Relation::LOAD => Relation::LOAD_PROMISE,
                Relation::SCHEMA => [
                    Relation::CASCADE => true,
                    Relation::NULLABLE => false,
                    Relation::WHERE => [],
                    Relation::ORDER_BY => [
                        // 'aspect.order' => 'asc',
                    ],
                    Relation::INNER_KEY => ['id'],
                    Relation::OUTER_KEY => ['mark_subcriterion_result_id'],
                    Relation::INVERSION => 'markSubcriterionResult',
                ],
            ],
        ],
        // Schema::SCOPE => 'App\\Database\\Scope\\NotDeleted',
        Schema::TYPECAST => [
            // 'calculatedAt' => 'datetime',
            // 'id' => ['Ramsey\\Uuid\\Uuid', 'fromString'],
            // 'resultTotal' => 'int',
            // 'resultJudgment' => 'int',
            'resultObjective' => 'int',
            // 'deletedAt' => 'datetime',
        ],
        Schema::SCHEMA => [],
        Schema::LISTENERS => [
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Uuid\\Listener\\Uuid6',
            //     [
            //         'field' => 'id',
            //         'node' => null,
            //         'clockSeq' => null,
            //     ],
            // ],
            // [
            //     'Cycle\\ORM\\Entity\\Behavior\\Listener\\SoftDelete',
            //     [
            //         'field' => 'deletedAt',
            //     ],
            // ],
        ],
        Schema::TYPECAST_HANDLER => [Typecast::class],
    ],
];
