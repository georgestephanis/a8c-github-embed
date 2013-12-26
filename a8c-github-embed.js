
jQuery(document).ready( function( $ ) {
	'use strict';

	var data,
		$wrapper,
		$header,
		$pre,
		$meta;

	function processGitHubResponse( data, $wrapper ) {
		return function( response ) {
			window.console.log( data );
			window.console.log( response );

			$wrapper.attr( 'data-type', data.type );
			$wrapper.attr( 'data-state', response.state );

			$header = $( '<div class="github-embed-header" />').prependTo( $wrapper );
			$pre    = $( '<pre />' ).appendTo( $wrapper );
			$meta   = $( '<div class="github-embed-meta" />').appendTo( $wrapper );

			switch ( data.type ) {

				case 'pulls':
				case 'pull':
					$header.append( '<span class="state state-' + response.state + '" style="float:right">' +
										response.state +
									'</span>' );
					$header.append( 'Pull Request on <a href="https://github.com/' + data.account + '/' + data.repository + '">' +
										data.account + '/' + data.repository +
									'</a>' );
					$pre.text( response.body );
					$meta.append( '<span style="float:right">' +
										'<a href="' + response.patch_url + '">view patch</a>' +
										' or <a href="' + response.diff_url + '">diff</a>' +
									'</span>' );
					$meta.append( 'By <a href="' + response.user.url + '">' +
									response.user.login +
								'</a> on ' +
								'<a href="' + response.html_url + '">' +
									new Date( response.created_at ).toLocaleString('XXX') +
								'</a>' );
					break;

				case 'issues':
				case 'issue':
					$header.append( '<span class="state state-' + response.state + '" style="float:right">' +
										response.state +
									'</span>' );
					$header.append( 'Issue on <a href="https://github.com/' + data.account + '/' + data.repository + '">' +
						data.account + '/' + data.repository +
					'</a>' );
					$pre.text( response.body );
					if ( undefined !== response.patch_url ) {
						$meta.append( '<span style="float:right">' +
											'<a href="' + response.patch_url + '">view patch</a>' +
											' or <a href="' + response.diff_url + '">diff</a>' +
										'</span>' );
					}
					$meta.append( 'By <a href="' + response.user.url + '">' +
									response.user.login +
								'</a> on ' +
								'<a href="' + response.html_url + '">' +
									new Date( response.created_at ).toLocaleString('XXX') +
								'</a>' );
					break;

				case undefined:
				case 'commit':
					$header.append( 'Commit on <a href="https://github.com/' + data.account + '/' + data.repository + '">' +
						data.account + '/' + data.repository +
					'</a>' );
					$pre.text( response.message );
					$meta.append( 'By ' + response.author.name + ' on ' +
								'<a href="' + response.html_url + '">' +
									new Date( response.author.date ).toLocaleString('XXX') +
								'</a>' );
					break;

			}
		};
	}

	$('.github-embed').each( function( index, element ) {
		data     = $( element ).data();
		$wrapper = $( element ).wrap( '<div class="github-embed-wrap" />' ).parent();

		if ( undefined === data.type ) {
			data.type = 'commit';
		}

		$.ajax( $( element ).attr('href'), {
			dataType : 'json',
			success  : processGitHubResponse( data, $wrapper )
		} );
	} );

} );
