<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads hook stubs so the plugin can be exercised without WordPress or a database.
 *
 * @package No_Random_Posts
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require __DIR__ . '/stubs/wordpress.php';
require __DIR__ . '/PostTypeDouble.php';
