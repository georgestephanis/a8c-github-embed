<?php

/**
 * Jetpack-only for now, default to Automattic/Jetpack if account or repository aren't specified.
 */
add_filter( 'shortcode_atts_github_issue', 'jetpack_filter_github_shortcodes', 10, 3 );
add_filter( 'shortcode_atts_github_commit', 'jetpack_filter_github_shortcodes', 10, 3 );
function jetpack_filter_github_shortcodes( $out, $pairs, $atts ) {
	if ( isset( $pairs['account'] ) && empty( $atts['account'] ) ) {
		$out['account'] = 'automattic';
	}
	if ( isset( $pairs['repository'] ) && empty( $atts['repository'] ) ) {
		$out['repository'] = 'jetpack';
	}
	return $out;
}
