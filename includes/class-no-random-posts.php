<?php
/**
 * Disables WordPress random content URLs.
 *
 * @package No_Random_Posts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns off random content redirects for every post type.
 *
 * WordPress 7.2 redirects `/random/` (and author, term, and post type variants)
 * to a random post. The official switch is the `wp_enable_random_content_redirect`
 * filter. Cached rewrite rules and the `random` query variable can still fire
 * after that filter is set, and a post type registered without `randomable`
 * defaults to eligible, so those paths are removed as well.
 *
 * @since 1.0.0
 */
class No_Random_Posts {

	/**
	 * Query variable WordPress uses to request a random post.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'random';

	/**
	 * Registers the disable hooks once.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot() {

		static $booted = false;

		if ( $booted ) {
			return;
		}

		$booted = true;

		$plugin = new self();
		$plugin->register_hooks();
	}

	/**
	 * Attaches the callbacks that keep the feature off.
	 *
	 * Also disables post types and handlers that already exist. Plugins and
	 * mu-plugins that loaded first may have registered both.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_hooks() {

		add_filter( 'wp_enable_random_content_redirect', [ $this, 'disable_feature' ], PHP_INT_MAX );
		add_filter( 'random_rewrite_rules', [ $this, 'clear_random_rewrite_rules' ], PHP_INT_MAX );
		add_filter( 'rewrite_rules_array', [ $this, 'strip_random_rewrite_rules' ], PHP_INT_MAX );
		add_filter( 'option_rewrite_rules', [ $this, 'strip_random_rewrite_rules' ], PHP_INT_MAX );
		add_filter( 'query_vars', [ $this, 'remove_random_query_var' ], PHP_INT_MAX );
		add_filter( 'request', [ $this, 'remove_random_request_var' ], PHP_INT_MAX );

		add_action( 'parse_request', [ $this, 'remove_random_from_parsed_request' ], PHP_INT_MAX );
		add_action( 'registered_post_type', [ $this, 'disable_post_type' ], PHP_INT_MAX, 2 );
		add_action( 'init', [ $this, 'disable_registered_post_types' ], PHP_INT_MAX );
		add_action( 'wp_loaded', [ $this, 'disable_registered_post_types' ], 0 );
		add_action( 'init', [ $this, 'remove_random_query_var_now' ], PHP_INT_MAX );
		add_action( 'pre_get_posts', [ $this, 'remove_random_handlers' ], 19 );

		$this->disable_registered_post_types();
		$this->remove_random_handlers();
	}

	/**
	 * Disables random content redirects.
	 *
	 * Core checks this before generating rewrite rules, redirecting, and showing
	 * the random base field on the permalink screen.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Always false.
	 */
	public function disable_feature() {
		return false;
	}

	/**
	 * Removes the rewrite rules that exist only for random content redirects.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> No random content rules.
	 */
	public function clear_random_rewrite_rules() {
		return [];
	}

	/**
	 * Removes random content targets from a full rewrite map.
	 *
	 * Generated rules and the cached `rewrite_rules` option both pass through
	 * here. Filtering the option on read avoids writing it. A missing option is
	 * returned unchanged so WordPress can build the normal ruleset itself.
	 *
	 * Rules for a real page, post, or term whose slug is `random` use another
	 * query variable and are kept.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $rules Rewrite rules keyed by pattern, or a non-array when the option is empty.
	 * @return mixed Rules with random content targets removed.
	 */
	public function strip_random_rewrite_rules( $rules ) {

		if ( ! is_array( $rules ) ) {
			return $rules;
		}

		foreach ( $rules as $pattern => $target ) {
			if ( ! $this->rule_sets_random_query_var( $target ) ) {
				continue;
			}

			$archive_query = $this->archive_query_for_random_base_pattern( $pattern, $target );

			if ( is_string( $archive_query ) && $archive_query !== '' ) {
				$rules[ $pattern ] = $archive_query;
				continue;
			}

			unset( $rules[ $pattern ] );
		}

		return $rules;
	}

	/**
	 * Rebuilds a post type archive query stolen by the random redirect.
	 *
	 * The redirect and the archive share one pattern when the archive slug is the
	 * random base, for example `random/?$`. Cached rules keep only the redirect.
	 * Putting the archive query back does not write the option.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $pattern Rewrite pattern.
	 * @param mixed $target  Rewrite target that sets the random query variable.
	 * @return string Archive query, or an empty string when this pattern is not that archive.
	 */
	private function archive_query_for_random_base_pattern( $pattern, $target ) {

		if ( ! is_string( $pattern ) || ! isset( $GLOBALS['wp_post_types'] ) || ! is_array( $GLOBALS['wp_post_types'] ) ) {
			return '';
		}

		$random_base = $this->random_content_base();

		foreach ( $GLOBALS['wp_post_types'] as $post_type_object ) {
			if ( ! is_object( $post_type_object ) || empty( $post_type_object->public ) ) {
				continue;
			}

			if ( ! isset( $post_type_object->name ) || ! is_string( $post_type_object->name ) || $post_type_object->name === '' ) {
				continue;
			}

			if ( $this->archive_rewrite_pattern( $post_type_object, $random_base ) !== $pattern ) {
				continue;
			}

			return $this->rewrite_index_from_target( $target ) . '?post_type=' . $post_type_object->name;
		}

		return '';
	}

	/**
	 * Builds the archive rewrite pattern when its slug is the random base.
	 *
	 * Matches the pattern `WP_Post_Type::add_rewrite_rules()` stores, including
	 * the front or root prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param object $post_type   Public post type object.
	 * @param string $random_base Random content base slug, without the root.
	 * @return string Pattern ending in `/?$`, or an empty string when this archive is not the random base.
	 */
	private function archive_rewrite_pattern( $post_type, $random_base ) {

		if ( empty( $post_type->has_archive ) || ! is_array( $post_type->rewrite ) ) {
			return '';
		}

		$slug = $post_type->has_archive === true ? ( $post_type->rewrite['slug'] ?? '' ) : $post_type->has_archive;

		if ( ! is_string( $slug ) || $slug === '' || $slug !== $random_base ) {
			return '';
		}

		$rewrite    = ( isset( $GLOBALS['wp_rewrite'] ) && is_object( $GLOBALS['wp_rewrite'] ) ) ? $GLOBALS['wp_rewrite'] : null;
		$with_front = ! isset( $post_type->rewrite['with_front'] ) || $post_type->rewrite['with_front'];

		if ( $with_front ) {
			$front = ( $rewrite && isset( $rewrite->front ) && is_string( $rewrite->front ) ) ? $rewrite->front : '';
			$slug  = substr( $front, 1 ) . $slug;
		} else {
			$root = ( $rewrite && isset( $rewrite->root ) && is_string( $rewrite->root ) ) ? $rewrite->root : '';
			$slug = $root . $slug;
		}

		return $slug . '/?$';
	}

	/**
	 * Reads the random content base slug.
	 *
	 * Uses `WP_Rewrite::get_random_base()` when WordPress 7.2 provides it.
	 * Otherwise uses a custom base already stored on the rewrite object, then `random`.
	 *
	 * @since 1.0.0
	 *
	 * @return string Random base slug.
	 */
	private function random_content_base() {

		$rewrite = ( isset( $GLOBALS['wp_rewrite'] ) && is_object( $GLOBALS['wp_rewrite'] ) ) ? $GLOBALS['wp_rewrite'] : null;

		if ( $rewrite && method_exists( $rewrite, 'get_random_base' ) ) {
			$base = $rewrite->get_random_base();

			if ( is_string( $base ) && $base !== '' ) {
				return $base;
			}
		}

		if ( $rewrite && isset( $rewrite->random_base ) && is_string( $rewrite->random_base ) && $rewrite->random_base !== '' ) {
			return $rewrite->random_base;
		}

		return 'random';
	}

	/**
	 * Reads the index file from a rewrite target.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $target Rewrite target, usually `index.php?...`.
	 * @return string Index file name.
	 */
	private function rewrite_index_from_target( $target ) {

		if ( ! is_string( $target ) ) {
			return 'index.php';
		}

		$query_pos = strpos( $target, '?' );

		if ( $query_pos === false || $query_pos === 0 ) {
			return 'index.php';
		}

		return substr( $target, 0, $query_pos );
	}

	/**
	 * Removes `random` from the public query variables.
	 *
	 * Pretty permalinks are not required. `/?random=1` and `/2026/?random` set
	 * the same variable, and `WP::handle_random()` redirects when it is present.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $query_vars Public query variable names.
	 * @return mixed Query variable names without `random`.
	 */
	public function remove_random_query_var( $query_vars ) {

		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		return array_values( array_diff( $query_vars, [ self::QUERY_VAR ] ) );
	}

	/**
	 * Removes the random query variable from the parsed request.
	 *
	 * A slug of `random` arrives as `pagename`, `name`, or a taxonomy variable.
	 * Those are left in place.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $query_vars Parsed query variables.
	 * @return mixed Query variables without `random`.
	 */
	public function remove_random_request_var( $query_vars ) {

		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		unset( $query_vars[ self::QUERY_VAR ] );

		return $query_vars;
	}

	/**
	 * Removes the random query variable after the request has been parsed.
	 *
	 * Runs after the `request` filter, which is the last point a callback can
	 * put the variable back before `WP::handle_random()`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $wp Current WordPress environment instance.
	 * @return void
	 */
	public function remove_random_from_parsed_request( $wp ) {

		if ( ! is_object( $wp ) || ! isset( $wp->query_vars ) || ! is_array( $wp->query_vars ) ) {
			return;
		}

		unset( $wp->query_vars[ self::QUERY_VAR ] );
	}

	/**
	 * Removes `random` from the main WP object's public query variables.
	 *
	 * The object is created after plugins load, so this waits for `init`.
	 * The `query_vars` filter still covers requests if the list is rebuilt.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_random_query_var_now() {

		if ( ! isset( $GLOBALS['wp'] ) || ! is_object( $GLOBALS['wp'] ) ) {
			return;
		}

		if ( ! method_exists( $GLOBALS['wp'], 'remove_query_var' ) ) {
			return;
		}

		$GLOBALS['wp']->remove_query_var( self::QUERY_VAR );
	}

	/**
	 * Forces one post type out of random content selection.
	 *
	 * Objects from WordPress versions without this feature have no `randomable`
	 * property and are not modified.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_type        Post type key from `registered_post_type`.
	 * @param mixed $post_type_object Registered post type object.
	 * @return void
	 */
	public function disable_post_type( $post_type, $post_type_object ) {

		if ( ! is_scalar( $post_type ) || ! is_object( $post_type_object ) ) {
			return;
		}

		if ( ! property_exists( $post_type_object, 'randomable' ) ) {
			return;
		}

		$post_type_object->randomable = false;
	}

	/**
	 * Forces every already registered post type out of random content selection.
	 *
	 * Covers types registered before this plugin loaded, and types whose flag
	 * was turned back on after `registered_post_type`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function disable_registered_post_types() {

		if ( ! isset( $GLOBALS['wp_post_types'] ) || ! is_array( $GLOBALS['wp_post_types'] ) ) {
			return;
		}

		foreach ( $GLOBALS['wp_post_types'] as $post_type => $post_type_object ) {
			$this->disable_post_type( $post_type, $post_type_object );
		}
	}

	/**
	 * Detaches the callbacks core uses for a random content request.
	 *
	 * `WP::handle_random()` adds the query callbacks just before the main query,
	 * at priority 20. This method is also hooked just before that, so they are
	 * gone before the query runs. The insert and delete callbacks only exist
	 * to regenerate the random rules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_random_handlers() {

		remove_action( 'pre_get_posts', 'wp_random_content_pre_get_posts', 20 );
		remove_filter( 'posts_orderby', 'wp_random_content_posts_orderby', 20 );
		remove_filter( 'post_limits', 'wp_random_content_post_limits', 20 );
		remove_action( 'wp_after_insert_post', 'wp_random_content_flush_rewrite_rules_for_post', 10 );
		remove_action( 'after_delete_post', 'wp_random_content_flush_rewrite_rules_for_post', 10 );
	}

	/**
	 * Determines whether a rewrite target sets the random query variable.
	 *
	 * A target such as `index.php?pagename=random` is a real slug and returns false.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $target Rewrite target, usually an `index.php` query string.
	 * @return bool Whether the target sets the random query variable.
	 */
	private function rule_sets_random_query_var( $target ) {

		if ( ! is_string( $target ) || strpos( $target, self::QUERY_VAR ) === false ) {
			return false;
		}

		$query = wp_parse_url( $target, PHP_URL_QUERY );

		if ( ! is_string( $query ) ) {
			return false;
		}

		$vars = [];
		parse_str( $query, $vars );

		return array_key_exists( self::QUERY_VAR, $vars );
	}
}
