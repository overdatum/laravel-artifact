<?php

// --------------------------------------------------------------
// Register the Layla namespace
// --------------------------------------------------------------
Autoloader::namespaces(array(
	'Layla' => __DIR__.DS.'layla'
));

// --------------------------------------------------------------
// Load dependencies
// --------------------------------------------------------------
require __DIR__.DS.'dependencies'.DS.'bootsparks'.DS.'start'.EXT;