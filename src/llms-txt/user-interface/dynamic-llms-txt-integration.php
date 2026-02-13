<?php

namespace Yoast\WP\SEO\Llms_Txt\User_Interface;

use Yoast\WP\SEO\Conditionals\No_Conditionals;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Integrations\Integration_Interface;
use Yoast\WP\SEO\Llms_Txt\Application\Markdown_Builders\Markdown_Builder;

/**
 * Handles dynamic llms.txt generation for multisite environments.
 *
 * This integration provides dynamic llms.txt generation via rewrite rules, similar
 * to how WordPress handles robots.txt on multisite. It intercepts requests to llms.txt
 * and generates the content on-the-fly, avoiding file system permission issues.
 */
class Dynamic_Llms_Txt_Integration implements Integration_Interface {

	use No_Conditionals;

	/**
	 * The markdown builder.
	 *
	 * @var Markdown_Builder
	 */
	private $markdown_builder;

	/**
	 * The options helper.
	 *
	 * @var Options_Helper
	 */
	private $options_helper;

	/**
	 * Constructor.
	 *
	 * @param Markdown_Builder $markdown_builder The markdown builder.
	 * @param Options_Helper   $options_helper   The options helper.
	 */
	public function __construct(
		Markdown_Builder $markdown_builder,
		Options_Helper $options_helper
	) {
		$this->markdown_builder = $markdown_builder;
		$this->options_helper   = $options_helper;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'init', [ $this, 'add_rewrite_rule' ] );
		\add_filter( 'query_vars', [ $this, 'add_query_var' ] );
		\add_action( 'template_redirect', [ $this, 'serve_dynamic_llms_txt' ], 1 );
	}

	/**
	 * Adds the llms.txt rewrite rule.
	 *
	 * @return void
	 */
	public function add_rewrite_rule() {
		\add_rewrite_rule( '^llms\.txt$', 'index.php?yoast_llms_txt=1', 'top' );
	}

	/**
	 * Adds the query var for llms.txt detection.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_var( $vars ) {
		$vars[] = 'yoast_llms_txt';
		return $vars;
	}

	/**
	 * Generates and serves the llms.txt content dynamically.
	 *
	 * This method checks if the current request is for llms.txt and if the feature
	 * is enabled. If so, it generates and outputs the content, then exits.
	 *
	 * @return void
	 */
	public function serve_dynamic_llms_txt() {
		// Check if this is a llms.txt request.
		if ( ! \get_query_var( 'yoast_llms_txt' ) ) {
			return;
		}

		// Check if llms.txt is enabled.
		if ( ! $this->options_helper->get( 'enable_llms_txt', false ) ) {
			\status_header( 404 );
			\nocache_headers();
			echo 'llms.txt is not enabled';
			exit;
		}

		/**
		 * Fires when displaying the llms.txt file.
		 *
		 * @since 1.0.0 (Yoast SEO)
		 */
		\do_action( 'wpseo_do_llmstxt' );

		// Set headers.
		\nocache_headers();
		\header( 'Content-Type: text/plain; charset=utf-8' );

		// Generate and output content.
		$content = $this->markdown_builder->render();

		/**
		 * Filters the llms.txt output.
		 *
		 * @since 1.0.0 (Yoast SEO)
		 *
		 * @param string $content The llms.txt content.
		 */
		$content = \apply_filters( 'wpseo_llmstxt_content', $content );

		echo $content;
		exit;
	}
}
