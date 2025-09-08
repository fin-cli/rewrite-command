<?php

use FP_CLI\Utils;

/**
 * Lists or flushes the site's rewrite rules, updates the permalink structure.
 *
 * See the FinPress [Rewrite API](https://codex.finpress.org/Rewrite_API) and
 * [FP Rewrite](https://codex.finpress.org/Class_Reference/FP_Rewrite) class reference.
 *
 * ## EXAMPLES
 *
 *     # Flush rewrite rules
 *     $ fp rewrite flush
 *     Success: Rewrite rules flushed.
 *
 *     # Update permalink structure
 *     $ fp rewrite structure '/%year%/%monthnum%/%postname%'
 *     Success: Rewrite structure set.
 *
 *     # List rewrite rules
 *     $ fp rewrite list --format=csv
 *     match,query,source
 *     ^fp-json/?$,index.php?rest_route=/,other
 *     ^fp-json/(.*)?,index.php?rest_route=/$matches[1],other
 *     category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
 *     category/(.+?)/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
 *     category/(.+?)/embed/?$,index.php?category_name=$matches[1]&embed=true,category
 *
 * @package fp-cli
 */

class Rewrite_Command extends FP_CLI_Command {

	/**
	 * Flushes rewrite rules.
	 *
	 * Resets FinPress' rewrite rules based on registered post types, etc.
	 *
	 * To regenerate a .htaccess file with FP-CLI, you'll need to add the mod_rewrite module
	 * to your fp-cli.yml or config.yml. For example:
	 *
	 * ```
	 * apache_modules:
	 *   - mod_rewrite
	 * ```
	 *
	 * ## OPTIONS
	 *
	 * [--hard]
	 * : Perform a hard flush - update `.htaccess` rules as well as rewrite rules in database. Works only on single site installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fp rewrite flush
	 *     Success: Rewrite rules flushed.
	 */
	public function flush( $args, $assoc_args ) {
		// make sure we detect mod_rewrite if configured in apache_modules in config
		self::apache_modules();

		if ( Utils\get_flag_value( $assoc_args, 'hard' ) && ! in_array( 'mod_rewrite', (array) FP_CLI::get_config( 'apache_modules' ), true ) ) {
			FP_CLI::warning( 'Regenerating a .htaccess file requires special configuration. See usage docs.' );
		}

		if ( Utils\get_flag_value( $assoc_args, 'hard' ) && is_multisite() ) {
			FP_CLI::warning( "FinPress can't generate .htaccess file for a multisite install." );
		}

		self::check_skip_plugins_themes();

		flush_rewrite_rules( Utils\get_flag_value( $assoc_args, 'hard' ) );

		if ( ! get_option( 'rewrite_rules' ) ) {
			FP_CLI::warning( "Rewrite rules are empty, possibly because of a missing permalink_structure option. Use 'fp rewrite list' to verify, or 'fp rewrite structure' to update permalink_structure." );
		} else {
			FP_CLI::success( 'Rewrite rules flushed.' );
		}
	}

	/**
	 * Updates the permalink structure.
	 *
	 * Sets the post permalink structure to the specified pattern.
	 *
	 * To regenerate a .htaccess file with FP-CLI, you'll need to add
	 * the mod_rewrite module to your [FP-CLI config](https://make.finpress.org/cli/handbook/config/#config-files).
	 * For example:
	 *
	 * ```
	 * apache_modules:
	 *   - mod_rewrite
	 * ```
	 *
	 * ## OPTIONS
	 *
	 * <permastruct>
	 * : The new permalink structure to apply.
	 *
	 * [--category-base=<base>]
	 * : Set the base for category permalinks, i.e. '/category/'.
	 *
	 * [--tag-base=<base>]
	 * : Set the base for tag permalinks, i.e. '/tag/'.
	 *
	 * [--hard]
	 * : Perform a hard flush - update `.htaccess` rules as well as rewrite rules in database.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fp rewrite structure '/%year%/%monthnum%/%postname%/'
	 *     Success: Rewrite structure set.
	 */
	public function structure( $args, $assoc_args ) {
		global $fp_rewrite;

		// copypasta from /fp-admin/options-permalink.php

		$blog_prefix = '';
		$prefix      = $blog_prefix;
		if ( is_multisite() && ! is_subdomain_install() && is_main_site() ) {
			$blog_prefix = '/blog';
		}

		$permalink_structure = ( 'default' === $args[0] ) ? '' : $args[0];

		if ( ! empty( $permalink_structure ) ) {
			$permalink_structure = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $permalink_structure ) );
			$permalink_structure = $blog_prefix . $permalink_structure;
		}
		$fp_rewrite->set_permalink_structure( $permalink_structure );

		// Update category or tag bases
		if ( isset( $assoc_args['category-base'] ) ) {

			$category_base = $assoc_args['category-base'];
			if ( ! empty( $category_base ) ) {
				$category_base = $blog_prefix . preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $category_base ) );
			}
			$fp_rewrite->set_category_base( $category_base );
		}

		if ( isset( $assoc_args['tag-base'] ) ) {

			$tag_base = $assoc_args['tag-base'];
			if ( ! empty( $tag_base ) ) {
				$tag_base = $blog_prefix . preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $tag_base ) );
			}
			$fp_rewrite->set_tag_base( $tag_base );
		}

		// make sure we detect mod_rewrite if configured in apache_modules in config
		self::apache_modules();

		FP_CLI::success( 'Rewrite structure set.' );

		// Launch a new process to flush rewrites because core expects flush
		// to happen after rewrites are set
		$cmd = 'rewrite flush';
		if ( Utils\get_flag_value( $assoc_args, 'hard' ) ) {
			$cmd .= ' --hard';
			if ( ! in_array( 'mod_rewrite', (array) FP_CLI::get_config( 'apache_modules' ), true ) ) {
				FP_CLI::warning( 'Regenerating a .htaccess file requires special configuration. See usage docs.' );
			}
		}

		/**
		 * @var object{stdout: string, stderr: string, return_code: int} $process_run
		 */
		$process_run = FP_CLI::runcommand( $cmd );
		if ( ! empty( $process_run->stderr ) ) {
			// Strip "Warning: "
			FP_CLI::warning( substr( $process_run->stderr, 9 ) );
		}
	}

	/**
	 * Gets a list of the current rewrite rules.
	 *
	 * ## OPTIONS
	 *
	 * [--match=<url>]
	 * : Show rewrite rules matching a particular URL.
	 *
	 * [--source=<source>]
	 * : Show rewrite rules from a particular source.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to match,query,source.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ fp rewrite list --format=csv
	 *     match,query,source
	 *     ^fp-json/?$,index.php?rest_route=/,other
	 *     ^fp-json/(.*)?,index.php?rest_route=/$matches[1],other
	 *     category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
	 *     category/(.+?)/(feed|rdf|rss|rss2|atom)/?$,index.php?category_name=$matches[1]&feed=$matches[2],category
	 *     category/(.+?)/embed/?$,index.php?category_name=$matches[1]&embed=true,category
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		global $fp_rewrite;

		$rules = get_option( 'rewrite_rules' );
		if ( ! $rules ) {
			$rules = [];
			FP_CLI::warning( 'No rewrite rules.' );
		}

		/**
		 * @var array<string, string> $rules
		 */

		self::check_skip_plugins_themes();

		$defaults   = [
			'source' => '',
			'match'  => '',
			'format' => 'table',
			'fields' => 'match,query,source',
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		if ( ! empty( $assoc_args['match'] ) ) {
			if ( 0 === stripos( $assoc_args['match'], 'http://' )
				|| 0 === stripos( $assoc_args['match'], 'https://' ) ) {
				$bits                = FP_CLI\Utils\parse_url( $assoc_args['match'] );
				$assoc_args['match'] = ( isset( $bits['path'] ) ? $bits['path'] : '' )
					. ( isset( $bits['query'] ) ? '?' . $bits['query'] : '' );
			}
		}

		$rewrite_rules_by_source             = [];
		$rewrite_rules_by_source['post']     = $fp_rewrite->generate_rewrite_rules( $fp_rewrite->permalink_structure, EP_PERMALINK );
		$rewrite_rules_by_source['date']     = $fp_rewrite->generate_rewrite_rules( $fp_rewrite->get_date_permastruct(), EP_DATE );
		$rewrite_rules_by_source['root']     = $fp_rewrite->generate_rewrite_rules( $fp_rewrite->root . '/', EP_ROOT );
		$rewrite_rules_by_source['comments'] = $fp_rewrite->generate_rewrite_rules( $fp_rewrite->root . $fp_rewrite->comments_base, EP_COMMENTS, true, true, true, false );
		$rewrite_rules_by_source['search']   = $fp_rewrite->generate_rewrite_rules( $fp_rewrite->get_search_permastruct(), EP_SEARCH );
		$rewrite_rules_by_source['author']   = $fp_rewrite->generate_rewrite_rules( $fp_rewrite->get_author_permastruct(), EP_AUTHORS );
		$rewrite_rules_by_source['page']     = $fp_rewrite->page_rewrite_rules();

		// Extra permastructs including tags, categories, etc.
		foreach ( $fp_rewrite->extra_permastructs as $permastructname => $permastruct ) {
			if ( is_array( $permastruct ) ) {
				$rewrite_rules_by_source[ $permastructname ] = $fp_rewrite->generate_rewrite_rules( $permastruct['struct'], $permastruct['ep_mask'], $permastruct['paged'], $permastruct['feed'], $permastruct['forcomments'], $permastruct['walk_dirs'], $permastruct['endpoints'] );
			} else {
				$rewrite_rules_by_source[ $permastructname ] = $fp_rewrite->generate_rewrite_rules( $permastruct, EP_NONE );
			}
		}

		// Apply the filters used in core just in case
		foreach ( $rewrite_rules_by_source as $source => $source_rules ) {
			// phpcs:ignore FinPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Can't prefix dynamic hooks here, calling hooks for custom permastructs.
			$rewrite_rules_by_source[ $source ] = apply_filters( $source . '_rewrite_rules', $source_rules );
			if ( 'post_tag' === $source ) {
				if ( Utils\fp_version_compare( '3.1.0', '>=' ) ) {
					// phpcs:ignore FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native FinPress hook.
					$rewrite_rules_by_source[ $source ] = apply_filters( 'post_tag_rewrite_rules', $source_rules );
				} else {
					// phpcs:ignore FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native FinPress hook.
					$rewrite_rules_by_source[ $source ] = apply_filters( 'tag_rewrite_rules', $source_rules );
				}
			}
		}

		$rule_list = [];
		foreach ( $rules as $match => $query ) {

			if ( ! empty( $assoc_args['match'] ) && ! preg_match( "!^$match!", trim( $assoc_args['match'], '/' ) ) ) {
				continue;
			}

			$source = 'other';
			foreach ( $rewrite_rules_by_source as $rules_source => $source_rules ) {
				if ( array_key_exists( $match, $source_rules ) ) {
					$source = $rules_source;
				}
			}

			if ( ! empty( $assoc_args['source'] ) && $source !== $assoc_args['source'] ) {
				continue;
			}

			$rule_list[] = compact( 'match', 'query', 'source' );
		}

		Utils\format_items( $assoc_args['format'], $rule_list, explode( ',', $assoc_args['fields'] ) );
	}

	/**
	 * Exposes apache modules if present in config
	 *
	 * Implementation Notes: This function exposes a global function
	 * apache_get_modules and also sets the $is_apache global variable.
	 *
	 * This is so that flush_rewrite_rules will actually write out the
	 * .htaccess file for apache FinPress installations. There is a check
	 * to see:
	 *
	 * 1. if the $is_apache variable is set.
	 * 2. if the mod_rewrite module is returned from the apache_get_modules
	 *    function.
	 *
	 * To get this to work with fp-cli you'll need to add the mod_rewrite module
	 * to your config.yml. For example
	 *
	 * ```
	 * apache_modules:
	 *   - mod_rewrite
	 * ```
	 *
	 * If this isn't done then the .htaccess rewrite rules won't be flushed out
	 * to disk.
	 */
	private static function apache_modules() {
		$mods = FP_CLI::get_config( 'apache_modules' );
		if ( ! empty( $mods ) && ! function_exists( 'apache_get_modules' ) ) {
			global $is_apache;
			// phpcs:ignore FinPress.FP.GlobalVariablesOverride.Prohibited
			$is_apache = true;

			// needed for get_home_path() and .htaccess location
			$_SERVER['SCRIPT_FILENAME'] = ABSPATH;

			// @phpstan-ignore function.inner
			function apache_get_modules() { // phpcs:ignore FinPress.NamingConventions.PrefixAllGlobals
				return FP_CLI::get_config( 'apache_modules' );
			}
		}
	}

	/**
	 * Displays a warning if --skip-plugins or --skip-themes are in use.
	 *
	 * Skipping the loading of plugins or themes can mean some rewrite rules
	 * are unregistered, which may cause erroneous behavior.
	 */
	private static function check_skip_plugins_themes() {
		$skipped = [];
		if ( FP_CLI::get_config( 'skip-plugins' ) ) {
			$skipped[] = 'plugins';
		}
		if ( FP_CLI::get_config( 'skip-themes' ) ) {
			$skipped[] = 'themes';
		}
		if ( empty( $skipped ) ) {
			return;
		}
		$skipped = implode( ' and ', $skipped );
		FP_CLI::warning( sprintf( "Some rewrite rules may be missing because %s weren't loaded by FP-CLI.", $skipped ) );
	}
}

FP_CLI::add_command( 'rewrite', 'Rewrite_Command' );
