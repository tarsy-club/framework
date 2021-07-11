#!/usr/bin/env php
<?php

namespace TarsyClub\Framework;

require_once dirname(__DIR__) . '/vendor/autoload.php';

front_controller(parameters(), __FILE__, '../.env');
