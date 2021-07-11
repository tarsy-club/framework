<?php

namespace TarsyClub\Framework;

trait BundleOptionsAwareTrait
{
    /**
     * @var string|null
     */
    protected $bundle;
    /**
     * @var string|null
     */
    protected $alias;

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function getBundle(): ?string
    {
        return $this->bundle;
    }

    /**
     * @param string|null $bundle
     *
     * @return static
     */
    public function setBundle(?string $bundle = null)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @param string|null $alias
     *
     * @return static
     */
    public function setAlias(?string $alias = null)
    {
        $this->alias = $alias;

        return $this;
    }
}
