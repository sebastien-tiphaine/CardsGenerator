<?php

// MAIN FILE USED TO CONFIGURE THE FRAMEWORK
require_once(__DIR__.'/Libs/Bootstrap.php');

// init the framework
Bootstrap::getInstance();

define('__BASE__', 		Bootstrap::getInstance()->base);
define('__LIBS__', 		Bootstrap::getInstance()->libs);

