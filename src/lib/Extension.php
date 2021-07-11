<?php

namespace TarsyClub\Framework;

use Exception;
use RuntimeException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;
use TarsyClub\Framework\Exception\FileNotFoundException;
use TarsyClub\Framework\Exception\MetadataNotFoundException;

class Extension extends BaseExtension implements BundleOptionsAwareInterface
{
    use BundleOptionsAwareTrait;

    public const LOAD_DEPTH_ONE = 0b1;
    public const LOAD_DEPTH_TWO = 0b10;
    public const LOAD_DEPTH_THREE = 0b100;
    public const LOAD_DEPTH_FULL = self::LOAD_DEPTH_ONE | self::LOAD_DEPTH_TWO | self::LOAD_DEPTH_THREE;

    public const CONFIG_EXTS = '.yml';

    public function __construct(string $bundle, string $alias)
    {
        $this->setBundle($bundle);
        $this->setAlias($alias);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->_load(
            $container,
            $this->getRoot($container),
            'service',
            self::LOAD_DEPTH_FULL,
            $container->getParameter('kernel.environment')
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string $root
     * @param string $configName
     * @param int|null $depth
     * @param string|null $env
     *
     * @throws Exception
     */
    protected function _load(
        ContainerBuilder $container,
        string $root,
        $configName,
        int $depth = null,
        ?string $env = null
    ) {
        try {
            $loader = new YamlFileLoader($container, new FileLocator($root));
            $this->loadConfigs($loader, $configName, $depth, $env);
        } catch (RuntimeException $exception) {
            // do nothing
        }
    }

    protected function getRoot(ContainerBuilder $container): string
    {
        if (!($container->getParameter('kernel.bundles_metadata')[$this->getBundle()] ?? false)) {
            throw new MetadataNotFoundException($this->getBundle() . ' metadata not found');
        }
        $root = sprintf(
            '%s/../../../resources/bundles/%s/config',
            $container->getParameter('kernel.bundles_metadata')[$this->getBundle()]['path'],
            $this->getBundle()
        );
        if (!file_exists($root)) {
            throw new FileNotFoundException($this->getBundle() . ' resources not found');
        }

        return $root;
    }

    /**
     * @param LoaderInterface $loader
     * @param string $root
     * @param string $configName
     * @param int|null $depth
     * @param string|null $env
     *
     * @throws Exception
     */
    protected function loadConfigs(
        LoaderInterface $loader,
        string $configName,
        ?int $depth = self::LOAD_DEPTH_FULL,
        ?string $env = null
    ): void {
        $paths = [];
        if ($depth & self::LOAD_DEPTH_ONE) {
            $paths[] = $configName . self::CONFIG_EXTS;
        }
        if ($depth & self::LOAD_DEPTH_TWO) {
            $paths[] = $configName . '/' . $configName . self::CONFIG_EXTS;
        }
        if ($depth & self::LOAD_DEPTH_THREE) {
            $paths[] = $configName . '/' . $env . '/' . $configName . self::CONFIG_EXTS;
        }

        foreach ($paths as $path) {
            try {
                $loader->load($path);
            } catch (FileLocatorFileNotFoundException $e) {
                // skip if resource not found
            }
        }
    }
}
