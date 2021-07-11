<?php

namespace TarsyClub\Framework\Test\Unit;

use Codeception\Test\Unit;
use TarsyClub\Framework\Exception\UnknownFrontControllerException;
use TarsyClub\Framework\RawFrontController;
use TarsyClub\Framework\Test\UnitTester;

/**
 * @tarsy-club
 *
 * @internal
 */
class RawFrontControllerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @test
     * @dataProvider dataRawInvalid
     *
     * @param string|null $explicitFrontController
     */
    public function rawInvalid(?string $explicitFrontController = null): void
    {
        $this->expectExceptionObject((new UnknownFrontControllerException('unknown front controller ' . $explicitFrontController)));
        (new RawFrontController())($explicitFrontController);
    }

    public static function dataRawInvalid(): \Generator
    {
        yield [
            null,
        ];
        yield [
            'unknown.php',
        ];
    }

    /**
     * @test
     * @dataProvider dataRaw
     *
     * @param array $expected
     * @param string|null $explicitFrontController
     */
    public function raw(array $expected, string $explicitFrontController = null): void
    {
        $actual = (new RawFrontController())($explicitFrontController);
        $this->tester->assertEquals($expected, $actual);
    }

    public static function dataRaw(): \Generator
    {
        yield [
            [
                'index.php',
                'public/.env',
            ],
            'public/index.php',
        ];
        yield [
            [
                'console.php',
                'src/.env',
            ],
            'src/console.php',
        ];
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
        $this->tester->amInPath('../');
        array_map(function (string $dir) {
            $this->tester->deleteDir($dir);
        }, ['resources', 'var', 'src']);
    }
}
