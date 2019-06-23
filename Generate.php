<?php

require_once(__DIR__.'/Bootstrap.php');
require_once(__LIBS__.'/CliExec.php');

// php Generate.php --config config.ini --entry entryKey (single entry)
// php Generate.php --config config.ini --all (from template group var)

$arrOptions = getopt(
	// short opts
	'c:e:d:t:ah',
	// long opts
	array(
		'config:',
		'entry:',
		'card:',
		'tones:',
		'all',
		'help',
		'debug'
	)
);

CliExec::generate($arrOptions);
