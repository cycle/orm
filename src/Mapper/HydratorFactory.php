<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use CodeGenerationUtils\Exception\InvalidGeneratedClassesDirectoryException;
use CodeGenerationUtils\FileLocator\FileLocator;
use CodeGenerationUtils\GeneratorStrategy\FileWriterGeneratorStrategy;
use CodeGenerationUtils\GeneratorStrategy\GeneratorStrategyInterface;
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

    public function __construct(?GeneratorStrategyInterface $strategy = null)
    {
        $strategy = $strategy ?? new FileWriterGeneratorStrategy(
            new FileLocator(
                sys_get_temp_dir()
            )
        );

        $this->config = new Configuration();
        $this->config->setGeneratorStrategy($strategy);
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
}