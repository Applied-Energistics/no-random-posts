<?php
/**
 * Stand-in for the post type class once WordPress 7.2 declares randomable.
 *
 * Loaded only by tests that need the property to exist. Older-WordPress tests
 * leave this file unloaded, so the class stays absent.
 *
 * @package No_Random_Posts
 */

/**
 * Declares the randomable property from WP_Post_Type.
 */
class WP_Post_Type {

	/**
	 * Whether random content redirects may select this post type.
	 *
	 * @var bool|null
	 */
	public $randomable = null;
}
