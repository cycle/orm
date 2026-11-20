<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use PHPUnit\Framework\TestCase;

class ClassPropertiesExtractorTest
{
    private ClassPropertiesExtractor $extractor;

    #[\Testo\Test]
    public function testPropertyFromBaseClassShouldBeExtracted(): void
    {
        $class = User::class;

        $map = $this->extractor->extract($class, []);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
            '' => [
                'id' => 'id',
                'comments' => 'comments',
            ],
            User::class => [
                'username' => 'username',
                'email' => 'email',
            ],
        ]);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
        ]);
    }

    #[\Testo\Test]
    public function testPropertyFromBaseClassWithRelationsShouldBeExtracted(): void
    {
        $class = User::class;

        $map = $this->extractor->extract($class, ['comments']);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
            '' => [
                'id' => 'id',
            ],
            User::class => [
                'username' => 'username',
                'email' => 'email',
            ],
        ]);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
            '' => [
                'comments' => 'comments',
            ],
        ]);
    }

    #[\Testo\Test]
    public function testPropertyFromExtendedClassShouldBeExtracted(): void
    {
        $class = SuperUser::class;

        $map = $this->extractor->extract($class, []);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
            '' => [
                'id' => 'id',
                'age' => 'age',
                'totalLogin' => 'totalLogin',
                'comments' => 'comments',
            ],
            User::class => [
                'username' => 'username',
                'email' => 'email',
            ],
            ExtendedUser::class => [
                'isVerified' => 'isVerified',
                'profileId' => 'profileId',
                'tags' => 'tags',
            ],
            SuperUser::class => [
                'isAdmin' => 'isAdmin',
            ],
        ]);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
        ]);
    }

    #[\Testo\Test]
    public function testPropertyFromExtendedClassWithRelationsShouldBeExtracted(): void
    {
        $class = SuperUser::class;

        $map = $this->extractor->extract($class, ['comments', 'tags']);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
            '' => [
                'id' => 'id',
                'age' => 'age',
                'totalLogin' => 'totalLogin',
            ],
            User::class => [
                'username' => 'username',
                'email' => 'email',
            ],
            ExtendedUser::class => [
                'isVerified' => 'isVerified',
                'profileId' => 'profileId',
            ],
            SuperUser::class => [
                'isAdmin' => 'isAdmin',
            ],
        ]);

        \Testo\Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
            '' => [
                'comments' => 'comments',
            ],
            ExtendedUser::class => [
                'tags' => 'tags',
            ],
        ]);
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->extractor = new ClassPropertiesExtractor();
    }
}

// phpcs:disable
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
// phpcs:enable
