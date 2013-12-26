<?php
/*
Plugin Name: A8c GitHub Embed
Plugin URI: http://github.com/georgestephanis/a8c-github-embed
Description: Pulls in details of issues, pull requests, commits, and gists from GitHub to embed.
Author: George Stephanis
Contributors: miyauchi
Version: 0.1
Author URI: http://stephanis.info
*/

class A8c_GitHub_Embed {

	/**
	 * Just enqueue things for later.
	 */
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init',           array( $this, 'register_scripts_styles' ) );
	}

	/**
	 * Kick off the embeds and shortcodes.
	 */
	function plugins_loaded() {
		// Embed Handlers for pasting URLs in directly.
		$gist_regex          = '~https://gist.github.com/([^\/]+\/)?([a-zA-Z0-9]+)(\#file(\-|_)(.+))?$~i';
		$github_commit_regex = '~https://github.com/([^\/]+)/([^\/]+)/commit/([a-f\d]{40})/?(.*)$~i';
		$github_issue_regex  = '~https://github.com/([^\/]+)/([^\/]+)/(issues|pull)/([\d]+)/?(.*)$~i';;
		wp_embed_register_handler( 'gist',          $gist_regex,          array( $this, 'gist_handler' ) );
		wp_embed_register_handler( 'github_commit', $github_commit_regex, array( $this, 'github_commit_handler' ) );
		wp_embed_register_handler( 'github_issue',  $github_issue_regex,  array( $this, 'github_issue_handler' ) );

		// And the shortcodes that really do the work.
		add_shortcode( 'gist',          array( $this, 'gist_shortcode' ) );
		add_shortcode( 'github_commit', array( $this, 'github_commit_shortcode' ) );
		add_shortcode( 'github_issue',  array( $this, 'github_issue_shortcode' ) );
	}

	/**
	 * Register the scripts that we use, so they can be enqueued later if needed.
	 */
	function register_scripts_styles() {
		$script_ver = md5_file( plugin_dir_path( __FILE__ ) . 'a8c-github-embed.js' );
		wp_register_script( 'a8c-github-embed', plugins_url( 'a8c-github-embed.js',  __FILE__ ), array( 'jquery' ), $script_ver, 'footer' );

		$style_ver  = md5_file( plugin_dir_path( __FILE__ ) . 'a8c-github-embed.css' );
		wp_register_style(  'a8c-github-embed', plugins_url( 'a8c-github-embed.css', __FILE__ ), null, $style_ver );
	}

	/**
	 * Converts an inline URL into the [gist] shortcode.
	 * Adapted from http://wordpress.org/plugins/oembed-gist/ props @miyauchi
	 */
	function gist_handler( $matches ) {
		$id   = sanitize_key( $matches[2] );
		$file = ( empty( $matches[3] ) || empty( $matches[5] ) ) ? null : sanitize_key( $matches[5] );

		return "[gist id='{$id}' file='{$file}']";
	}

	/**
	 * Converts an inline URL into the [gist] shortcode.
	 */
	function github_commit_handler( $matches ) {
		$account    = sanitize_key( $matches[1] );
		$repository = sanitize_key( $matches[2] );
		$commit     = sanitize_key( $matches[3] );

		return "[github_commit account='{$account}' repository='{$repository}' commit='{$commit}']";
	}

	/**
	 * Converts an inline URL into the [gist] shortcode.
	 */
	function github_issue_handler( $matches ) {
		$account    = sanitize_key( $matches[1] );
		$repository = sanitize_key( $matches[2] );
		$type       = sanitize_key( $matches[3] ); // (issue|pull)
		$issue      = intval( $matches[4] );

		return "[github_issue account='{$account}' repository='{$repository}' type='{$type}' issue='{$issue}']";
	}

	/**
	 * Process the [gist] shortcode.
	 * Adapted from http://wordpress.org/plugins/oembed-gist/ props @miyauchi
	 */
	function gist_shortcode( $params ) {
		$defaults = array(
			'id'   => null,
			'file' => null,
		);
		$params = shortcode_atts( $defaults, $params, 'gist' );

		if ( ctype_xdigit( $params['id'] ) ) {
			$id       = sanitize_key( $params['id'] );
			$file     = $params['file'] ? $params['file'] : '';
			$noscript = sprintf( __( '<p>View the code on <a href="https://gist.github.com/%1$s">Gist</a>.</p>' ), $id );
			$file_arg = $file ? '?file=' . preg_replace( '/[\-\.]([a-z\d]+)$/i', '.\1', $file ) : '';
			$html     = '<script src="https://gist.github.com/%1$s.js%2$s"></script><noscript>%3$s</noscript>';

			return sprintf( $html, $id, $file_arg, $noscript );
		}

		return '<code>' . esc_html__( 'The gist id was blank or invalid.' ) . '</code>';
	}

	/**
	 * Handle the display of GitHub Commits
	 *
	 * [github_commit]
	 */
	function github_commit_shortcode( $params ) {
		$defaults = array(
			'account'    => null,
			'repository' => null,
			'commit'     => null,
		);
		$params = shortcode_atts( $defaults, $params, 'github_commit' );

		if ( ctype_xdigit( $params['commit'] ) && 40 == strlen( $params['commit'] ) ) {
			$url = sprintf(
				'https://api.github.com/repos/%1$s/%2$s/git/commits/%3$s',
				$params['account'],
				$params['repository'],
				$params['commit']
			);

			$return = sprintf( '<a href="%1$s" class="github-embed github-embed-commit" data-account="%2$s" data-repository="%3$s" data-commit="%4$s">%1$s</a>',
				esc_url( $url ),
				esc_attr( $params['account'] ),
				esc_attr( $params['repository'] ),
				esc_attr( $params['commit'] )
			);

			wp_enqueue_script( 'a8c-github-embed' );
			wp_enqueue_style( 'a8c-github-embed' );
			return $return;
		}

		return '<code>' . esc_html__( 'The hexadecimal commit was invalid.' ) . '</code>';
	}

	/**
	 * Handle the display of GitHub Issues
	 *
	 * [github_issue]
	 */
	function github_issue_shortcode( $params ) {
		$defaults = array(
			'account'    => null,
			'repository' => null,
			'type'       => 'issues',
			'issue'      => null,
		);
		$params = shortcode_atts( $defaults, $params, 'github_issue' );

		if ( $params['type'] == 'pull' ) {
			$params['type'] = 'pulls';
		}

		if ( $params['type'] == 'issue' ) {
			$params['type'] = 'issues';
		}

		$url = sprintf(
			'https://api.github.com/repos/%1$s/%2$s/%3$s/%4$d',
			$params['account'],
			$params['repository'],
			$params['type'],
			$params['issue']
		);

		$return = sprintf( '<a href="%1$s" class="github-embed github-embed-%4$s" data-account="%2$s" data-repository="%3$s" data-type="%4$s" data-issue="%5$s">%1$s</a>',
			esc_url( $url ),
			esc_attr( $params['account'] ),
			esc_attr( $params['repository'] ),
			esc_attr( $params['type'] ),
			esc_attr( $params['issue'] )
		);

		wp_enqueue_script( 'a8c-github-embed' );
		wp_enqueue_style( 'a8c-github-embed' );
		return $return;
	}
}
new A8c_GitHub_Embed;
