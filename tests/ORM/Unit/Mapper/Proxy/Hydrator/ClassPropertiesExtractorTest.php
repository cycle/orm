<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\Proxy\Hydrator;

use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Tests\Fixtures\AsymmetricVisibilityEntity;
use PHPUnit\Framework\TestCase;

class ClassPropertiesExtractorTest extends TestCase
{
    private ClassPropertiesExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new ClassPropertiesExtractor();
    }

    public function testPublicPropertyClassifiedAsPublic(): void
    {
        $result = $this->extractor->extract(AsymmetricVisibilityEntity::class, []);
        $fieldMap = $result[ClassPropertiesExtractor::KEY_FIELDS];

        $this->assertTrue($fieldMap->isPublicProperty('id'));
        $this->assertTrue($fieldMap->isPublicProperty('name'));
    }

    public function testPrivateSetPropertyClassifiedAsNonPublic(): void
    {
        $result = $this->extractor->extract(AsymmetricVisibilityEntity::class, []);
        $fieldMap = $result[ClassPropertiesExtractor::KEY_FIELDS];

        // private(set) has public read but private write — must NOT be PUBLIC_CLASS
        $this->assertFalse($fieldMap->isPublicProperty('login'));
        $this->assertSame(
            AsymmetricVisibilityEntity::class,
            $fieldMap->getPropertyClass('login'),
        );
    }

    public function testProtectedSetPropertyClassifiedAsNonPublic(): void
    {
        $result = $this->extractor->extract(AsymmetricVisibilityEntity::class, []);
        $fieldMap = $result[ClassPropertiesExtractor::KEY_FIELDS];

        // protected(set) has public read but protected write — must NOT be PUBLIC_CLASS
        $this->assertFalse($fieldMap->isPublicProperty('email'));
        $this->assertSame(
            AsymmetricVisibilityEntity::class,
            $fieldMap->getPropertyClass('email'),
        );
    }

    public function testPrivatePropertyClassifiedAsNonPublic(): void
    {
        $result = $this->extractor->extract(AsymmetricVisibilityEntity::class, []);
        $fieldMap = $result[ClassPropertiesExtractor::KEY_FIELDS];

        $this->assertFalse($fieldMap->isPublicProperty('password'));
        $this->assertSame(
            AsymmetricVisibilityEntity::class,
            $fieldMap->getPropertyClass('password'),
        );
    }

    public function testRelationPropertiesRespectAsymmetricVisibility(): void
    {
        $result = $this->extractor->extract(
            AsymmetricVisibilityEntity::class,
            ['login'],
        );

        $fieldMap = $result[ClassPropertiesExtractor::KEY_FIELDS];
        $relationMap = $result[ClassPropertiesExtractor::KEY_RELATIONS];

        // login is a relation — not in fields
        $this->assertNull($fieldMap->getPropertyClass('login'));

        // login (private(set)) in relations — must be class-scoped, not PUBLIC_CLASS
        $this->assertFalse($relationMap->isPublicProperty('login'));
        $this->assertSame(
            AsymmetricVisibilityEntity::class,
            $relationMap->getPropertyClass('login'),
        );
    }
}
