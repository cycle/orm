<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\Mapper\Proxy\Hydrator\PropertyMap;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class PropertyMapTest
{
    private PropertyMap $properties;

    public function testGetsPropertyClass(): void
    {
        Assert::equals($this->properties->getPropertyClass('id'), '');
        Assert::equals($this->properties->getPropertyClass('username'), '');

        Assert::equals($this->properties->getPropertyClass('is_verified'), 'Test\User\ExtendedUser');
        Assert::equals($this->properties->getPropertyClass('has_avatar'), 'Test\User\ExtendedUser');

        Assert::equals($this->properties->getPropertyClass('is_admin'), 'Test\User\SuperUser');
        Assert::equals($this->properties->getPropertyClass('is_blocked'), 'Test\User\SuperUser');
    }

    public function testCheckPropertyPublicity(): void
    {
        Assert::true($this->properties->isPublicProperty('id'));
        Assert::true($this->properties->isPublicProperty('username'));

        Assert::false($this->properties->isPublicProperty('is_verified'));
        Assert::false($this->properties->isPublicProperty('has_avatar'));

        Assert::false($this->properties->isPublicProperty('is_admin'));
        Assert::false($this->properties->isPublicProperty('is_blocked'));
    }

    public function testGetsProperties(): void
    {
        Assert::array($this->properties->getProperties());
    }

    public function testGetsClass(): void
    {
        Assert::equals($this->properties->getClass(), 'User');
    }

    #[BeforeTest]
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
