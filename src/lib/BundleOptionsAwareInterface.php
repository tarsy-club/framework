<?php

namespace TarsyClub\Framework;

interface BundleOptionsAwareInterface
{
    /**
     * @return string|null
     */
    public function getAlias();

    /**
     * @return string|null
     */
    public function getBundle(): ?string;

    /**
     * @param string|null $bundle
     *
     * @return static
     */
    public function setBundle(?string $bundle = null);

    /**
     * @param string|null $alias
     *
     * @return static
     */
    public function setAlias(?string $alias = null);
}
