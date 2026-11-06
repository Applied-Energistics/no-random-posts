<?php
/**
 * Post type stand-in for tests.
 *
 * Declares the `randomable` property WordPress 7.2 adds to WP_Post_Type.
 *
 * @package No_Random_Posts
 */

/**
 * Post type object that can be marked randomable.
 */
class No_Random_Posts_Post_Type_Double {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Whether random content redirects may select this post type.
	 *
	 * @var bool|null
	 */
	public $randomable;

	/**
	 * Stores the name and randomable flag.
	 *
	 * @param string    $name       Post type key.
	 * @param bool|null $randomable Whether the type starts as randomable.
	 */
	public function __construct( $name, $randomable ) {

		$this->name       = $name;
		$this->randomable = $randomable;
	}
}

/**
 * Post type object from a WordPress version that has no randomable property.
 */
class No_Random_Posts_Legacy_Post_Type_Double {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Stores the post type key.
	 *
	 * @param string $name Post type key.
	 */
	public function __construct( $name ) {

		$this->name = $name;
	}
}
