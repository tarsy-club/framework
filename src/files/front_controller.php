<?php

namespace TarsyClub\Framework;

use Phar;
use TarsyClub\Framework\Exception\UnknownFrontControllerException;

/**
 * @param array $parameters
 * @param string|null $explicitFrontController
 * @param string|null $dotenvLocation
 * @param bool $useSharedEnv
 *
 * @throws \Exception
 */
function front_controller(
    array $parameters,
    ?string $explicitFrontController = null,
    ?string $dotenvLocation = '.env',
    bool $useSharedEnv = false
) {
    [
        $frontController,
        $dotenvLocation,
    ] = (new RawFrontController())($explicitFrontController, $dotenvLocation);
    $exitCode = (new FrontController($frontController))(
        $parameters,
        $dotenvLocation,
        $useSharedEnv
    );
    if (defined('STDIN')) {
        exit($exitCode);
    }
}

class RawFrontController
{
    public function __invoke(?string $exlicitFrontController = null, ?string $dotenvLocation = '.env')
    {
        if (!($phar = Phar::running()) && (null !== $exlicitFrontController)) {
            $frontController = $exlicitFrontController;
            $dotenvLocation = dirname($frontController) . '/' . $dotenvLocation;
            $frontController = basename($frontController);
        } elseif ($phar) {
            $files = get_included_files();
            $shift = array_search(Phar::running(false), $files);
            $files = array_slice($files, $shift + 1);
            $files = array_filter($files, static function (string $path) use ($phar) {
                return false === strpos($path, $phar . '/.box');
            });
            [$frontController] = array_values($files);
            $dotenvLocation = $phar . '/' . basename($dotenvLocation);
            $frontController = basename($frontController);
        }
        if (
            !($frontController ?? false)
            || !($dotenvLocation ?? false)
            || !FrontController::hasInControllers($frontController)
        ) {
            throw new UnknownFrontControllerException('unknown front controller ' . ($frontController ?? null));
        }

        return [
            $frontController,
            $dotenvLocation,
        ];
    }
}
