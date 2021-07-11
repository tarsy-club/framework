<?php

namespace TarsyClub\Framework;

function parameters()
{
    return [
        \Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['envs' => ['all' => true], 'routes' => false],
    ];
}
