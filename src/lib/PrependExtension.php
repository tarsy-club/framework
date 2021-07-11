<?php

namespace TarsyClub\Framework;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class PrependExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->_load(
            $container,
            $this->getRoot($container),
            'parameter',
            self::LOAD_DEPTH_FULL,
            $container->getParameter('kernel.environment')
        );
    }
}
