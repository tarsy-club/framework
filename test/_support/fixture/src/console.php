#!/usr/bin/env php
<?php

namespace TarsyClub\Framework\Test\Unit;

use function TarsyClub\Framework\front_controller;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

front_controller([], __FILE__);
