<?php

namespace TarsyClub\Framework;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\KernelInterface as BaseKernelInterface;
use Symfony\Component\HttpKernel\RebootableInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

interface KernelInterface extends
    BaseKernelInterface,
    RebootableInterface,
    TerminableInterface
{
    public function getInput(): ?InputInterface;

    /**
     * @param InputInterface|null $input
     *
     * @return static
     */
    public function setInput(?InputInterface $input);

    /**
     * @param array|null $parameters
     *
     * @return static
     */
    public function setParameters(array $parameters);

    public function getParameters(): array;

    public function isAppKernelSilent(): bool;

    /**
     * @param bool|null $appKernelSilent
     *
     * @return static
     */
    public function setAppKernelSilent(?bool $appKernelSilent = true);

    public function isAppRouteSilent(): bool;

    /**
     * @param bool $appVarAvailable
     *
     * @return static
     */
    public function setAppVarAvailable(?bool $appVarAvailable = true);

    public function isAppVarAvailable(): bool;

    /**
     * @param bool $appRouteSilent
     *
     * @return static
     */
    public function setAppRouteSilent(?bool $appRouteSilent = true);

    /**
     * @param string|null $appCacheDir
     *
     * @return static
     */
    public function setAppCacheDir(?string $appCacheDir = null);

    /**
     * @param string|null $appLogDir
     *
     * @return static
     */
    public function setAppLogDir(?string $appLogDir = null);
}
