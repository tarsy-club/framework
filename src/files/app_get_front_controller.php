<?php

namespace TarsyClub\Framework;

use Exception;

/**
 * @deprecated use front_controller instead
 *
 * @param string $frontController
 * @param array $parameters
 * @param string $env
 * @param bool $useSharedEnv
 *
 * @throws Exception
 *
 * @return int
 */
function app_get_front_controller(string $frontController, array $parameters, string $env, bool $useSharedEnv = true)
{
    return (new FrontController($frontController))(
        $parameters,
        $env,
        $useSharedEnv
    );
}
