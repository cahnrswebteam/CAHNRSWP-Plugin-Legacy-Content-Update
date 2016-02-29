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
		add_submenu_page( 'tools.php', 'Update Legacy Layouts', 'Update Legacy Layouts', 'manage_options', 'update-legacy-layouts', array( $this, 'update_legacy_layouts_page' ) );
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
			$pb1_layout_meta  = '';
			$pb1_content_meta = '';
			$more_tags        = '';
			$wip_layout_meta  = '';
			$pieces           = '';
			$pb_content       = '';
			$pb1_layout_meta  = get_post_meta( $post->ID, '_cahnrs_layout', true );
			$pb1_content_meta = get_post_meta( $post->ID, '_pagebuilder_editor', true );
			$more_tags        = (int) substr_count( $post->post_content, '<!--more-->' );
			$wip_layout_meta  = get_post_meta( $post->ID, '_layout', true );
			//setup_postdata( $post ); // Doesn't seem necessary

			// Pagebuilder generation 1.
			if ( $pb1_layout_meta ) {

				// Add post to the $legacy_layout_posts array.
				$legacy_layout_posts[] = $post->ID;

				// Update posts.
				if ( isset( $_POST['submit'] ) ) {

					// Send stuff to pb1 function for processing.
					$pb_content .= $this->pb1_layout( $pb1_layout_meta, $pb1_content_meta );

					// Update post with shortcoded content.
					wp_update_post( array(
						'ID'           => $post->ID,
						'post_content' => $pb_content,
					) );

					// Delete WIP layout meta (it might be in there).
					delete_post_meta( $post->ID, '_layout' );

					// Delete pb1 meta.
					delete_post_meta( $post->ID, '_cahnrs_layout' );
					delete_post_meta( $post->ID, '_pagebuilder_settings' );
					delete_post_meta( $post->ID, '_pagebuilder_editor' );

				}

			}

			// WIP/WSU theme/
			if ( ! $pb1_layout_meta && ( $more_tags > 1 && $wip_layout_meta ) ) { // Check for two More tags, as the first is not used for layout.

				// Add post to the $legacy_layout_posts array.
				$legacy_layout_posts[] = $post->ID;

				// Update posts.
				if ( isset( $_POST['submit'] ) ) {

					$pieces = explode( '<!--more-->', $post->post_content );

					switch ( $wip_layout_meta['layout'] ) {
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

					// Update post with shortcoded content.
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
			$pb1_layout_meta  = '';
			$pb1_content_meta = '';
			$more_tags        = '';
			$wip_layout_meta  = '';
			$wip_dynamic_meta = '';
			$pieces           = '';
			$pb_content       = '';
			$pb1_layout_meta  = get_post_meta( $page->ID, '_cahnrs_layout', true );
			$pb1_content_meta = get_post_meta( $page->ID, '_pagebuilder_editor', true );
			$more_tags        = (int) substr_count( $page->post_content, '<!--more-->' );
			$wip_layout_meta  = get_post_meta( $page->ID, '_layout', true );
			$wip_dynamic_meta = get_post_meta( $page->ID, '_dynamic', true );

			// Pagebuilder generation 1.
			if ( $pb1_layout_meta ) {

				// Add page to the $legacy_layout_pages array.
				$legacy_layout_pages[] = $page->ID;

				// Update posts.
				if ( isset( $_POST['submit'] ) ) {

					// Send stuff to pb1 function for processing.
					$pb_content .= $this->pb1_layout( $pb1_layout_meta, $pb1_content_meta );

					// Update post with shortcoded content.
					wp_update_post( array(
						'ID'           => $page->ID,
						'post_content' => $pb_content,
					) );

					// Delete WIP layout meta (it might be in there).
					delete_post_meta( $post->ID, '_layout' );
					delete_post_meta( $page->ID, '_dynamic' );

					// Delete pb1 meta.
					delete_post_meta( $post->ID, '_cahnrs_layout' );
					delete_post_meta( $post->ID, '_pagebuilder_settings' );
					delete_post_meta( $post->ID, '_pagebuilder_editor' );

				}

			}

			// WIP/WSU theme.
			if ( ! $pb1_layout_meta && ( $more_tags > 0 || ( $wip_layout_meta || $wip_dynamic_meta ) ) ) { // Presumably, any More tag in a page is for layout.

				// Add page to the $legacy_layout_pages array.
				$legacy_layout_pages[] = $page->ID;

				// Update pages
				if ( isset( $_POST['submit'] ) ) {

					$pieces = explode( '<!--more-->', $page->post_content );

					switch ( $wip_layout_meta['layout'] ) {
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
					if ( 'dynamic' == $wip_layout_meta['page_type'] && 'show' == $wip_layout_meta['slideshow'] && $wip_dynamic_meta['wipHomeArray'] ) {
						$pb_content .= '[row layout="single" padding="pad-bottom" gutter="gutter"]';
						$pb_content .= $this->wip_dynamic_column( $wip_dynamic_meta['wipHomeArray'], array(), $wip_dynamic_meta );
						$pb_content .= '[/row]';
					}

					// Row.
					$pb_content .= '[row layout="' . $layout . '" padding="pad-ends" gutter="gutter"]';

					if ( 'dynamic' == $wip_layout_meta['page_type'] && $wip_dynamic_meta ) {

						// Column one.
						if ( $wip_dynamic_meta['wipMainArray'] ) {
							$pb_content .= $this->wip_dynamic_column( $wip_dynamic_meta['wipMainArray'], $pieces, $wip_dynamic_meta );
						} else {// Sometimes a page will be set as dynamic but not have page content set in the columns...
							$pb_content .= '[column][textblock]' . "\n\n" . $pieces[0] . "\n\n" . '[/textblock][/column]';
						}

						// Column two.
						if ( 'side-right' == $layout || 'halves' == $layout || 'thirds' == $layout || 'quarters' == $layout ) {
							if ( $wip_dynamic_meta['wipSecondaryArray'] ) {
								$pb_content .= $this->wip_dynamic_column( $wip_dynamic_meta['wipSecondaryArray'], $pieces, $wip_dynamic_meta );
							} else {
								// Column two (we have at least two if we're even in here).
								$pb_content .= '[column][textblock]' . "\n\n" . $pieces[1] . "\n\n" . '[/textblock][/column]';
							}
						}

						// Column three.
						if ( 'thirds' == $layout || 'quarters' == $layout ) {
							if ( $wip_dynamic_meta['wipAdditionalArray'] ) {
								$pb_content .= $this->wip_dynamic_column( $wip_dynamic_meta['wipAdditionalArray'], $pieces, $wip_dynamic_meta );
							} else if ( $pieces[2] ) {
								$pb_content .= '[column][textblock]' . "\n\n" . $pieces[2] . "\n\n" . '[/textblock][/column]';
							}
						}

						// Column four.
						if ( 'quarters' == $layout ) {
							if ( $wip_dynamic_meta['wipFourthArray'] ) {
								$pb_content .= $this->wip_dynamic_column( $wip_dynamic_meta['wipFourthArray'], $pieces, $wip_dynamic_meta );
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
									$title = '<a href="' . get_edit_post_link( $page_id ) . '" target="_blank">' . get_the_title( $page_id ) . '</a>';
									echo '<li>' . ( ( isset( $_POST['submit'] ) ) ? '<strong>' . $title . '</strong> updated' : $title ) . '</li>';
								}
								// Could be helpful to do something like this, but not particularly necessary.
								/*if ( ! isset( $_POST['submit'] ) ) {
									add_thickbox();
									?><p>The following pages have been identified as having legacy layouts. Click the title(s) to see what the updated version would look like.</p><?php
								}
								foreach ( $legacy_layout_pages as $legacy_layout_page ) {
									if ( isset( $_POST['submit'] ) ) :
									?>
										<strong><?php echo get_the_title( $legacy_layout_page ); ?></strong> updated<br />
									<?php else: ?>
										<div id="page-<?php echo $legacy_layout_page; ?>-updated" class="updated-page-thickbox">
											// Would have to get $pb_content in here somehow.
											// Would be really cool to add a button here to allow for individual page/post updating...
										</div>
										<a href="#TB_inline?width=900&height=700&inlineId=page-<?php echo $legacy_layout_page; ?>-updated" class="thickbox"><?php echo get_the_title( $legacy_layout_page ); ?></a><br />
									<?php
									endif;
								}*/
							?>
							</ul>
							<?php // Random notes...
              	// Could allow for updating content types separately (change respective `isset( $_POST['submit']` conditions). See below.
								// Could also add a checkbox for each page/post to allow updating them individually...
							?>
							<?php /*if ( ! isset( $_POST['update_legacy_page_layouts'] ) ) : ?>
							<input id="update_legacy_page_layouts" type="checkbox" name="update_legacy_page_layouts" /> <label for="update_legacy_page_layouts">Update pages</label>
							<?php endif;*/ ?>
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
									$title = '<a href="' . get_edit_post_link( $post_id ) . '" target="_blank">' . get_the_title( $post_id ) . '</a>';
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

	/** Parse Page Builder generation 1 data into shortcode format.
	 *
	 * @param array $layout_meta  The layout meta.
	 * $param array $content_meta `Native` content.
	 *
	 * @return string Layout data parsed into shortcodes.
	 */
	public function pb1_layout( $layout_meta, $content_meta ) {

		$content = '';

		foreach ( $layout_meta as $row ) {

			// Modify this to check if the header/footer is empty.
			if ( 'row-100' === $row['id'] || 'row-200' === $row['id'] ) {
				continue;
			}

			switch ( $row['layout'] ) {
				case 'pagbuilder-layout-full':
					$layout = 'single';
					break;
				case 'pagbuilder-layout-aside':
					$layout = 'side-right';
					break;
				case 'pagbuilder-layout-half':
					$layout = 'halves';
					break;
				case 'pagbuilder-layout-thirds':
				case 'pagbuilder-layout-third-left':
				case 'pagbuilder-layout-third-right':
					$layout = 'thirds';
					break;
				case 'pagbuilder-layout-fourths':
					$layout = 'quarters';
					break;
				default:
					$layout = 'single';
			}

			$content .= '<p>[row layout="' . $layout . '" padding="pad-ends" gutter="gutter"]';

			foreach ( array_reverse( $row['columns'] ) as $column ) { // array_reverse based on export from CAHNRS...

				$content .= '[column]';

					foreach ( $column['items'] as $item ) {

						// Maybe a switch would be better here.

						// `Native` content.
						if ( 'page_content' === $item['id'] || 'content_block' === $item['id'] ) {
							$content .= '[textblock]';
							if ( 'page_content' === $item['id'] ) {
								$content .= $content_meta['primary_content'];
							} else {
								$content .= $content_meta['content_block-' . $item['instance']];
							}
							$content .= '[/textblock]</br />';
						}

						// Action items.
						if ( 'cahnrs_action_item' === $item['id'] ) {
							if ( $item['settings']['name_1'] && $item['settings']['url_1'] ) {
								$content .= '[action label="' . $item['settings']['name_1'] . '" link="' . $item['settings']['url_1'] . '"][/action]';
							}
							if ( $item['settings']['name_2'] && $item['settings']['url_2'] ) {
								$content .= '[action label="' . $item['settings']['name_2'] . '" link="' . $item['settings']['url_2'] . '"][/action]';
							}
							if ( $item['settings']['name_3'] && $item['settings']['url_3'] ) {
								$content .= '[action label="' . $item['settings']['name_3'] . '" link="' . $item['settings']['url_3'] . '"][/action]';
							}
						}
// Build all these out!
						// A-Z.
						if ( 'cahnrs_az_index' === $item['id'] ) {}

						// Feed, FAQs, etc. ('CAHNRS_feed_widget' is present in an export from CAHNRS, but I'll be darned if I can find it anywhere.)
						if ( 'cahnrs_faqs' === $item['id'] || 'cahnrs_feed' === $item['id'] || 'CAHNRS_feed_widget' === $item['id'] ) {
							$content .= '[list ][/list]';
						}

						// iFrame.
						if ( 'cahnrs_iframe' === $item['id'] ) {}

						// Existing content.
						if ( 'cahnrs_insert_existing' === $item['id'] ) {}
						
						// Item.
						if ( 'cahnrs_insert_item' === $item['id'] ) {}
						
						// Video.
						if ( 'cahnrs_insert_video' === $item['id'] ) {}

						// Slideshow (ditto the comment on line 475.)
						if ( 'cahnrs_slideshow' === $item['id'] || 'CAHNRS_Slideshow_widget' === $item['id'] ) {}

						// Gallery.
						if ( 'cahnrs_custom_gallery_widget' === $item['id'] ) {
							$content .= '[postgallery ][/postgallery]';
						}

						// Facebook.
						if ( 'cahnrs_facebook' === $item['id'] ) {}

					}

				$content .= '[/column]';

			}

			$content .= '[/row]</p>';

		}
		
		return $content;

	}

	/**
	 * Parse WSU Legacy theme column data into shortcode format.
	 *
	 * @param array $column_array Content types included in this column.
	 * @param array $pieces       Content (text) from the page itself.
	 * @param array $dynamic_meta All dynamic meta data.
	 *
	 * @return string Column data parsed into shortcode format.
	 */
	public function wip_dynamic_column( $column_array, $pieces, $dynamic_meta ) {

		$content_types = explode( ',', $column_array );

		$content = "[column]\n\n";

		foreach ( $content_types as $content_type ) {

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