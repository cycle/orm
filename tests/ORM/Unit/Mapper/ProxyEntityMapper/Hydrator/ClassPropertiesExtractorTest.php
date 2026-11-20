<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\ProxyEntityMapper\Hydrator;

use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class ClassPropertiesExtractorTest
{
    private ClassPropertiesExtractor $extractor;

    public function testPropertyFromBaseClassShouldBeExtracted(): void
    {
        $class = User::class;

        $map = $this->extractor->extract($class, []);

        Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
            '' => [
                'id' => 'id',
                'comments' => 'comments',
            ],
            User::class => [
                'username' => 'username',
                'email' => 'email',
            ],
        ]);

        Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
        ]);
    }

    public function testPropertyFromBaseClassWithRelationsShouldBeExtracted(): void
    {
        $class = User::class;

        $map = $this->extractor->extract($class, ['comments']);

        Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
            '' => [
                'id' => 'id',
            ],
            User::class => [
                'username' => 'username',
                'email' => 'email',
            ],
        ]);

        Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
            '' => [
                'comments' => 'comments',
            ],
        ]);
    }

    public function testPropertyFromExtendedClassShouldBeExtracted(): void
    {
        $class = SuperUser::class;

        $map = $this->extractor->extract($class, []);

        Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
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

        Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
        ]);
    }

    public function testPropertyFromExtendedClassWithRelationsShouldBeExtracted(): void
    {
        $class = SuperUser::class;

        $map = $this->extractor->extract($class, ['comments', 'tags']);

        Assert::equals($map[ClassPropertiesExtractor::KEY_FIELDS]->getProperties(), [
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

        Assert::equals($map[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties(), [
            '' => [
                'comments' => 'comments',
            ],
            ExtendedUser::class => [
                'tags' => 'tags',
            ],
        ]);
    }

    #[BeforeTest]
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
