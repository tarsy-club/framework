<?php

namespace TarsyClub\Framework\Test\Helper;

if (!trait_exists('\\TarsyClub\\Framework\\Test\\_generated\\UnitTesterActions')) {
    trait UnitTesterActions
    {
        // empty
    }
} else {
    trait UnitTesterActions
    {
        use \TarsyClub\Framework\Test\_generated\UnitTesterActions;
    }
}
