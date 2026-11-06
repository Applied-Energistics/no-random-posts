<?php
/**
 * Plugin Name:       No Random Posts
 * Plugin URI:        https://github.com/Applied-Energistics/no-random-posts
 * Description:       Disables WordPress random content URLs, such as /random/, for every post type.
 * Version:           1.1.1
 * Requires at least: 4.7
 * Requires PHP:      7.2
 * Author:            Jono Alderson
 * Author URI:        https://www.jonoalderson.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       no-random-posts
 *
 * @package No_Random_Posts
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-no-random-posts.php';

No_Random_Posts::boot();
