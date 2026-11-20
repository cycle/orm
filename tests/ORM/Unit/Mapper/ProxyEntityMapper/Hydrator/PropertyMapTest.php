<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\Mapper\Proxy\Hydrator\PropertyMap;
use PHPUnit\Framework\TestCase;

class PropertyMapTest
{
    private PropertyMap $properties;

    #[\Testo\Test]
    public function testGetsPropertyClass(): void
    {
        \Testo\Assert::equals($this->properties->getPropertyClass('id'), '');
        \Testo\Assert::equals($this->properties->getPropertyClass('username'), '');

        \Testo\Assert::equals($this->properties->getPropertyClass('is_verified'), 'Test\User\ExtendedUser');
        \Testo\Assert::equals($this->properties->getPropertyClass('has_avatar'), 'Test\User\ExtendedUser');

        \Testo\Assert::equals($this->properties->getPropertyClass('is_admin'), 'Test\User\SuperUser');
        \Testo\Assert::equals($this->properties->getPropertyClass('is_blocked'), 'Test\User\SuperUser');
    }

    #[\Testo\Test]
    public function testCheckPropertyPublicity(): void
    {
        \Testo\Assert::true($this->properties->isPublicProperty('id'));
        \Testo\Assert::true($this->properties->isPublicProperty('username'));

        \Testo\Assert::false($this->properties->isPublicProperty('is_verified'));
        \Testo\Assert::false($this->properties->isPublicProperty('has_avatar'));

        \Testo\Assert::false($this->properties->isPublicProperty('is_admin'));
        \Testo\Assert::false($this->properties->isPublicProperty('is_blocked'));
    }

    #[\Testo\Test]
    public function testGetsProperties(): void
    {
        \Testo\Assert::array($this->properties->getProperties());
    }

    #[\Testo\Test]
    public function testGetsClass(): void
    {
        \Testo\Assert::equals($this->properties->getClass(), 'User');
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->properties = new PropertyMap('User', [
            '' => [
                'id' => 'id',
                'username' => 'username',
            ],
            'Test\User\ExtendedUser' => [
                'is_verified' => 'is_verified',
                'has_avatar' => 'has_avatar',
            ],
            'Test\User\SuperUser' => [
                'is_admin' => 'is_admin',
                'is_blocked' => 'is_blocked',
            ],
        ]);
    }
}
