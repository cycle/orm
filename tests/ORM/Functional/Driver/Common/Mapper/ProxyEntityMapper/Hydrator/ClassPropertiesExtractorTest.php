<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use PHPUnit\Framework\TestCase;

class ClassPropertiesExtractorTest extends TestCase
{
    private ClassPropertiesExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new ClassPropertiesExtractor();
    }

    public function testPropertyFromBaseClassShouldBeExtracted(): void
    {
        $class = User::class;

        $map = $this->extractor->extract($class, []);

        $this->assertEquals([
            '' => [
                'id' => 'id',
                'comments' => 'comments',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\User' => [
                'username' => 'username',
                'email' => 'email',
            ],
        ], $map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties());

        $this->assertEquals([
        ], $map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties());
    }

    public function testPropertyFromBaseClassWithRelationsShouldBeExtracted(): void
    {
        $class = User::class;

        $map = $this->extractor->extract($class, ['comments']);

        $this->assertEquals([
            '' => [
                'id' => 'id',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\User' => [
                'username' => 'username',
                'email' => 'email',
            ],
        ], $map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties());

        $this->assertEquals([
            '' => [
                'comments' => 'comments',
            ],
        ], $map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties());
    }

    public function testPropertyFromExtendedClassShouldBeExtracted(): void
    {
        $class = SuperUser::class;

        $map = $this->extractor->extract($class, []);

        $this->assertEquals([
            '' => [
                'id' => 'id',
                'age' => 'age',
                'totalLogin' => 'totalLogin',
                'comments' => 'comments',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\User' => [
                'username' => 'username',
                'email' => 'email',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\ExtendedUser' => [
                'isVerified' => 'isVerified',
                'profileId' => 'profileId',
                'tags' => 'tags',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\SuperUser' => [
                'isAdmin' => 'isAdmin',
            ],
        ], $map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties());

        $this->assertEquals([
        ], $map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties());
    }

    public function testPropertyFromExtendedClassWithRelationsShouldBeExtracted(): void
    {
        $class = SuperUser::class;

        $map = $this->extractor->extract($class, ['comments', 'tags']);

        $this->assertEquals([
            '' => [
                'id' => 'id',
                'age' => 'age',
                'totalLogin' => 'totalLogin',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\User' => [
                'username' => 'username',
                'email' => 'email',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\ExtendedUser' => [
                'isVerified' => 'isVerified',
                'profileId' => 'profileId',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\SuperUser' => [
                'isAdmin' => 'isAdmin',
            ],
        ], $map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties());

        $this->assertEquals([
            '' => [
                'comments' => 'comments',
            ],
            'Cycle\ORM\Tests\Mapper\ProxyEntityMapper\Hydrator\ExtendedUser' => [
                'tags' => 'tags',
            ],
        ], $map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties());
    }
}


class User
{
    public int $id;
    public array $comments;
    protected string $username;
    private string $email;
}

class ExtendedUser extends User
{
    public int $age;
    protected bool $isVerified;
    private int $profileId;
    private array $tags;
}

class SuperUser extends ExtendedUser
{
    public int $totalLogin;
    private int $isAdmin;
}
