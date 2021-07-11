<?php

namespace TarsyClub\Framework\Test\Unit;

use Codeception\Test\Unit;
use TarsyClub\Framework\BundleOptionsAwareInterface;
use TarsyClub\Framework\BundleOptionsAwareTrait;
use TarsyClub\Framework\Test\UnitTester;

/**
 * @tarsy-club
 *
 * @internal
 */
class BundleOptionsAwareTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider dataAlias
     *
     * @param BundleOptionsAwareInterface $model
     * @param string|null $expected
     */
    public function testAlias(BundleOptionsAwareInterface $model, ?string $expected = null): void
    {
        $model->setAlias($expected);
        $this->tester->assertEquals($expected, $model->getAlias());
    }

    public static function dataAlias(): \Generator
    {
        $model = (new class() implements BundleOptionsAwareInterface {
            use BundleOptionsAwareTrait;
        });
        $alias0 = 'alias';
        $model0 = clone $model;
        $model1 = clone $model;
        yield [
            $model0, $alias0,
        ];
        yield [
            $model1, null,
        ];
    }

    /**
     * @dataProvider dataBundle
     *
     * @param BundleOptionsAwareInterface $model
     * @param string|null $expected
     */
    public function testBundle(BundleOptionsAwareInterface $model, ?string $expected = null): void
    {
        $model->setBundle($expected);
        $this->tester->assertEquals($expected, $model->getBundle());
    }

    public static function dataBundle(): \Generator
    {
        $model = (new class() implements BundleOptionsAwareInterface {
            use BundleOptionsAwareTrait;
        });
        $bundle0 = 'bundle';
        $model0 = clone $model;
        $model1 = clone $model;
        yield [
            $model0, $bundle0,
        ];
        yield [
            $model1, null,
        ];
    }
}
