<?php

namespace TarsyClub\Framework\Test;

use Codeception\Actor;
use Codeception\Lib\Friend;
use TarsyClub\Framework\Test\Helper\UnitTesterActions;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class UnitTester extends Actor
{
    use UnitTesterActions;
    /**
     * Define custom actions here
     */
}
