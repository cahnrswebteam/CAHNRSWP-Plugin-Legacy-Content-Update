<?php
/*
Plugin Name: CAHNRSWP Legacy Content Update
Description: A utility to help update layouts from the legacy WSU theme to the new page builder plugin.
Author: CAHNRS, philcable
Version: 0.0.1
*/

class CAHNRSWP_Legacy_Content_Update {

	/**
	 * Start the plugin and apply associated hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Add options page link to the menu.
	 */
	public function admin_menu() {
		add_submenu_page( 'options-general.php', 'Update Legacy Layouts', 'Update Legacy Layouts', 'manage_options', 'update-legacy-layouts', array( $this, 'update_legacy_layouts_page' ) );
	}

	/**
	 * Options page content.
	 */
	public function update_legacy_layouts_page() {

		// Loop through posts.
		$posts_array = get_posts( array(
			'posts_per_page' => -1,
			'post_type'      => 'post'
		) );

		$legacy_layout_posts = array();
		foreach ( $posts_array as $post ) {
			$more_tags = '';
			$layout_meta = '';
			$pieces = '';
			$pb_content = '';
			//setup_postdata( $post ); // Doesn't seem necessary
			$more_tags = (int) substr_count( $post->post_content, '<!--more-->' );
			$layout_meta = get_post_meta( $post->ID, '_layout', true );
			if ( $more_tags > 1 && $layout_meta ) { // Check for two More tags, as the first is not used for layout.

				// If the post seems to have a layout from the legacy WSU theme, add it to the $legacy_layout_posts array.
				$legacy_layout_posts[] = $post->ID;

				// Update posts.
				if ( isset( $_POST['submit'] ) ) {

					$pieces = explode( '<!--more-->', $post->post_content );

					switch ( $layout_meta['layout'] ) {
						case '0':
							$layout = 'single';
							break;
						case '1':
							$layout = 'side-right';
							break;
						case '2':
							$layout = 'halves';
							break;
						case '4':
							$layout = 'thirds';
							break;
						case '5':
							$layout = 'quarters';
							break;
						default:
							$layout = 'single';
					}

					// Row.
					$pb_content = '[row layout="' . $layout . '" padding="pad-ends" gutter="gutter"]';
					// Column One.
					//$pb_content .= '[column verticalbleed="0"][textblock]' . "\n\n" . $pieces[0] . "\n<!--more-->\n" . $pieces[1] . "\n\n" . '[/textblock][/column]';
					$pb_content .= '[column][textblock]' . "\n\n" . $pieces[0] . "\n\n" . $pieces[1] . "\n\n" . '[/textblock][/column]';
					// Column Two (we have at least two if we're even in here).
					$pb_content .= '[column][textblock]' . "\n\n" . $pieces[2] . "\n\n" . '[/textblock][/column]';
					// Column Three.
					if ( $pieces[3] && ( 'thirds' == $layout || 'quarters' == $layout ) ) {
						$pb_content .= '[column][textblock]' . "\n\n" . $pieces[3] . "\n\n" . '[/textblock][/column]';
					}
					// Column Four.
					if ( $pieces[4] && 'quarters' === $layout ) {
						$pb_content .= '[column][textblock]' . "\n\n" . $pieces[4] . "\n\n" . '[/textblock][/column]';
					}
					// Close row.
					$pb_content .= '[/row]';

					// Update post with short coded content.
					wp_update_post( array(
						'ID'           => $post->ID,
						'post_content' => $pb_content,
					) );

					//update_post_meta( $post->ID, '_cpb_excerpt', $pieces[0] );
					// Set pagebuilder as on (not sure if we want to do this unless PB is enabled for posts).
					update_post_meta( $post->ID, '_cpb_pagebuilder', 1 );

					// Delete the meta data.
					delete_post_meta( $post->ID, '_layout' );
				}
			}

		}
		//wp_reset_postdata();

		// Loop through pages.
		$pages_array = get_pages();
		$legacy_layout_pages = array();

		foreach ( $pages_array as $page ) {
			$more_tags = '';
			$layout_meta = '';
			$dynamic_meta = '';
			$pieces = '';
			$pb_content = '';
			$more_tags = (int) substr_count( $page->post_content, '<!--more-->' );
			$layout_meta = get_post_meta( $page->ID, '_layout', true );
			$dynamic_meta = get_post_meta( $page->ID, '_dynamic', true );
			if ( $more_tags > 0 || ( $layout_meta || $dynamic_meta ) ) { // Presumably, any More tag in a page is for layout.

				// If the page seems to have a layout from the legacy WSU theme, add it to the $legacy_layout_pages array.
				$legacy_layout_pages[] = $page->ID;

				// Update pages
				if ( isset( $_POST['submit'] ) ) {

					$pieces = explode( '<!--more-->', $page->post_content );

					switch ( $layout_meta['layout'] ) {
						case '0':
							$layout = 'single';
							break;
						case '1':
							$layout = 'side-right';
							break;
						case '2':
							$layout = 'halves';
							break;
						case '4':
							$layout = 'thirds';
							break;
						case '5':
							$layout = 'quarters';
							break;
						default:
							$layout = 'single';
					}

					// Slideshow.
					if ( 'dynamic' == $layout_meta['page_type'] && 'show' == $layout_meta['slideshow'] && $dynamic_meta['wipHomeArray'] ) {
						$pb_content .= '[row layout="single" padding="pad-bottom" gutter="gutter"]';
						$pb_content .= $this->dynamic_column( $dynamic_meta['wipHomeArray'], array(), $dynamic_meta );
						$pb_content .= '[/row]';
					}

					// Row.
					$pb_content .= '[row layout="' . $layout . '" padding="pad-ends" gutter="gutter"]';

					if ( 'dynamic' == $layout_meta['page_type'] && $dynamic_meta ) {

						// Column one.
						if ( $dynamic_meta['wipMainArray'] ) {
							$pb_content .= $this->dynamic_column( $dynamic_meta['wipMainArray'], $pieces, $dynamic_meta );
						} else {// Sometimes a page will be set as dynamic but not have page content set in the columns...
							$pb_content .= '[column][textblock]' . "\n\n" . $pieces[0] . "\n\n" . '[/textblock][/column]';
						}

						// Column two.
						if ( 'side-right' == $layout || 'halves' == $layout || 'thirds' == $layout || 'quarters' == $layout ) {
							if ( $dynamic_meta['wipSecondaryArray'] ) {
								$pb_content .= $this->dynamic_column( $dynamic_meta['wipSecondaryArray'], $pieces, $dynamic_meta );
							} else {
								// Column two (we have at least two if we're even in here).
								$pb_content .= '[column][textblock]' . "\n\n" . $pieces[1] . "\n\n" . '[/textblock][/column]';
							}
						}

						// Column three.
						if ( 'thirds' == $layout || 'quarters' == $layout ) {
							if ( $dynamic_meta['wipAdditionalArray'] ) {
								$pb_content .= $this->dynamic_column( $dynamic_meta['wipAdditionalArray'], $pieces, $dynamic_meta );
							} else if ( $pieces[2] ) {
								$pb_content .= '[column][textblock]' . "\n\n" . $pieces[2] . "\n\n" . '[/textblock][/column]';
							}
						}

						// Column four.
						if ( 'quarters' == $layout ) {
							if ( $dynamic_meta['wipFourthArray'] ) {
								$pb_content .= $this->dynamic_column( $dynamic_meta['wipFourthArray'], $pieces, $dynamic_meta );
							} else if ( $pieces[3] ) {
								$pb_content .= '[column][textblock]' . "\n\n" . $pieces[3] . "\n\n" . '[/textblock][/column]';
							}
						}

					} else { // Non-dynamic pages are easy by comparison.

						// Column one.
						$pb_content .= '[column][textblock]' . "\n\n" . $pieces[0] . "\n\n" . '[/textblock][/column]';

						// Column two (we have at least two if we're even in here).
						$pb_content .= '[column][textblock]' . "\n\n" . $pieces[1] . "\n\n" . '[/textblock][/column]';

						// Column three.
						if ( $pieces[2] && ( 'thirds' == $layout || 'quarters' == $layout ) ) {
							$pb_content .= '[column][textblock]' . "\n\n" . $pieces[2] . "\n\n" . '[/textblock][/column]';
						}

						// Column four.
						if ( $pieces[3] && 'quarters' == $layout ) {
							$pb_content .= '[column][textblock]' . "\n\n" . $pieces[3] . "\n\n" . '[/textblock][/column]';
						}

					}

					// Close row.
					$pb_content .= '[/row]';

					// Update post with short coded content.
					wp_update_post( array(
						'ID'           => $page->ID,
						'post_content' => $pb_content,
					) );

					// Set Page Builder excerpt.
					update_post_meta( $page->ID, '_cpb_excerpt', $pieces[0] );

					// Set Page Builder as on.
					update_post_meta( $post->ID, '_cpb_pagebuilder', 1 );

					// Delete the legacy meta.
					delete_post_meta( $page->ID, '_layout' );
					delete_post_meta( $page->ID, '_dynamic' );

				}
			}
		}

		?>
		<div class="wrap">
			<h2>Update Legacy Content Layouts</h2>
			<form method="post" action="">
				<?php if ( ! empty( $legacy_layout_posts ) || ! empty( $legacy_layout_pages ) || isset( $_POST['submit'] ) ) : ?>
				<table class="form-table">
					<?php if ( ! empty( $legacy_layout_pages ) ) : ?>
					<tr valign="top">
						<th scope="row">Pages</th>
						<td>
							<?php if ( ! isset( $_POST['submit'] ) ) : ?><p>The following pages have been identified as having legacy layouts.</p><?php endif; ?>
							<ul>
							<?php
								foreach ( $legacy_layout_pages as $page_id ) {
									$title = '<a href="' . get_the_permalink( $page_id ) . '" target="_blank">' . get_the_title( $page_id ) . '</a>';
									echo '<li>' . ( ( isset( $_POST['submit'] ) ) ? '<strong>' . $title . '</strong> updated' : $title ) . '</li>';
								}
							?>
							</ul>
						</td>
					</tr>
					<?php endif; ?>
					<?php if ( ! empty( $legacy_layout_posts ) ) : ?>
					<tr valign="top">
						<th scope="row">Posts</th>
						<td>
							<?php if ( ! isset( $_POST['submit'] ) ): ?><p>The following posts have been identified as having legacy layouts.</p><?php endif; ?>
							<ul>
							<?php
								foreach ( $legacy_layout_posts as $post_id ) {
									$title = '<a href="' . get_the_permalink( $post_id ) . '" target="_blank">' . get_the_title( $post_id ) . '</a>';
									echo '<li>' . ( ( isset( $_POST['submit'] ) ) ? '<strong>' . $title . '</strong> updated' : $title ) . '</li>';
								}
							?>
							</ul>
						</td>
					</tr>
					<?php endif; ?>
				</table>

					<?php submit_button( 'Update' ); ?>

				<?php else: ?>

					<p>Your content doesn't seem to have any legacy layouts.</p>

				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Modify ouput of dynamic columns.
	 *
	 * @param array $column_array Content types included in this column.
	 * @param array $pieces       Content (text) from the page itself.
	 * @param array $dynamic_meta All dynamic meta data.
	 *
	 * @return string Column data parsed into shortcode format.
	 */
	public function dynamic_column( $column_array, $pieces, $dynamic_meta ) {

		$content_types = explode( ',', $column_array );

		$content .= "[column]\n\n";

		foreach( $content_types as $content_type ) {

			// Page content.
			if ( 'cTypePage' == substr( $content_type, 0, 9 ) ) {
				$content .= '[textblock]' . $pieces[substr( $content_type, 11 )] . "\n\n[/textblock]";
			}

			// Posts
			if ( 'cTypePosts' == substr( $content_type, 0, 10 ) ) {
				$count = $dynamic_meta[$content_type.'_number'] ? $dynamic_meta[$content_type.'_number'] : 5;
				$category = $dynamic_meta[$content_type.'_category'] ? get_term_by( 'id', $dynamic_meta[$content_type.'_category'], 'category') : '';
				$content .= '[list source="feed" post_type="post" taxonomy="category" terms="' . $category->name . '" posts_per_page="' . $count . '"][/list]';
			}

			// Employees
			/*if ( 'cTypeEmployees' == substr( $content_type, 0, 14 ) ) {
				$content .= '[wsuwp_people]';
			}*/

			// Links (No idea... HTML snippet[s]?)
			/*if ( 'cTypeLinks' == substr( $content_type, 0, 10 ) ) {
				$count = $dynamic_meta[$content_type.'_number'] ? $dynamic_meta[$content_type.'_number'] : 5;
				$category = $dynamic_meta[$content_type.'_category'] ? get_term_by( 'id', $dynamic_meta[$content_type.'_category'], 'category') : '';
				$content .= '[feed feed_type="basic" post_type="link" taxonomy="category" tax_terms="' . $category->name . '" display="list" posts_per_page="' . $count . '"][/feed]';
			}*/

			// Documents (Consolidate into Posts [as a feed])
			/*if ( 'cTypeDocs' == substr( $content_type, 0, 9 ) ) {
				$content .= '';
			}*/

		}

		$content .= '[/column]';

		return $content;

	}

}

new CAHNRSWP_Legacy_Content_Update();