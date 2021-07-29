<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Trait;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Employee;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Engineer;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Manager;
use Cycle\ORM\Tests\Relation\JTI\Fixture\Programator;

trait SelectTrait
{

    public function testSelectEmployeeAllData(): void
    {
        $selector = new Select($this->orm, Employee::class);

        $this->assertEquals(static::EMPLOYEE_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEmployeeDataFirst(): void
    {
        $selector = (new Select($this->orm, Employee::class))->limit(1);

        $this->assertEquals(static::EMPLOYEE_1_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectEngineerAllData(): void
    {
        $selector = (new Select($this->orm, Engineer::class));

        $this->assertEquals(static::ENGINEER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectEngineerDataFirst(): void
    {
        $this->logger->display();

        $selector = (new Select($this->orm, Engineer::class))->limit(1);

        $this->assertEquals(static::ENGINEER_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectProgramatorAllData(): void
    {
        $selector = (new Select($this->orm, Programator::class));

        $this->assertEquals(static::PROGRAMATOR_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectProgramatorDataFirst(): void
    {
        $selector = (new Select($this->orm, Programator::class))->limit(1);

        $this->assertEquals(static::PROGRAMATOR_2_LOADED, $selector->fetchData()[0]);
    }

    public function testSelectManagerAllData(): void
    {
        $selector = (new Select($this->orm, Manager::class));

        $this->assertEquals(static::MANAGER_ALL_LOADED, $selector->fetchData());
    }

    public function testSelectManagerDataFirst(): void
    {
        $selector = (new Select($this->orm, Manager::class))->limit(1);

        $this->assertEquals(static::MANAGER_1_LOADED, $selector->fetchData()[0]);
    }
}