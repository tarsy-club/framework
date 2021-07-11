<?php

namespace TarsyClub\Framework\Test\Unit;

use Codeception\Test\Unit;
use TarsyClub\Framework\Kernel;
use TarsyClub\Framework\Test\UnitTester;

/**
 * @tarsy-club
 *
 * @internal
 */
class KernelTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @test
     */
    public function boot(): void
    {
        $kernel = new Kernel('env', false);
        $kernel->setParameters([
            \Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['envs' => ['all' => true]],
        ]);
        $kernel->setAppDir(getcwd());
        $kernel->boot();
        $this->tester->assertEquals('env', $kernel->getContainer()->getParameter('kernel.environment'));
    }

    protected function _before()
    {
        $this->tester->amInPath('_data');
        array_map(function (string $dir) {
            $this->tester->deleteDir($dir);
            $this->tester->copyDir('../_support/fixture/' . $dir, $dir);
        }, ['resources', 'var', 'src']);
    }

    protected function _after()
    {
        array_map(function (string $dir) {
            $this->tester->deleteDir($dir);
        }, ['resources', 'var', 'src']);
    }
}
