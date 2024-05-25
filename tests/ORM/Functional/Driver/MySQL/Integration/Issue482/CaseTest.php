<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase;

/**
 * @group driver
 * @group driver-mysql
 */
class CaseTest extends AbstractTestCase
{
    public const DRIVER = 'mysql';

    protected function assertExpectedSql(Select $select): void
    {
        $actual = (string)$select->buildQuery();
        $expected = <<<SQL
SELECT `country`.`id` AS `c0`, `country`.`name` AS `c1`, `country`.`code` AS `c2`, `country`.`is_friendly` AS `c3`
FROM `country` AS `country`
LEFT JOIN `translation` AS `trans`
    ON `trans`.`country_id` = `country`.`id`
LEFT JOIN `translation` AS `transEn`
    ON `transEn`.`country_id` = `country`.`id` AND `transEn`.`locale_id` = 1
WHERE `country`.`is_friendly` = TRUE AND (`country`.`code` LIKE '%eng%' OR `country`.`name` LIKE '%eng%' OR `trans`.`title` LIKE '%eng%'  )
ORDER BY `transEn`.`title` ASC
SQL;
        $this->assertEquals(
            \array_map('trim', \explode($actual, PHP_EOL)),
            \array_map('trim', \explode($expected, PHP_EOL))
        );
    }
}
