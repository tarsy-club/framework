<?php

namespace TarsyClub\Framework\Test\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TarsyClub\Framework\Extension;
use TarsyClub\Framework\PrependExtension;
use TarsyClub\Framework\Test\UnitTester;

/**
 * @tarsy-club
 *
 * @internal
 */
class ExtensionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ContainerBuilder|null
     */
    protected $containerBuilder;

    /**
     * @test
     * @dataProvider dataFileNotFound
     */
    public function fileNotFound(RuntimeException $expected, string $bundle, array $metadata)
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.environment', 'env');
        $containerBuilder->setParameter('kernel.bundles_metadata', $metadata);
        $this->expectExceptionObject($expected);
        $extension = new Extension($bundle, 'alias');
        $extension->load([], $containerBuilder);
    }

    public static function dataFileNotFound(): \Generator
    {
        $bundle0 = 'NotAppBundle';
        $metadata0 = [$bundle0 => [
            'path' => 'bundles/' . $bundle0,
        ]];
        $expected0 = new RuntimeException($bundle0 . ' resources not found');
        $bundle1 = 'NullAppBundle';
        $metadata1 = [$bundle1 => [
            'path' => 'bundles/' . $bundle1,
        ]];
        $expected1 = new RuntimeException($bundle1 . ' resources not found');
        $metadata2 = [$bundle1 => [
            'path' => 'bundles/' . $bundle1,
        ]];
        $bundle2 = $bundle0;
        $expected2 = new RuntimeException($bundle2 . ' metadata not found');
        yield [
            $expected0,
            $bundle0,
            $metadata0,
        ];
        yield [
            $expected1,
            $bundle1,
            $metadata1,
        ];
        yield [
            $expected2,
            $bundle2,
            $metadata2,
        ];
    }

    /**
     * @test
     */
    public function load(): void
    {
        /**
         * @var Extension $extension
         */
        $extension = $this->make(Extension::class, [
            'loadConfigs' => Expected::exactly(1),
            'getBundle' => function () { return 'AppBundle'; },
        ]);
        $extension->load([], $this->containerBuilder);
    }

    /**
     * @test
     */
    public function prepend(): void
    {
        /**
         * @var PrependExtension $extension
         */
        $extension = $this->make(PrependExtension::class, [
            'loadConfigs' => Expected::exactly(1),
            'getBundle' => function () { return 'AppBundle'; },
        ]);
        $extension->prepend($this->containerBuilder);
    }

    /**
     * @test
     */
    public function loadConfigs(): void
    {
        $extension = new Extension('AppBundle', 'app');
        $extension->load([], $this->containerBuilder);
        $this->assertTrue($this->containerBuilder->has('app_std_class'));
    }

    protected function _before()
    {
        $this->tester->amInPath('_data');
        array_map(function (string $dir) {
            $this->tester->deleteDir($dir);
            $this->tester->copyDir('../_support/fixture/' . $dir, $dir);
        }, ['resources', 'var', 'src']);
        $this->tester->amInPath('src');
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.environment', 'env');
        $containerBuilder->setParameter('kernel.bundles_metadata', ['AppBundle' => [
            'path' => 'bundles/AppBundle',
        ]]);
        $this->containerBuilder = $containerBuilder;
    }

    protected function _after()
    {
        $this->tester->amInPath('../');
        array_map(function (string $dir) {
            $this->tester->deleteDir($dir);
        }, ['resources', 'var', 'src']);
    }
}
