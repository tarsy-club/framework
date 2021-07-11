<?php

namespace TarsyClub\Framework;

use Exception;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use TarsyClub\Framework\Exception\ClassNotFoundException;
use Throwable;

class Kernel extends BaseKernel implements KernelInterface
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{yml}';

    /**
     * @var string
     */
    private $appDir;

    /**
     * @var InputInterface|null
     */
    private $input;
    private $parameters = [];
    private $appKernelSilent = true;
    private $appRouteSilent = true;
    private $appVarAvailable = true;

    /**
     * @var string|null
     */
    private $appCacheDir;

    /**
     * @var string|null
     */
    private $appLogDir;

    /**
     * @var string|null
     */
    private $projectDir;

    /**
     * @var string|null
     */
    private $warmupDir;

    /**
     * @return string|null
     */
    public function getAppCacheDir(): ?string
    {
        return $this->appCacheDir;
    }

    /**
     * @param string|null $appCacheDir
     *
     * @return static
     */
    public function setAppCacheDir(?string $appCacheDir = null)
    {
        $this->appCacheDir = $appCacheDir;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAppLogDir(): ?string
    {
        return $this->appLogDir;
    }

    /**
     * @param string|null $appLogDir
     *
     * @return static
     */
    public function setAppLogDir(?string $appLogDir = null)
    {
        $this->appLogDir = $appLogDir;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAppVarAvailable(): bool
    {
        return $this->appVarAvailable;
    }

    /**
     * @param bool $appVarAvailable
     *
     * @return static
     */
    public function setAppVarAvailable(?bool $appVarAvailable = true)
    {
        $this->appVarAvailable = $appVarAvailable;

        return $this;
    }

    public function getAppDir(): ?string
    {
        return $this->appDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir()
    {
        if (null === $this->projectDir) {
            $this->projectDir = $this->isAppVarAvailable()
                ? $this->getAppDir()
                : $this->getPharAppDir();
        }

        return $this->projectDir;
    }

    /**
     * @param string $appDir
     * @param bool|null $appVarAvailable
     *
     * @return static
     */
    public function setAppDir(string $appDir, ?bool $appVarAvailable = true)
    {
        $this->appDir = $appVarAvailable && ($phar = \Phar::running(false))
            ? \dirname($phar)
            : $appDir;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->getProjectDir() . '/' . ((null !== ($cacheDir = $this->getAppCacheDir()))
                ? $cacheDir
                : $this->getDefaultCacheDir());
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getProjectDir() . '/' . ((null !== ($logDir = $this->getAppLogDir()))
                ? $logDir
                : $this->getDefaultLogDir());
    }

    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    public function setInput(?InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        foreach ($this->getParameters() as $bundle => $parameters) {
            if ((bool) ($parameters['envs']['all'] ?? $parameters['envs'][$this->getEnvironment()] ?? false)) {
                yield new $bundle();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAppKernelSilent(): bool
    {
        return $this->appKernelSilent;
    }

    /**
     * {@inheritdoc}
     */
    public function setAppKernelSilent(?bool $appKernelSilent = true)
    {
        $this->appKernelSilent = $appKernelSilent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isAppRouteSilent(): bool
    {
        return $this->appRouteSilent;
    }

    /**
     * {@inheritdoc}
     */
    public function setAppRouteSilent(?bool $appRouteSilent = true)
    {
        $this->appRouteSilent = $appRouteSilent;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->setParameter('kernel.project_dir', $this->getAppDir());
        $this->loadExtensionDefaults($container);
        $this->loadFiles($loader);
    }

    /**
     * {@inheritdoc}
     *
     * @TODO: https://symfony.com/doc/current/routing/custom_route_loader.html
     *
     * @throws LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $pattern = '%1$s/resources/bundles/%2$s/%3$s';
        $paths = [
            'routes' . static::CONFIG_EXTS,
            'routes/*' . static::CONFIG_EXTS,
            'routes/' . $this->getEnvironment() . '/*' . static::CONFIG_EXTS,
        ];
        $bundles = [];
        foreach ($this->getParameters() as $bundle => $parameters) {
            while (true) {
                if (null === ($bundleShortName = $this->getBundleShortName($bundle))) {
                    break;
                }
                if (!($this->getBundles()[$bundleShortName] ?? false)) {
                    break;
                }
                if (!($parameters['routes'] ?? false)) {
                    break;
                }
                $bundles[$bundle] = [
                    'name' => $bundleShortName,
                    'prefix' => is_string($parameters['routes']) ? $parameters['routes'] : '/',
                ];
                break;
            }
        }
        foreach ($bundles as $bundle) {
            foreach ($paths as $path) {
                try {
                    $routes->import(sprintf(
                        $pattern,
                        $this->getAppDir(),
                        $bundle['name'],
                        $path
                    ), $bundle['prefix'], 'glob');
                } catch (LoaderLoadException | FileLocatorFileNotFoundException $exception) {
                    if (!$this->isAppRouteSilent()) {
                        throw $exception;
                    }
                    // otherwise skip if resource not found
                }
            }
        }
    }

    /**
     * @NOTE: modified parent::initializeContainer
     *
     * @throws ReflectionException
     */
    protected function initializeContainer()
    {
        $fresh = false;
        if (($pharFile = \Phar::running(false))) {
            if ($this->isDebug()) {
                $this->tryToExtractDebugFiles($pharFile);
            } else {
                $fresh = true;
            }
        }
        if ($fresh) {
            $this->setContainerKernel();
        } else {
            parent::initializeContainer();
        }
    }

    private function getDefaultCacheDir()
    {
        return 'var/cache/' . $this->getEnvironment();
    }

    private function getDefaultLogDir()
    {
        return 'var/log';
    }

    private function tryToExtractDebugFiles(string $pharFile): void
    {
        if (!\file_exists(\realpath($this->getCacheDir()))) {
            $phar = new \Phar($pharFile);
            $phar->extractTo($this->getProjectDir(), $this->getDefaultCacheDir() . '/', true);
            $phar->extractTo($this->getProjectDir(), 'resources' . '/', true);
        }
    }

    /**
     * @NOTE: part of parent::initializeContainer method
     */
    private function setContainerKernel(): void
    {
        $class = $this->getContainerClass();
        $cacheDir = $this->warmupDir ?: $this->getCacheDir();
        $cache = new ConfigCache($cacheDir . '/' . $class . '.php', $this->isDebug());
        $errorLevel = error_reporting(\E_ALL ^ \E_WARNING);
        try {
            if (file_exists($cache->getPath()) && \is_object($this->container = include $cache->getPath())) {
                $this->container->set('kernel', $this);
            }
        } catch (Throwable $e) {
            // do nothing
        } finally {
            error_reporting($errorLevel);
        }
    }

    private function getPharAppDir()
    {
        return ($path = \Phar::running()) ? $path : $this->getAppDir();
    }

    private function getBundleShortName(string $bundle): ?string
    {
        $result = null;
        try {
            $result = class_exists($bundle) ? (new ReflectionClass($bundle))->getShortName() : $result;
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException($bundle . ' class not found');
        }

        return $result;
    }

    private function loadFiles(LoaderInterface $loader): void
    {
        $basePath = $this->getAppDir() . '/resources/kernel/config';
        $paths[] = $basePath . '/parameter' . static::CONFIG_EXTS;
        $paths[] = $basePath . '/parameter/*' . static::CONFIG_EXTS;
        $paths[] = $basePath . '/parameter/' . $this->getEnvironment() . '/*' . static::CONFIG_EXTS;
        $paths[] = $basePath . '/vendor' . static::CONFIG_EXTS;
        $paths[] = $basePath . '/vendor/*' . static::CONFIG_EXTS;
        $paths[] = $basePath . '/vendor/' . $this->getEnvironment() . '/*' . static::CONFIG_EXTS;

        foreach ($paths as $path) {
            try {
                $loader->load($path, 'glob');
            } catch (FileLocatorFileNotFoundException $exception) {
                if (!$this->isAppKernelSilent()) {
                    throw $exception;
                }
                // otherwise skip if resource not found
            }
        }
    }

    private function loadExtensionDefaults(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'session' => [
                'gc_probability' => 0,
            ],
        ]);
    }
}
