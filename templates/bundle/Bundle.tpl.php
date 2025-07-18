<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name; ?> extends AbstractBundle
{
    protected string $extensionAlias = '<?= $extension_alias; ?>';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.xml');

        // Modify services...
    }

    public function build(ContainerBuilder $container): void
    {
        $this->addDoctrineOrmCompilerPass($container);

        // Add other tasks...
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        // Build the bundle configuration here
    }

    private function addDoctrineOrmCompilerPass(ContainerBuilder $container): void
    {
        $ormCompilerClass = "Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass";

        if (class_exists($ormCompilerClass)) {
            $namespaces = ['<?= $namespace; ?>'];
            $directories = [
                // Change this to the directory where the bundle ORM models will reside
                realpath(__DIR__ . '/ORM/Model')
            ];
            $manageParameters = [];
            $container->addCompilerPass(
                new $ormCompilerClass(
                    new Definition(AttributeDriver::class, [$directories]),
                    $namespaces,
                    $manageParameters
                )
            );
        }
    }
}
