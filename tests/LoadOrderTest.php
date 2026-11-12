<?php
/**
 * Load-order tests.
 *
 * These run in a separate process so post types and core callbacks can exist
 * before the plugin file is loaded.
 *
 * @package No_Random_Posts
 */

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Proves the plugin disables state that was registered before it loaded.
 */
class LoadOrderTest extends TestCase {

	/**
	 * Post types already in the global registry are disabled on boot.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_post_types_registered_before_the_plugin_are_disabled(): void {

		$post    = new No_Random_Posts_Post_Type_Double( 'post', true );
		$invoice = new No_Random_Posts_Post_Type_Double( 'invoice', true );
		$page    = new No_Random_Posts_Post_Type_Double( 'page', false );
		$legacy  = new No_Random_Posts_Legacy_Post_Type_Double( 'book' );

		$GLOBALS['wp_post_types'] = array(
			'post'    => $post,
			'invoice' => $invoice,
			'page'    => $page,
			'book'    => $legacy,
		);

		require dirname( __DIR__ ) . '/no-random-posts.php';

		$this->assertFalse( $post->randomable );
		$this->assertFalse( $invoice->randomable );
		$this->assertFalse( $page->randomable );
		$this->assertFalse( property_exists( $legacy, 'randomable' ) );
	}

	/**
	 * Core callbacks registered before plugins load are detached.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_handlers_registered_before_the_plugin_are_removed(): void {

		add_action( 'wp_after_insert_post', 'wp_random_content_flush_rewrite_rules_for_post', 10, 4 );
		add_action( 'after_delete_post', 'wp_random_content_flush_rewrite_rules_for_post', 10, 2 );
		add_action( 'pre_get_posts', 'wp_random_content_pre_get_posts', 20 );
		add_filter( 'posts_orderby', 'wp_random_content_posts_orderby', 20, 2 );
		add_filter( 'post_limits', 'wp_random_content_post_limits', 20, 2 );

		require dirname( __DIR__ ) . '/no-random-posts.php';

		$this->assertFalse( has_action( 'wp_after_insert_post', 'wp_random_content_flush_rewrite_rules_for_post' ) );
		$this->assertFalse( has_action( 'after_delete_post', 'wp_random_content_flush_rewrite_rules_for_post' ) );
		$this->assertFalse( has_action( 'pre_get_posts', 'wp_random_content_pre_get_posts' ) );
		$this->assertFalse( has_filter( 'posts_orderby', 'wp_random_content_posts_orderby' ) );
		$this->assertFalse( has_filter( 'post_limits', 'wp_random_content_post_limits' ) );
	}

	/**
	 * Loading the plugin does not require the WordPress 7.2 random-content APIs.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_no_fatal_when_core_random_apis_are_absent(): void {

		$this->assertFalse( function_exists( 'wp_is_random_content_redirect_enabled' ) );
		$this->assertFalse( function_exists( 'wp_random_content_redirect' ) );
		$this->assertFalse( function_exists( 'wp_random_content_pre_get_posts' ) );
		$this->assertFalse( class_exists( 'WP' ) );

		$legacy                       = new No_Random_Posts_Legacy_Post_Type_Double( 'book' );
		$GLOBALS['wp_post_types']     = array(
			'book' => $legacy,
		);

		require dirname( __DIR__ ) . '/no-random-posts.php';

		$this->assertFalse( property_exists( $legacy, 'randomable' ) );
		$this->assertFalse( apply_filters( 'option_rewrite_rules', false ) );
		$this->assertSame( array(), apply_filters( 'random_rewrite_rules', array( 'random/?$' => 'index.php?random=1' ) ) );
		$this->assertFalse( apply_filters( 'wp_enable_random_content_redirect', true ) );

		do_action( 'registered_post_type', 'book', $legacy );
		do_action( 'pre_get_posts' );
		do_action( 'init' );
		do_action( 'wp_loaded' );
		do_action( 'parse_request', new stdClass() );

		$this->assertFalse( property_exists( $legacy, 'randomable' ) );
		$this->assertTrue( class_exists( 'No_Random_Posts' ) );
	}

	/**
	 * Registration arguments stay untouched when WordPress has no randomable property.
	 *
	 * Setting the argument on those versions would add a property core never reads.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_registration_args_are_unchanged_without_randomable(): void {

		$this->assertFalse( class_exists( 'WP_Post_Type', false ) );

		require dirname( __DIR__ ) . '/no-random-posts.php';

		$args = array(
			'public'             => true,
			'publicly_queryable' => true,
			'has_archive'        => true,
			'randomable'         => true,
		);

		$this->assertSame( $args, apply_filters( 'register_post_type_args', $args, 'book' ) );
	}
}
