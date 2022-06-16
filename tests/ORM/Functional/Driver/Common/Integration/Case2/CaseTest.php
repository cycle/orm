<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2;

use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity\MarkAspectResult;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity\MarkCriterionResult;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity\MarkSubcriterionResult;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity\Student;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity\StudentProgress;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();

        // Make tables
        $this->makeTable('students', [
            'id' => 'string,primary',
            'first_name' => 'string',
        ]);

        $this->makeTable('mark_criterion_results', [
            'id' => 'string,primary',
            'result_objective' => 'int',
            'student_id' => 'string',
        ]);
        $this->makeFK('mark_criterion_results', 'student_id', 'students', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('student_progresses', [
            'id' => 'string,primary',
            'aspects_entered_count' => 'int',
            'student_id' => 'string',
        ]);
        $this->makeFK('student_progresses', 'student_id', 'students', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('mark_subcriterion_results', [
            'id' => 'string,primary',
            'result_objective' => 'int',
            'mark_criterion_result_id' => 'string',
            'student_id' => 'string',
        ]);
        $this->makeFK('mark_subcriterion_results', 'student_id', 'students', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('mark_subcriterion_results', 'mark_criterion_result_id', 'mark_criterion_results', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('mark_aspect_results', [
            'id' => 'string,primary',
            'marks_requires_attention' => 'bool',
            'student_id' => 'string',
            'mark_subcriterion_result_id' => 'string',
        ]);
        $this->makeFK('mark_aspect_results', 'student_id', 'students', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('mark_aspect_results', 'mark_subcriterion_result_id', 'mark_subcriterion_results', 'id', 'NO ACTION', 'NO ACTION');

        // fill data

        $this->getDatabase()->table('students')->insertMultiple(
            ['id', 'first_name'],
            [
                ['stud-1', 'Kent'],
                ['stud-2', 'Kant'],
            ]
        );
        $this->getDatabase()->table('mark_criterion_results')->insertMultiple(
            ['id', 'result_objective', 'student_id'],
            [
                ['criterion-1', '24', 'stud-1'],
                ['criterion-2', '42', 'stud-1'],
            ]
        );
        $this->getDatabase()->table('student_progresses')->insertMultiple(
            ['id', 'aspects_entered_count', 'student_id'],
            [
                ['progress-1', '52', 'stud-1'],
                ['progress-2', '25', 'stud-1'],
            ]
        );
        $this->getDatabase()->table('mark_subcriterion_results')->insertMultiple(
            ['id', 'result_objective', 'mark_criterion_result_id', 'student_id'],
            [
                ['subcriterion-1', '63', 'criterion-1', 'stud-1'],
                ['subcriterion-2', '36', 'criterion-1', 'stud-1'],
            ]
        );
        $this->getDatabase()->table('mark_aspect_results')->insertMultiple(
            ['id', 'marks_requires_attention', 'student_id', 'mark_subcriterion_result_id'],
            [
                ['aspect-1', '1', 'stud-1', 'subcriterion-1'],
                ['aspect-2', '0', 'stud-1', 'subcriterion-1'],
            ]
        );

        $schema = include __DIR__ . '/schema.php';
        \assert(\is_array($schema));
        $this->orm = $this->orm->withSchema(new Schema($schema));
    }

    public function testRun(): void
    {
        $markCriterionResult = (new Select($this->orm, MarkCriterionResult::class))
            ->wherePK('criterion-1')
            ->fetchOne();
        \assert($markCriterionResult instanceof MarkCriterionResult);

        $sub = $markCriterionResult->markSubcriterionResults->first();
        $aspect = $sub->markAspectResults->first();
        $student = $aspect->student;
        $student->studentProgresses->add(new StudentProgress('new-progress-42'));

        $this->captureWriteQueries();
        $this->save($markCriterionResult, $sub, $aspect);
        $this->assertNumWrites(1);

        $this->orm->getHeap()->clean();
        $studentProgress = (new Select($this->orm, StudentProgress::class))
            ->wherePK('new-progress-42')
            ->fetchOne();
        $this->assertNotNull($studentProgress);
    }
}
