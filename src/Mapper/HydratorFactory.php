<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use CodeGenerationUtils\Exception\InvalidGeneratedClassesDirectoryException;
use CodeGenerationUtils\Visitor\ClassRenamerVisitor;
use Cycle\ORM\Mapper\Hydrator\Configuration;
use GeneratedHydrator\GeneratedHydrator;
use GeneratedHydratorTestAsset\HydratedObject;
use Laminas\Hydrator\HydratorInterface;
use PhpParser\NodeTraverser;
use ReflectionClass;

class HydratorFactory
{
    private \GeneratedHydrator\Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;

        $this->includeGeneratedHydrators();
    }

    /**
     * Create a new hydrator for given entity class
     */
    public function create(string $class): HydratorInterface
    {
        $this->config->setHydratedClassName($class);
        $hydratorClass = $this->getHydratorClass();

        return new $hydratorClass();
    }

    /**
     * Retrieves the generated hydrator FQCN
     *
     * @throws InvalidGeneratedClassesDirectoryException
     * @psalm-return class-string<GeneratedHydrator<HydratedObject>>
     */
    private function getHydratorClass(): string
    {
        $inflector = $this->config->getClassNameInflector();
        /** @psalm-var class-string $realClassName */
        $realClassName = $inflector->getUserClassName($this->config->getHydratedClassName());

        /** @psalm-var class-string<GeneratedHydrator<HydratedObject>> $hydratorClassName */
        $hydratorClassName = $inflector->getGeneratedClassName($realClassName, ['factory' => static::class]);

        if (!class_exists($hydratorClassName) && $this->config->doesAutoGenerateProxies()) {
            $generator = $this->config->getHydratorGenerator();
            $originalClass = new ReflectionClass($realClassName);
            $generatedAst = $generator->generate($originalClass);
            $traverser = new NodeTraverser();

            $traverser->addVisitor(new ClassRenamerVisitor($originalClass, $hydratorClassName));

            $this->config->getGeneratorStrategy()->generate($traverser->traverse($generatedAst));
            $this->config->getGeneratedClassAutoloader()->__invoke($hydratorClassName);
        }

        return $hydratorClassName;
    }

    /**
     * Scan generated hydrators inside target dir specified in the configuration
     * and include them
     */
    private function includeGeneratedHydrators(): void
    {
        $dir = scandir($this->config->getGeneratedClassesTargetDir());

        foreach ($dir as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
                continue;
            }

            include_once $this->config->getGeneratedClassesTargetDir() . DIRECTORY_SEPARATOR . $file;
        }
    }
}