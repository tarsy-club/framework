<?php

namespace TarsyClub\Framework;

use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpFoundation\Request;
use TarsyClub\Framework\Exception\UnknownFrontControllerException;

class FrontController
{
    private const FRONT_CONTROLLER_CONSOLE = 'console.php';
    private const FRONT_CONTROLLER_WEB = 'index.php';
    private const APP_ENV_DEV = 'dev';
    private const APP_ENV_LOCAL = 'local';

    /**
     * @var string|null
     */
    private $frontController;

    public function __construct(string $frontController)
    {
        $this->frontController = $frontController;
    }

    /**
     * @param array $parameters
     * @param string $env
     * @param bool $useSharedEnv
     *
     * @throws Exception
     *
     * @return int
     */
    public function __invoke(array $parameters, string $env, bool $useSharedEnv = true): int
    {
        if (!$this->isConsole() && !$this->isWeb()) {
            throw new UnknownFrontControllerException('Unknown front controller ' . $this->frontController);
        }
        $appEnv = array_combine([
            'APP_KERNEL_SILENT',
            'APP_ROUTE_SILENT',
            'APP_CACHE_DIR',
            'APP_LOG_DIR',
            'APP_VAR_AVAILABLE',
        ], $this->processEnv($env, $useSharedEnv));
        $kernel = $this->buildKernel(dirname($env), $parameters, $appEnv);

        return $this->isConsole()
            ? $this->invokeConsoleApplication($kernel)
            : $this->invokeWebApplication($kernel);
    }

    public static function hasInControllers(string $frontController)
    {
        return in_array($frontController, [
            self::FRONT_CONTROLLER_CONSOLE,
            self::FRONT_CONTROLLER_WEB,
        ]);
    }

    private function getDefaultAppEnv()
    {
        return (false !== ($env = getenv('APP_ENV')))
            ? $env
            : self::APP_ENV_LOCAL;
    }

    /**
     * @param string $env
     * @param bool $useSharedEnv
     *
     * @return array
     */
    private function processEnv(string $env, bool $useSharedEnv = true): array
    {
        $envs = [];
        $envs[] = $env;
        if ($useSharedEnv) {
            $envs[] = function (): string {
                @trigger_error('Do not use shared .env file. Please, consider the boxed ci', E_USER_DEPRECATED);

                return '/etc/share/www/current/resources/app/' . getenv('APP_NAME') . '/' . getenv('APP_ENV') . '/.env';
            };
        }
        if (class_exists('\\Symfony\\Component\\Dotenv\\Dotenv')) {
            $dotEnv = new \Symfony\Component\Dotenv\Dotenv();
            array_map(function ($env) use ($dotEnv) {
                try {
                    $dotEnv->load(is_callable($env)
                        ? $env()
                        : $env);
                } catch (\Symfony\Component\Dotenv\Exception\PathException $e) {
                    // if file is not available then just try to load others
                }
            }, $envs);
        }
        $APP_KERNEL_SILENT = (bool) (false !== ($APP_KERNEL_SILENT = getenv('APP_KERNEL_SILENT'))
            ? $APP_KERNEL_SILENT
            : true);
        $APP_ROUTE_SILENT = (bool) (false !== ($APP_ROUTE_SILENT = getenv('APP_ROUTE_SILENT'))
            ? $APP_ROUTE_SILENT
            : true);
        $APP_CACHE_DIR = (false !== ($APP_CACHE_DIR = getenv('APP_CACHE_DIR'))
            ? $APP_CACHE_DIR
            : null);
        $APP_LOG_DIR = (false !== ($APP_LOG_DIR = getenv('APP_LOG_DIR'))
            ? $APP_LOG_DIR
            : null);
        $APP_VAR_AVAILABLE = (bool) (false !== ($APP_VAR_AVAILABLE = getenv('APP_VAR_AVAILABLE'))
            ? $APP_VAR_AVAILABLE
            : true);

        return [
            $APP_KERNEL_SILENT,
            $APP_ROUTE_SILENT,
            $APP_CACHE_DIR,
            $APP_LOG_DIR,
            $APP_VAR_AVAILABLE,
        ];
    }

    private function isDefaultDebug(string $environment): bool
    {
        return isset($_SERVER['APP_DEBUG'])
            ? (bool) $_SERVER['APP_DEBUG']
            : in_array($environment, [static::APP_ENV_DEV, static::APP_ENV_LOCAL], true);
    }

    /**
     * @param KernelInterface $kernel
     *
     * @throws Exception
     *
     * @return int
     */
    private function invokeConsoleApplication(KernelInterface $kernel): int
    {
        return (new Application($kernel))
            ->run($kernel->getInput())
            ;
    }

    private function invokeWebApplication(KernelInterface $kernel): int
    {
        return (function (KernelInterface $kernel) {
            if (!empty($trustedProxies = ($_SERVER['TRUSTED_PROXIES'] ?? ''))) {
                Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
            }
            if (!empty($trustedHosts = ($_SERVER['TRUSTED_HOSTS'] ?? ''))) {
                Request::setTrustedHosts(explode(',', $trustedHosts));
            }
            $request = Request::createFromGlobals();
            $response = $kernel->handle($request);
            $response->send();
            $kernel->terminate($request, $response);

            return 0;
        })($kernel);
    }

    private function buildKernel(string $appDir, array $parameters, array $appEnv = [])
    {
        return ($this->isConsole()
            ? $this->buildConsoleKernel($appDir, $parameters, $appEnv)
            : $this->buildWebKernel($appDir, $parameters, $appEnv))
            ->setAppKernelSilent($appEnv['APP_KERNEL_SILENT'])
            ->setAppRouteSilent($appEnv['APP_ROUTE_SILENT'])
            ->setAppCacheDir($appEnv['APP_CACHE_DIR'])
            ->setAppLogDir($appEnv['APP_LOG_DIR'])
            ->setAppVarAvailable($appEnv['APP_VAR_AVAILABLE'])
            ;
    }

    private function isConsole(): bool
    {
        return static::FRONT_CONTROLLER_CONSOLE === $this->frontController;
    }

    private function isWeb(): bool
    {
        return static::FRONT_CONTROLLER_WEB === $this->frontController;
    }

    private function buildConsoleKernel(string $appDir, array $parameters, array $appEnv = [])
    {
        set_time_limit(0); // @TODO: https://itmoru.atlassian.net/browse/INT-378
        $input = new ArgvInput();
        $environment = $input->getParameterOption(['--env', '-e'], $_SERVER['APP_ENV'] ?? $this->getDefaultAppEnv(), true);
        if (\Phar::running()) {
            $debug = $this->isDebug($appEnv, $environment)
                && !$input->hasParameterOption('--no-debug', true);
        } else {
            $debug = $this->isDefaultDebug($environment)
                && !$input->hasParameterOption('--no-debug', true);
        }
        $this->tryToEnableDebug($debug);

        return $this->_buildKernel($environment, $debug, $appDir, $parameters, $appEnv)
            ->setInput($input)
            ;
    }

    private function isDebug(array $appEnv, string $environment)
    {
        return ($appEnv['APP_VAR_AVAILABLE'] ?? false)
            && $this->isDefaultDebug($environment);
    }

    private function buildWebKernel(string $appDir, array $parameters, array $appEnv = [])
    {
        $environment = $_SERVER['APP_ENV'] ?? $this->getDefaultAppEnv();
        if (\Phar::running()) {
            $debug = $this->isDebug($appEnv, $environment);
        } else {
            $debug = $this->isDefaultDebug($environment);
        }
        $this->tryToEnableDebug($debug);

        return $this->_buildKernel($environment, $debug, $appDir, $parameters, $appEnv);
    }

    private function _buildKernel(string $environment, bool $debug, string $appDir, array $parameters, ?array $appEnv = [])
    {
        return (new Kernel($environment, $debug))
            ->setAppDir($appDir, $appEnv['APP_VAR_AVAILABLE'] ?? false)
            ->setParameters($parameters)
            ;
    }

    private function tryToEnableDebug(bool $debug): void
    {
        if ($debug) {
            if (class_exists($class = '\\Symfony\\Component\\ErrorHandler\\Debug')) {
                // do nothing
            } elseif (class_exists($class = '\\Symfony\\Component\\Debug\\Debug')) {
                // do nothing;
            }
            if (null !== $class) {
                umask(0000);
                $class::enable();
            }
        }
    }
}
