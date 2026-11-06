<?php
/**
 * Minimal WordPress hook API for command-line tests.
 *
 * Records callbacks in the same order WordPress runs them. It does not load
 * WordPress, talk to a database, or define the random-content APIs.
 *
 * @package No_Random_Posts
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Stand-in for wp_parse_url().
	 *
	 * @param string $url       URL to parse.
	 * @param int    $component Optional. Component to retrieve. Default -1.
	 * @return array|string|int|null|false Same shape as parse_url().
	 */
	function wp_parse_url( $url, $component = -1 ) {

		if ( $component === -1 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- This stand-in is the wp_parse_url() implementation.
			return parse_url( $url );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- This stand-in is the wp_parse_url() implementation.
		return parse_url( $url, $component );
	}
}

if ( ! isset( $GLOBALS['wp_filter'] ) || ! is_array( $GLOBALS['wp_filter'] ) ) {
	$GLOBALS['wp_filter'] = array();
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Registers a filter callback.
	 *
	 * @param string   $hook_name     Filter name.
	 * @param callable $callback      Callback to run.
	 * @param int      $priority      Optional. Order within the hook. Default 10.
	 * @param int      $accepted_args Optional. Arguments passed to the callback. Default 1.
	 * @return true
	 */
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {

		$GLOBALS['wp_filter'][ $hook_name ][ $priority ][] = array(
			'function'      => $callback,
			'accepted_args' => $accepted_args,
		);

		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Registers an action callback.
	 *
	 * @param string   $hook_name     Action name.
	 * @param callable $callback      Callback to run.
	 * @param int      $priority      Optional. Order within the hook. Default 10.
	 * @param int      $accepted_args Optional. Arguments passed to the callback. Default 1.
	 * @return true
	 */
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {

		return add_filter( $hook_name, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	/**
	 * Removes a filter callback.
	 *
	 * @param string   $hook_name Filter name.
	 * @param callable $callback  Callback to remove.
	 * @param int      $priority  Optional. Priority the callback was registered at. Default 10.
	 * @return bool Whether a callback was removed.
	 */
	function remove_filter( $hook_name, $callback, $priority = 10 ) {

		if ( ! isset( $GLOBALS['wp_filter'][ $hook_name ][ $priority ] ) ) {
			return false;
		}

		$removed = false;

		foreach ( $GLOBALS['wp_filter'][ $hook_name ][ $priority ] as $index => $filter ) {
			if ( $filter['function'] === $callback ) {
				unset( $GLOBALS['wp_filter'][ $hook_name ][ $priority ][ $index ] );
				$removed = true;
			}
		}

		return $removed;
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	/**
	 * Removes an action callback.
	 *
	 * @param string   $hook_name Action name.
	 * @param callable $callback  Callback to remove.
	 * @param int      $priority  Optional. Priority the callback was registered at. Default 10.
	 * @return bool Whether a callback was removed.
	 */
	function remove_action( $hook_name, $callback, $priority = 10 ) {

		return remove_filter( $hook_name, $callback, $priority );
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	/**
	 * Checks whether a filter has callbacks.
	 *
	 * @param string          $hook_name Filter name.
	 * @param callable|false  $callback  Optional. Specific callback to find. Default false.
	 * @return int|true|false Priority when a callback is given and found, true when the hook has any callback, otherwise false.
	 */
	function has_filter( $hook_name, $callback = false ) {

		if ( empty( $GLOBALS['wp_filter'][ $hook_name ] ) || ! is_array( $GLOBALS['wp_filter'][ $hook_name ] ) ) {
			return false;
		}

		if ( $callback === false ) {
			foreach ( $GLOBALS['wp_filter'][ $hook_name ] as $callbacks ) {
				if ( $callbacks !== array() ) {
					return true;
				}
			}

			return false;
		}

		foreach ( $GLOBALS['wp_filter'][ $hook_name ] as $priority => $callbacks ) {
			foreach ( $callbacks as $filter ) {
				if ( $filter['function'] === $callback ) {
					return (int) $priority;
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'has_action' ) ) {
	/**
	 * Checks whether an action has callbacks.
	 *
	 * @param string         $hook_name Action name.
	 * @param callable|false $callback  Optional. Specific callback to find. Default false.
	 * @return int|true|false Priority when a callback is given and found, true when the hook has any callback, otherwise false.
	 */
	function has_action( $hook_name, $callback = false ) {

		return has_filter( $hook_name, $callback );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Runs callbacks registered on a filter.
	 *
	 * @param string $hook_name Filter name.
	 * @param mixed  $value     Value to filter.
	 * @param mixed  ...$args   Further arguments passed to callbacks.
	 * @return mixed Filtered value.
	 */
	function apply_filters( $hook_name, $value, ...$args ) {

		if ( empty( $GLOBALS['wp_filter'][ $hook_name ] ) || ! is_array( $GLOBALS['wp_filter'][ $hook_name ] ) ) {
			return $value;
		}

		$priorities = array_keys( $GLOBALS['wp_filter'][ $hook_name ] );
		sort( $priorities, SORT_NUMERIC );

		foreach ( $priorities as $priority ) {
			if ( ! isset( $GLOBALS['wp_filter'][ $hook_name ][ $priority ] ) || $GLOBALS['wp_filter'][ $hook_name ][ $priority ] === array() ) {
				continue;
			}

			// Copy this priority so a callback can remove a later priority before it runs.
			$callbacks = $GLOBALS['wp_filter'][ $hook_name ][ $priority ];

			foreach ( $callbacks as $filter ) {
				$call_args = array_merge( array( $value ), $args );
				$accepted  = max( 0, (int) $filter['accepted_args'] );
				$value     = call_user_func_array( $filter['function'], array_slice( $call_args, 0, $accepted ) );
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Runs callbacks registered on an action.
	 *
	 * String callbacks that are not defined are recorded and skipped, so a test
	 * can prove a core handler was not left in place without defining that function.
	 *
	 * @param string $hook_name Action name.
	 * @param mixed  ...$args   Arguments passed to callbacks.
	 * @return void
	 */
	function do_action( $hook_name, ...$args ) {

		if ( empty( $GLOBALS['wp_filter'][ $hook_name ] ) || ! is_array( $GLOBALS['wp_filter'][ $hook_name ] ) ) {
			return;
		}

		$priorities = array_keys( $GLOBALS['wp_filter'][ $hook_name ] );
		sort( $priorities, SORT_NUMERIC );

		foreach ( $priorities as $priority ) {
			if ( ! isset( $GLOBALS['wp_filter'][ $hook_name ][ $priority ] ) || $GLOBALS['wp_filter'][ $hook_name ][ $priority ] === array() ) {
				continue;
			}

			// Copy this priority so a callback can remove a later priority before it runs.
			$callbacks = $GLOBALS['wp_filter'][ $hook_name ][ $priority ];

			foreach ( $callbacks as $filter ) {
				$callback = $filter['function'];

				if ( is_string( $callback ) && ! function_exists( $callback ) ) {
					$GLOBALS['wp_missing_callbacks'][] = array(
						'hook'     => $hook_name,
						'callback' => $callback,
						'priority' => $priority,
					);
					continue;
				}

				$accepted = max( 0, (int) $filter['accepted_args'] );
				call_user_func_array( $callback, array_slice( $args, 0, $accepted ) );
			}
		}
	}
}
