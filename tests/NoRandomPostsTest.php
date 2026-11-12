<?php
/**
 * Behaviour tests for No Random Posts.
 *
 * @package No_Random_Posts
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers disabling random content redirects without breaking a real "random" slug.
 */
class NoRandomPostsTest extends TestCase {

	/**
	 * Loads the plugin once for this process.
	 *
	 * @return void
	 */
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__ ) . '/no-random-posts.php';
	}

	/**
	 * The official core filter is forced off.
	 *
	 * @return void
	 */
	public function test_feature_filter_forces_random_redirects_off(): void {

		$this->assertFalse( apply_filters( 'wp_enable_random_content_redirect', true ) );
	}

	/**
	 * A callback that runs earlier cannot leave the feature enabled.
	 *
	 * @return void
	 */
	public function test_feature_stays_off_when_an_earlier_callback_enables_it(): void {

		add_filter(
			'wp_enable_random_content_redirect',
			static function () {
				return true;
			},
			10
		);

		$this->assertFalse( apply_filters( 'wp_enable_random_content_redirect', true ) );
	}

	/**
	 * Booting twice does not stack the disable filter.
	 *
	 * @return void
	 */
	public function test_boot_is_idempotent(): void {

		$before      = $this->callback_count( 'wp_enable_random_content_redirect' );
		$before_args = $this->callback_count( 'register_post_type_args' );

		No_Random_Posts::boot();

		$this->assertSame( $before, $this->callback_count( 'wp_enable_random_content_redirect' ) );
		$this->assertSame( $before_args, $this->callback_count( 'register_post_type_args' ) );
	}

	/**
	 * Every post type that exposes the flag is forced off, including an explicit opt-in.
	 *
	 * @return void
	 */
	public function test_feature_is_disabled_for_every_post_type(): void {

		$types = array(
			'post'       => new No_Random_Posts_Post_Type_Double( 'post', true ),
			'page'       => new No_Random_Posts_Post_Type_Double( 'page', false ),
			'attachment' => new No_Random_Posts_Post_Type_Double( 'attachment', false ),
			'invoice'    => new No_Random_Posts_Post_Type_Double( 'invoice', true ),
			'musicals'   => new No_Random_Posts_Post_Type_Double( 'musicals', true ),
		);

		foreach ( $types as $name => $object ) {
			do_action( 'registered_post_type', $name, $object );
		}

		foreach ( $types as $object ) {
			$this->assertFalse( $object->randomable );
		}
	}

	/**
	 * A type registered without the flag, then marked randomable again, is turned back off.
	 *
	 * Core defaults the omitted flag to true for public archives. The late init sweep
	 * covers that, and a later write to the same object.
	 *
	 * @return void
	 */
	public function test_post_type_cannot_opt_back_in_during_init(): void {

		$invoice = new No_Random_Posts_Post_Type_Double( 'invoice', true );

		do_action( 'registered_post_type', 'invoice', $invoice );
		$this->assertFalse( $invoice->randomable );

		$invoice->randomable            = true;
		$GLOBALS['wp_post_types']       = array(
			'invoice' => $invoice,
		);

		do_action( 'init' );

		$this->assertFalse( $invoice->randomable );

		$invoice->randomable = true;

		do_action( 'wp_loaded' );

		$this->assertFalse( $invoice->randomable );
	}

	/**
	 * WordPress versions without the property are left untouched.
	 *
	 * @return void
	 */
	public function test_post_type_without_randomable_property_is_unchanged(): void {

		$legacy = new No_Random_Posts_Legacy_Post_Type_Double( 'book' );

		do_action( 'registered_post_type', 'book', $legacy );
		do_action( 'init' );

		$this->assertFalse( property_exists( $legacy, 'randomable' ) );
	}

	/**
	 * Registration arguments are born ineligible, including an explicit opt-in.
	 *
	 * @return void
	 */
	public function test_registration_args_force_randomable_off(): void {

		$this->support_randomable_argument();

		$args = apply_filters(
			'register_post_type_args',
			array(
				'public'             => true,
				'publicly_queryable' => true,
				'has_archive'        => true,
				'randomable'         => true,
				'label'              => 'Invoices',
			),
			'invoice'
		);

		$this->assertFalse( $args['randomable'] );
		$this->assertTrue( $args['public'] );
		$this->assertSame( 'Invoices', $args['label'] );
	}

	/**
	 * A callback at the default priority cannot leave the argument on.
	 *
	 * @return void
	 */
	public function test_earlier_registration_callback_cannot_leave_randomable_on(): void {

		$this->support_randomable_argument();

		add_filter(
			'register_post_type_args',
			static function ( $args ) {

				if ( ! is_array( $args ) ) {
					return $args;
				}

				$args['randomable'] = true;

				return $args;
			},
			10,
			2
		);

		$args = apply_filters(
			'register_post_type_args',
			array(
				'public'      => true,
				'has_archive' => true,
			),
			'post'
		);

		$this->assertArrayHasKey( 'randomable', $args );
		$this->assertFalse( $args['randomable'] );
	}

	/**
	 * A per-type filter that opts back in is caught once the type is registered.
	 *
	 * `register_{post_type}_post_type_args` runs after the general filter.
	 *
	 * @return void
	 */
	public function test_specific_post_type_opt_in_is_caught_on_registration(): void {

		$this->support_randomable_argument();

		add_filter(
			'register_ledger_post_type_args',
			static function ( $args ) {

				$args['randomable'] = true;

				return $args;
			},
			PHP_INT_MAX
		);

		$args = apply_filters( 'register_post_type_args', array( 'public' => true ), 'ledger' );
		$args = apply_filters( 'register_ledger_post_type_args', $args, 'ledger' );

		$ledger = new No_Random_Posts_Post_Type_Double( 'ledger', $args['randomable'] );
		$this->assertTrue( $ledger->randomable );

		do_action( 'registered_post_type', 'ledger', $ledger );

		$this->assertFalse( $ledger->randomable );
	}

	/**
	 * Unexpected registration values do not fatal.
	 *
	 * @return void
	 */
	public function test_registration_args_ignore_unexpected_values(): void {

		$this->support_randomable_argument();

		$this->assertSame( 'nope', apply_filters( 'register_post_type_args', 'nope', 'invoice' ) );
		$this->assertNull( apply_filters( 'register_post_type_args', null, 'invoice' ) );
	}

	/**
	 * The dedicated random rewrite-rule filter contributes nothing.
	 *
	 * @return void
	 */
	public function test_random_rewrite_rules_are_cleared(): void {

		add_filter(
			'random_rewrite_rules',
			static function ( $rules ) {
				$rules['custom/?$'] = 'index.php?random=1';

				return $rules;
			},
			10
		);

		$this->assertSame(
			array(),
			apply_filters(
				'random_rewrite_rules',
				array(
					'random/?$' => 'index.php?random=1',
				)
			)
		);
	}

	/**
	 * Random endpoints are removed from generated and cached rules.
	 *
	 * A real page, post, or term whose slug is "random" keeps its own rule.
	 *
	 * @return void
	 */
	public function test_random_endpoints_are_not_rewritten_and_real_slugs_remain(): void {

		$rules = array(
			'random/?$'                              => 'index.php?random=1',
			'zufall/?$'                              => 'index.php?random=1',
			'random/category/(.+?)/?$'               => 'index.php?category_name=$matches[1]&random=1',
			'random/tag/([^/]+)/?$'                  => 'index.php?tag=$matches[1]&random=1',
			'random/author/([^/]+)/?$'               => 'index.php?author_name=$matches[1]&random=1',
			'random/invoice/?$'                      => 'index.php?post_type=invoice&random=1',
			'random/musicals/?$'                     => 'index.php?post_type=musicals&random=1',
			'(.?.+?)(?:/([0-9]+))?/?$'               => 'index.php?pagename=$matches[1]&page=$matches[2]',
			'([^/]+)(?:/([0-9]+))?/?$'               => 'index.php?name=$matches[1]&page=$matches[2]',
			'category/(.+?)/?$'                      => 'index.php?category_name=$matches[1]',
			'category/random/?$'                     => 'index.php?category_name=random',
			'tag/([^/]+)/?$'                         => 'index.php?tag=$matches[1]',
			'author/([^/]+)/?$'                      => 'index.php?author_name=$matches[1]',
			'invoice/?$'                             => 'index.php?post_type=invoice',
			'robots\\.txt$'                          => 'index.php?robots=1',
			'feed/(feed|rdf|rss|rss2|atom)/?$'       => 'index.php?&feed=$matches[1]',
			'kept-non-string'                        => array( 'random' => '1' ),
		);

		$expected = array(
			'(.?.+?)(?:/([0-9]+))?/?$'         => 'index.php?pagename=$matches[1]&page=$matches[2]',
			'([^/]+)(?:/([0-9]+))?/?$'         => 'index.php?name=$matches[1]&page=$matches[2]',
			'category/(.+?)/?$'                => 'index.php?category_name=$matches[1]',
			'category/random/?$'               => 'index.php?category_name=random',
			'tag/([^/]+)/?$'                   => 'index.php?tag=$matches[1]',
			'author/([^/]+)/?$'                => 'index.php?author_name=$matches[1]',
			'invoice/?$'                       => 'index.php?post_type=invoice',
			'robots\\.txt$'                    => 'index.php?robots=1',
			'feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?&feed=$matches[1]',
			'kept-non-string'                  => array( 'random' => '1' ),
		);

		$this->assertSame( $expected, apply_filters( 'rewrite_rules_array', $rules ) );
		$this->assertSame( $expected, apply_filters( 'option_rewrite_rules', $rules ) );
	}

	/**
	 * A public archive whose slug is the random base keeps its rewrite.
	 *
	 * Core stores one rule per pattern. The random redirect overwrites the archive
	 * rule, so removing the redirect has to put the archive query back.
	 * Page, post, and term rules for the slug "random" stay in place.
	 *
	 * @return void
	 */
	public function test_public_archive_at_the_random_base_is_rebuilt_in_memory(): void {

		$secret                 = new stdClass();
		$secret->name           = 'secret';
		$secret->public         = false;
		$secret->has_archive    = true;
		$secret->rewrite        = array(
			'slug'       => 'random',
			'with_front' => true,
		);
		$library                = new stdClass();
		$library->name          = 'library';
		$library->public        = true;
		$library->has_archive   = true;
		$library->rewrite       = array(
			'slug'       => 'random',
			'with_front' => true,
		);
		$GLOBALS['wp_post_types'] = array(
			'secret'  => $secret,
			'library' => $library,
		);
		$GLOBALS['wp_rewrite']        = new stdClass();
		$GLOBALS['wp_rewrite']->root  = '';
		$GLOBALS['wp_rewrite']->front = '';
		$GLOBALS['wp_rewrite']->index = 'index.php';
		$GLOBALS['wp_rewrite']->random_base = 'random';

		$rules = array(
			'random/?$'                => 'index.php?random=1',
			'random/library/?$'        => 'index.php?post_type=library&random=1',
			'random/feed/(feed|atom)/?$' => 'index.php?post_type=library&feed=$matches[1]',
			'(.?.+?)(?:/([0-9]+))?/?$' => 'index.php?pagename=$matches[1]&page=$matches[2]',
			'([^/]+)(?:/([0-9]+))?/?$' => 'index.php?name=$matches[1]&page=$matches[2]',
			'category/random/?$'       => 'index.php?category_name=random',
		);

		$filtered = apply_filters( 'option_rewrite_rules', $rules );

		$this->assertSame( 'index.php?post_type=library', $filtered['random/?$'] );
		$this->assertArrayNotHasKey( 'random/library/?$', $filtered );
		$this->assertSame( 'index.php?post_type=library&feed=$matches[1]', $filtered['random/feed/(feed|atom)/?$'] );
		$this->assertSame( 'index.php?pagename=$matches[1]&page=$matches[2]', $filtered['(.?.+?)(?:/([0-9]+))?/?$'] );
		$this->assertSame( 'index.php?name=$matches[1]&page=$matches[2]', $filtered['([^/]+)(?:/([0-9]+))?/?$'] );
		$this->assertSame( 'index.php?category_name=random', $filtered['category/random/?$'] );
		$this->assertSame( $filtered, apply_filters( 'rewrite_rules_array', $rules ) );
	}

	/**
	 * The rebuilt archive follows a custom random base and the site root.
	 *
	 * @return void
	 */
	public function test_archive_slug_matches_a_custom_random_base_under_a_root(): void {

		$invoices               = new stdClass();
		$invoices->name         = 'invoice';
		$invoices->public       = true;
		$invoices->has_archive  = 'zufall';
		$invoices->rewrite      = array(
			'slug'       => 'invoices',
			'with_front' => false,
		);
		$GLOBALS['wp_post_types'] = array(
			'invoice' => $invoices,
		);
		$GLOBALS['wp_rewrite']        = new stdClass();
		$GLOBALS['wp_rewrite']->root  = 'blog/';
		$GLOBALS['wp_rewrite']->front = '/blog/';
		$GLOBALS['wp_rewrite']->index = 'index.php';
		$GLOBALS['wp_rewrite']->random_base = 'zufall';

		$rules = array(
			'blog/zufall/?$'           => 'index.php?random=1',
			'blog/zufall/zufall/?$'    => 'index.php?post_type=invoice&random=1',
			'(.?.+?)(?:/([0-9]+))?/?$' => 'index.php?pagename=$matches[1]&page=$matches[2]',
		);

		$filtered = apply_filters( 'option_rewrite_rules', $rules );

		$this->assertSame( 'index.php?post_type=invoice', $filtered['blog/zufall/?$'] );
		$this->assertArrayNotHasKey( 'blog/zufall/zufall/?$', $filtered );
		$this->assertSame( 'index.php?pagename=$matches[1]&page=$matches[2]', $filtered['(.?.+?)(?:/([0-9]+))?/?$'] );
	}

	/**
	 * A missing rewrite option stays missing so WordPress can generate rules itself.
	 *
	 * Returning an empty array would look like a stored ruleset and can force a write.
	 *
	 * @return void
	 */
	public function test_missing_rewrite_rules_are_not_replaced(): void {

		$this->assertFalse( apply_filters( 'option_rewrite_rules', false ) );
		$this->assertNull( apply_filters( 'rewrite_rules_array', null ) );
		$this->assertSame( '', apply_filters( 'option_rewrite_rules', '' ) );
	}

	/**
	 * The public query variable list no longer accepts "random".
	 *
	 * @return void
	 */
	public function test_random_query_var_is_removed(): void {

		$this->assertSame(
			array( 'pagename', 'name', 'category_name', 'post_type', 's' ),
			apply_filters(
				'query_vars',
				array( 'pagename', 'name', 'category_name', 'random', 'post_type', 's' )
			)
		);
	}

	/**
	 * Parsed requests drop the random variable and keep a slug that happens to be "random".
	 *
	 * @return void
	 */
	public function test_random_request_is_not_redirected_and_real_slug_query_remains(): void {

		$archive = apply_filters(
			'request',
			array(
				'year'   => '2026',
				'random' => '1',
			)
		);

		$this->assertSame( array( 'year' => '2026' ), $archive );
		$this->assertFalse( apply_filters( 'wp_enable_random_content_redirect', true ) );
		$this->assertFalse( isset( $archive['random'] ) && apply_filters( 'wp_enable_random_content_redirect', true ) );

		$search = apply_filters(
			'request',
			array(
				's'      => 'foo',
				'random' => '',
			)
		);

		$this->assertSame( array( 's' => 'foo' ), $search );

		$page = apply_filters(
			'request',
			array(
				'pagename' => 'random',
			)
		);

		$this->assertSame( array( 'pagename' => 'random' ), $page );

		$term = apply_filters(
			'request',
			array(
				'category_name' => 'random',
			)
		);

		$this->assertSame( array( 'category_name' => 'random' ), $term );
	}

	/**
	 * The query variable is removed again after request parsing.
	 *
	 * @return void
	 */
	public function test_parsed_request_drops_the_random_query_var(): void {

		$wp              = new stdClass();
		$wp->query_vars  = array(
			'random'   => '1',
			'pagename' => 'random',
		);

		do_action( 'parse_request', $wp );

		$this->assertSame( array( 'pagename' => 'random' ), $wp->query_vars );
	}

	/**
	 * When the main WP object exists, its public query variable list loses "random".
	 *
	 * @return void
	 */
	public function test_public_query_var_is_removed_from_wp(): void {

		$GLOBALS['wp'] = new class() {
			/**
			 * Public query variables.
			 *
			 * @var string[]
			 */
			public $public_query_vars = array( 'name', 'random', 'pagename' );

			/**
			 * Removes one public query variable.
			 *
			 * @param string $name Query variable name.
			 * @return void
			 */
			public function remove_query_var( $name ) {

				$this->public_query_vars = array_values( array_diff( $this->public_query_vars, array( $name ) ) );
			}
		};

		do_action( 'init' );

		$this->assertNotContains( 'random', $GLOBALS['wp']->public_query_vars );
		$this->assertContains( 'pagename', $GLOBALS['wp']->public_query_vars );
	}

	/**
	 * Handlers core attaches inside WP::handle_random() are gone before they can run.
	 *
	 * @return void
	 */
	public function test_random_handlers_added_for_the_request_are_removed(): void {

		add_action( 'pre_get_posts', 'wp_random_content_pre_get_posts', 20 );
		add_filter( 'posts_orderby', 'wp_random_content_posts_orderby', 20, 2 );
		add_filter( 'post_limits', 'wp_random_content_post_limits', 20, 2 );

		$GLOBALS['wp_missing_callbacks'] = array();

		do_action( 'pre_get_posts', new stdClass() );

		$this->assertFalse( has_action( 'pre_get_posts', 'wp_random_content_pre_get_posts' ) );
		$this->assertFalse( has_filter( 'posts_orderby', 'wp_random_content_posts_orderby' ) );
		$this->assertFalse( has_filter( 'post_limits', 'wp_random_content_post_limits' ) );
		$this->assertSame( array(), $GLOBALS['wp_missing_callbacks'] );
	}

	/**
	 * Unexpected hook values do not fatal.
	 *
	 * @return void
	 */
	public function test_unexpected_hook_values_do_not_fatal(): void {

		$this->assertSame( 'nope', apply_filters( 'query_vars', 'nope' ) );
		$this->assertSame( 1, apply_filters( 'request', 1 ) );

		unset( $GLOBALS['wp_post_types'] );
		do_action( 'init' );

		$GLOBALS['wp_post_types'] = 'nope';
		do_action( 'wp_loaded' );

		$GLOBALS['wp_post_types'] = array(
			'bad' => 'post',
		);
		do_action( 'registered_post_type', 'bad', 'post' );
		do_action( 'init' );

		$wp = new stdClass();
		do_action( 'parse_request', $wp );

		$this->assertFalse( isset( $wp->query_vars ) );
	}

	/**
	 * Declares the randomable property WordPress 7.2 adds to WP_Post_Type.
	 *
	 * @return void
	 */
	private function support_randomable_argument() {

		if ( class_exists( 'WP_Post_Type', false ) ) {
			return;
		}

		require_once __DIR__ . '/stubs/class-wp-post-type.php';
	}

	/**
	 * Counts callbacks registered on a hook.
	 *
	 * @param string $hook_name Hook name.
	 * @return int
	 */
	private function callback_count( $hook_name ) {

		$count = 0;

		if ( empty( $GLOBALS['wp_filter'][ $hook_name ] ) || ! is_array( $GLOBALS['wp_filter'][ $hook_name ] ) ) {
			return 0;
		}

		foreach ( $GLOBALS['wp_filter'][ $hook_name ] as $callbacks ) {
			$count += count( $callbacks );
		}

		return $count;
	}
}
