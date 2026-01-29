<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase;

/**
 * @group driver
 * @group driver-mysql
 */
class CaseTest extends AbstractTestCase
{
    public const DRIVER = 'mysql';

    protected function getExpectedSql(): string
    {
        return <<<SQL
            SELECT `country`.`id` AS `c0`, `country`.`name` AS `c1`, `country`.`code` AS `c2`, `country`.`is_friendly` AS `c3`, `trans`.`id` AS `c4`, `trans`.`country_id` AS `c5`, `trans`.`locale_id`
            AS `c6`, `trans`.`title` AS `c7`
            FROM `country` AS `country`
            LEFT JOIN `translation` AS `trans`
                ON `trans`.`country_id` = `country`.`id`
            LEFT JOIN `translation` AS `transEn`
                ON `transEn`.`country_id` = `country`.`id` AND `transEn`.`locale_id` = 1
            WHERE `country`.`is_friendly` = TRUE AND (`country`.`code` LIKE '%eng%' OR `country`.`name` LIKE '%eng%' OR `trans`.`title` LIKE '%eng%'  )
            ORDER BY `transEn`.`title` ASC
            SQL;
    }
}
