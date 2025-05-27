<?php

namespace Dktaylor\BundleGeneratorBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DktaylorBundleGeneratorBundle extends AbstractBundle
{
    protected string $extensionAlias = 'bundle_generator';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.xml');

        // Modify services...
    }

    public function build(ContainerBuilder $container): void
    {
        // Uncomment the following two lines to enable doctrine mappings
        // Place doctrine xml files into {project_dir}/config/doctrine folder
        //parent::build($container);
        //$this->addDoctrineOrmCompilerPass($container);

        // Add other tasks below here

    }

    private function addDoctrineOrmCompilerPass(ContainerBuilder $container): void
    {
        $ormCompilerClass = DoctrineOrmMappingsPass::class;

        if (class_exists($ormCompilerClass)) {
            $namespaces = ['Dktaylor\BundleGeneratorBundle'];
            $directories = [
                // Change this to the directory where the bundle ORM models will be
                realpath(__DIR__ . '/ORM/Model')
            ];
            $manageParameters = [];
            $container->addCompilerPass(
                new DoctrineOrmMappingsPass(
                    new Definition(AttributeDriver::class, [$directories]),
                    $namespaces,
                    $manageParameters
                )
            );
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        // Build the bundle configuration here
    }
}
