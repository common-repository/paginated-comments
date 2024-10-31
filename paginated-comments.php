<?php
/*
Plugin Name: Paginated Comments
Plugin URI: http://www.badspiderbites.com/paginated-comments/
Description: Paginated Comments is a WordPress Plugin developed with <abbr title="Search Engine Optimization">SEO</abbr> in mind that gives you the ability to break your comments into a number of pages
Author: James Maurer
Version: 1.0.6
Author URI: http://www.badspiderbites.com/
*/

/*  Copyright 2008-2009	James Maurer	(email : jamesmaurerllc@gmail.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * Anti Full-Path Disclosure
 */
if ( !defined('ABSPATH') )
	die();

/*
 * Init Objects, Vars, Grab Settings and include the Class Pager
 */
$pagers->main = '';
$pagers->page = '';
$pagers->opts = array();
$comment_number = 0;
$comment_delta = 0;
$PdCs_Settings = get_option('PdCs_Settings');
require dirname(__FILE__) . '/classes/class.Pager.php';

/**
 * Paginated_Comments_install() - Add initial/default settings.
 *
 * @since beta1
 */
function Paginated_Comments_install() {
	$default_settings = array(
	'all_posts' => true,
	'all_pages' => false,
	'comments_page' => 'full',
	'comments_page_title' => '&raquo; Comment Page %pnumber%',
	'comments_page_desc' => true,
	'comments_page_keys' => true,
	'comments_page_default_dk' => '%title% Comment Page %pnumber%',
	'comments_page_slug' => 'comment-page',
	'comments_pagination' => 'number',
	'comments_per_page' => 10,
	'comments_per_size' => 102400,
	'comments_ordering' => 'desc',
	'page_range' => 11,
	'fancy_url' => false,
	'fill_last_comment_page' => false,
	'show_all_link' => true,
	'show_all_link_slug' => 'all-comments',
	'show_all_link_ordering' => 'asc',
	'default_page' => 'auto'
	);
	add_option('PdCs_Settings', $default_settings);
}

/**
 * Paginated_Comments_uninstall() - Delete settings.
 *
 * @since beta1
 */
function Paginated_Comments_uninstall() {
	delete_option('PdCs_Settings');
}

/**
 * Paginated_Comments_menu_add() - Adds the 'Comments Pagination' SubMenu inside Options/Settings Menu.
 *
 * @since beta1
 */
function Paginated_Comments_menu_add() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('options-general.php', 'Paginated Comments', 'Paginated Comments', 'manage_options', 'paginated-comments', 'Paginated_Comments_menu_page');
}

/**
 * Paginated_Comments_menu_page() - Prints 'Paginated Comments' SubMenu content.
 *
 * @since beta1
 */
function Paginated_Comments_menu_page() {
	global $PdCs_Settings;

	if ( isset($_POST['Submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer('paginated-comments-update-settings');

		/*
		 * All User Input is Evil Until Proven Otherwise :-P
		 */
		$PdCs_Settings['all_posts'] = ( 'true' === $_POST['all_posts'] ) ? true : false;
		$PdCs_Settings['all_pages'] = ( 'true' === $_POST['all_pages'] ) ? true : false;
		$PdCs_Settings['comments_page'] = ( preg_match('/\Afull|excerpt|nothing\Z/', $_POST['comments_page']) ) ? $_POST['comments_page'] : 'full';
		$PdCs_Settings['comments_page_title'] = wp_specialchars( strip_tags($_POST['comments_page_title']) );
		$PdCs_Settings['comments_page_desc'] = ( 'true' === $_POST['comments_page_desc'] ) ? true : false;
		$PdCs_Settings['comments_page_keys'] = ( 'true' === $_POST['comments_page_keywords'] ) ? true : false;
		$PdCs_Settings['comments_page_default_dk'] = wp_specialchars( strip_tags($_POST['comments_page_default_dk']) );
		$PdCs_Settings['comments_page_slug'] = apply_filters('sanitize_title', strip_tags($_POST['comments_page_slug']));
		$PdCs_Settings['comments_pagination'] = ( 'number' == $_POST['comments_pagination'] ) ? 'number' : 'size';
		$PdCs_Settings['comments_per_page'] = ( 0 < round(intval($_POST['comments_per_page'])) ) ?  round(intval($_POST['comments_per_page'])) : 10;
		$PdCs_Settings['comments_per_size'] = ( 0 < round(intval($_POST['comments_per_size'])) ) ?  round(intval($_POST['comments_per_size'])) : 102400;
		$PdCs_Settings['comments_ordering'] = ( 'asc' == $_POST['comments_ordering'] ) ? 'asc' : 'desc';
		$PdCs_Settings['page_range'] = ( 0 < round(intval($_POST['page_range'])) ) ?  round(intval($_POST['page_range'])) : 11;
		$PdCs_Settings['fancy_url'] = ( 'true' === $_POST['fancy_url'] ) ? true : false;
		$PdCs_Settings['fill_last_comment_page'] = ( 'true' === $_POST['fill_last_comment_page'] ) ? true : false;
		$PdCs_Settings['show_all_link'] = ( 'true' === $_POST['show_all_link'] ) ? true : false;
		$PdCs_Settings['show_all_link_slug'] = apply_filters('sanitize_title', strip_tags($_POST['show_all_link_slug']));
		$PdCs_Settings['show_all_link_ordering'] = ( 'asc' == $_POST['show_all_link_ordering'] ) ? 'asc' : 'desc';
		$PdCs_Settings['default_page'] = ( preg_match('/\Aauto|first|last\Z/', $_POST['default_page']) ) ? $_POST['default_page'] : 'auto';
		update_option('PdCs_Settings', $PdCs_Settings);
		echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
	}
	?>
	<div class="wrap">
		<form action="" method="post" id="paginated-comments">

			<h2><?php _e('Pagination', 'paginated-comments'); ?></h2>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Paginate', 'paginated-comments'); ?></th>
					<td>
						<label for="all_posts"><input name="all_posts" type="checkbox" <?php echo ( true === $PdCs_Settings['all_posts'] ) ? 'checked="checked"' : '' ; ?> id="all_posts" value="true"  /> <?php _e('Posts', 'paginated-comments'); ?></label><br />
						<label for="all_pages"><input name="all_pages" type="checkbox" <?php echo ( true === $PdCs_Settings['all_pages'] ) ? 'checked="checked"' : '' ; ?> id="all_pages" value="true"  /> <?php _e('Pages', 'paginated-comments'); ?></label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Method', 'paginated-comments'); ?></th>
					<td>
						<select name="comments_pagination">
							<option <?php echo ( 'number' == $PdCs_Settings['comments_pagination'] ) ? 'selected="selected"' : '' ; ?> value="number"><?php _e('Number Of Comments', 'paginated-comments'); ?></option>
							<option <?php echo ( 'size' == $PdCs_Settings['comments_pagination'] ) ? 'selected="selected"' : '' ; ?> value="size"><?php _e('Size Of Comments', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Ordering', 'paginated-comments'); ?></th>
					<td>
						<select name="comments_ordering">
							<option <?php echo ( 'asc' == $PdCs_Settings['comments_ordering'] ) ? 'selected="selected"' : '' ; ?> value="asc"><?php _e('Ascending', 'paginated-comments'); ?></option>
							<option <?php echo ( 'desc' == $PdCs_Settings['comments_ordering'] ) ? 'selected="selected"' : '' ; ?> value="desc"><?php _e('Descending', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Range', 'paginated-comments'); ?></th>
					<td>
						<input name="page_range" id="page_range" value="<?php echo intval($PdCs_Settings['page_range']); ?>" size="10" class="code" type="text" /> <?php _e('Pages', 'paginated-comments'); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Per Page', 'paginated-comments'); ?></th>
					<td>
						<input name="comments_per_page" id="comments_per_page" value="<?php echo intval($PdCs_Settings['comments_per_page']); ?>" size="10" class="code" type="text" /> <?php _e('Comments', 'paginated-comments'); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Per Size', 'paginated-comments'); ?></th>
					<td>
						<input name="comments_per_size" id="comments_per_size" value="<?php echo intval($PdCs_Settings['comments_per_size']); ?>" size="10" class="code" type="text" /> Bytes
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Fill Last Page', 'paginated-comments'); ?></th>
					<td>
						<select name="fill_last_comment_page">
							<option <?php echo ( true === $PdCs_Settings['fill_last_comment_page'] ) ? 'selected="selected"' : '' ; ?> value="true"><?php _e('Enabled', 'paginated-comments'); ?></option>
							<option <?php echo ( false === $PdCs_Settings['fill_last_comment_page'] ) ? 'selected="selected"' : '' ; ?> value="false"><?php _e('Disabled', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Show-All', 'paginated-comments'); ?></th>
					<td>
						<select name="show_all_link">
							<option <?php echo ( true === $PdCs_Settings['show_all_link'] ) ? 'selected="selected"' : '' ; ?> value="true"><?php _e('Enabled', 'paginated-comments'); ?></option>
							<option <?php echo ( false === $PdCs_Settings['show_all_link'] ) ? 'selected="selected"' : '' ; ?> value="false"><?php _e('Disabled', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Show-All Ordering', 'paginated-comments'); ?></th>
					<td>
						<select name="show_all_link_ordering">
							<option <?php echo ( 'asc' == $PdCs_Settings['show_all_link_ordering'] ) ? 'selected="selected"' : '' ; ?> value="asc"><?php _e('Ascending', 'paginated-comments'); ?></option>
							<option <?php echo ( 'desc' == $PdCs_Settings['show_all_link_ordering'] ) ? 'selected="selected"' : '' ; ?> value="desc"><?php _e('Descending', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Default Page', 'paginated-comments'); ?></th>
					<td>
						<select name="default_page">
							<option <?php echo ( 'auto' == $PdCs_Settings['default_page'] ) ? 'selected="selected"' : '' ; ?> value="auto"><?php _e('Auto', 'paginated-comments'); ?></option>
							<option <?php echo ( 'first' == $PdCs_Settings['default_page'] ) ? 'selected="selected"' : '' ; ?> value="first"><?php _e('First', 'paginated-comments'); ?></option>
							<option <?php echo ( 'last' == $PdCs_Settings['default_page'] ) ? 'selected="selected"' : '' ; ?> value="last"><?php _e('Last', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<br />
			<h2><?php _e('Personalization', 'paginated-comments'); ?></h2>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Insert On Pages', 'paginated-comments'); ?></th>
					<td>
						<label for="comments_page_desc"><input name="comments_page_desc" id="comments_page_desc" type="checkbox" <?php echo ( true === $PdCs_Settings['comments_page_desc'] ) ? 'checked="checked"' : '' ; ?> value="true"  /> <?php _e('Description', 'paginated-comments'); ?></label><br />
						<label for="comments_page_keywords"><input name="comments_page_keywords" id="comments_page_keywords" type="checkbox" <?php echo ( true === $PdCs_Settings['comments_page_keys'] ) ? 'checked="checked"' : '' ; ?> value="true" /> <?php _e('Keywords', 'paginated-comments'); ?></label>
					</td>
				</tr>
				<tr id="default_description" valign="top">
					<th scope="row"><?php _e('Default Description', 'paginated-comments'); ?></th>
					<td>
						<input name="comments_page_default_dk" id="comments_page_default_dk" value="<?php echo attribute_escape($PdCs_Settings['comments_page_default_dk']); ?>" size="30" class="code" type="text" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Page Title', 'paginated-comments'); ?></th>
					<td>
						<input name="comments_page_title" id="comments_page_title" value="<?php echo attribute_escape($PdCs_Settings['comments_page_title']); ?>" size="25" class="code" type="text" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Page Content', 'paginated-comments'); ?></th>
					<td>
						<select name="comments_page">
							<option <?php echo ( 'full' == $PdCs_Settings['comments_page'] ) ? 'selected="selected"' : '' ; ?> value="full"><?php _e('Full', 'paginated-comments'); ?></option>
							<option <?php echo ( 'excerpt' == $PdCs_Settings['comments_page'] ) ? 'selected="selected"' : '' ; ?> value="excerpt"><?php _e('Excerpt', 'paginated-comments'); ?></option>
							<option <?php echo ( 'nothing' == $PdCs_Settings['comments_page'] ) ? 'selected="selected"' : '' ; ?> value="nothing"><?php _e('Nothing', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Pretty Permalinks', 'paginated-comments'); ?></th>
					<td>
						<select id="fancy_url" name="fancy_url">
							<option <?php echo ( true === $PdCs_Settings['fancy_url'] ) ? 'selected="selected"' : '' ; ?> value="true"><?php _e('Enabled', 'paginated-comments'); ?></option>
							<option <?php echo ( false === $PdCs_Settings['fancy_url'] ) ? 'selected="selected"' : '' ; ?> value="false"><?php _e('Disabled', 'paginated-comments'); ?></option>
						</select>
					</td>
				</tr>
				<tr id="page_slug" valign="top">
					<th scope="row"><?php _e('Page Slug', 'paginated-comments'); ?></th>
					<td>
						<input name="comments_page_slug" id="comments_page_slug" value="<?php echo attribute_escape($PdCs_Settings['comments_page_slug']); ?>" size="25" class="code" type="text" />
					</td>
				</tr>
				<tr id="show_all_slug" valign="top">
					<th scope="row"><?php _e('Show-All Slug', 'paginated-comments'); ?></th>
					<td>
						<input name="show_all_link_slug" id="show_all_link_slug" value="<?php echo attribute_escape($PdCs_Settings['show_all_link_slug']); ?>" size="25" class="code" type="text" />
					</td>
				</tr>
			</table>

			<?php wp_nonce_field('paginated-comments-update-settings'); ?>

			<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes') ?>"/></p>

		</form>
	</div>

	<script type="text/javascript">
	//<![CDATA[
	(function($){
		$(document).ready( function() {

			// Description and Keywords Animations
			$("#comments_page_desc").change( function() {
				if ( ($("#comments_page_desc:checked").val() == 'true') ||  ($("#comments_page_keywords:checked").val() == 'true') ) {
					$("#default_description").show();
				} else {
					$("#default_description").hide();
				}
			});
			$("#comments_page_keywords").change( function() {
				if ( ($("#comments_page_desc:checked").val() == 'true') || ($("#comments_page_keywords:checked").val() == 'true') ) {
					$("#default_description").show();
				} else {
					$("#default_description").hide();
				}
			});

			// Fancy URL's Animation
			$("#fancy_url").change( function() {
				if ( $("#fancy_url").val() == 'true' ) {
					$("#page_slug").show();
					$("#show_all_slug").show();
				} else {
					$("#page_slug").hide();
					$("#show_all_slug").hide();
				}
			});

			// Default Status
			<?php if ( ( true === $PdCs_Settings['comments_page_desc'] ) || ( true === $PdCs_Settings['comments_page_keys'] ) ) : ?>
				$("#default_description").show();
			<?php else : ?>
				$("#default_description").hide();
			<?php endif; ?>

			<?php if ( true === $PdCs_Settings['fancy_url'] ) : ?>
				$("#page_slug").show();
				$("#show_all_slug").show();
			<?php else : ?>
				$("#page_slug").hide();
				$("#show_all_slug").hide();
			<?php endif; ?>

		});
	}(jQuery));
	//]]>
	</script>
	<?php
}

/**
 * Paginated_Comments_init() - Init Actions.
 *
 * @since beta1
 */
function Paginated_Comments_init() {
	global $wp_rewrite, $PdCs_Settings;

	if ( $PdCs_Settings['fancy_url'] && $wp_rewrite->using_permalinks() )
		Paginated_Comments_fancy_url();

	Paginated_Comments_l10n();
}

/**
 * Paginated_Comments_l10n() - l10n Support.
 *
 * @since beta3
 */
function Paginated_Comments_l10n() {
	load_plugin_textdomain('paginated-comments', 'wp-content/plugins/' . plugin_basename(dirname(__FILE__)) . '/languages/');
}

/**
 * Paginated_Comments_fancy_url() - Fancy URL's / Pretty Permalinks 
 *
 * @since beta4
 * @todo Drop request_uri/path_info based method and start using the $wp_rewrite object.
 * @param string $action Server Scope var (just for internal use)
 */
function Paginated_Comments_fancy_url($var='REQUEST_URI') {
	global $PdCs_Settings;

	if ( !in_array($var, array('REQUEST_URI', 'PATH_INFO')))
		$var = 'REQUEST_URI';
	$req = $_SERVER[$var];

	if ( preg_match('!^(.*/)' . $PdCs_Settings['comments_page_slug'] . '-([0-9]+)/?(.*)?$!', $req, $match) ) {
		$_GET['cp'] = intval($match[2]);
		$_SERVER[$var] = $match[1] . $match[3];
		remove_action('template_redirect', 'redirect_canonical');
	} elseif ( preg_match('!^(.*/)' . $PdCs_Settings['show_all_link_slug'] .'/?(.*)?$!', $req, $match) ) {
		$_GET['cp'] = 'all';
		$_SERVER[$var] = $match[1] . $match[2];
		remove_action('template_redirect', 'redirect_canonical');
	}

	if ( ($var != 'PATH_INFO') && isset($_SERVER['PATH_INFO']) )
		Paginated_Comments_fancy_url('PATH_INFO');
}

/**
 * Paginated_Comments_get_custom() - Get current post 'Custom field'.
 *
 * @since beta1
 * @param string $field Name of the Custom Field we are looking
 * @param int $post_ID ID of the post. defaults to current post ID.
 * @return mixed Custom Field data.
 */
function Paginated_Comments_get_custom($field, $post_ID = null) {
	global $post;

	if ( isset($post_ID) )
		$post->ID = (int) $post_ID;

	return @get_post_meta($post->ID, $field, true);
}

/**
 * Paginated_Comments() - Check if Paginated Comments is enabled
 *
 * @since beta1
 * @return bool True if Paginated Comments is enabled and if we are on post or a page.
 */
function Paginated_Comments() {
	global $PdCs_Settings;

	if (is_feed() || is_trackback()) return false;
	if (!is_single() && !is_page()) return false;

	$paging_enabled = strtolower(Paginated_Comments_get_custom('paginated_comments'));
	if ( is_single() ) {
		if ( $PdCs_Settings['all_posts'] )
			return ($paging_enabled != 'off');
		else
			return ($paging_enabled == 'on');
	} else {
		if ( $PdCs_Settings['all_pages'] )
			return ($paging_enabled != 'off');
		else
			return ($paging_enabled == 'on');
	}
	return false;
}

/**
 * Paginated_Comments_show_all() - Check if Show All is enabled
 *
 * @since beta1
 * @return bool True if Show All is enabled.
 */
function Paginated_Comments_show_all() {
	global $PdCs_Settings;
	return (($_GET['cp'] == 'all') && $PdCs_Settings['show_all_link']);
}

/**
 * Paginated_Comments_alter_source() - Modifies the behavior of comments_template() and the_content()
 *
 * @since beta1
 */
function Paginated_Comments_alter_source() {
	global $wpdb, $post, $comment, $PdCs_Settings;
	if ( Paginated_Comments() ) {
		if ( !Paginated_Comments_show_all() )
			add_filter('wp_title', 'Paginated_Comments_title_modify');
		$file_contents = '';
		$template = '';
		if ( is_single() )
			$template = get_single_template();
		else if ( is_page() )
			$template = get_page_template();

		if ( ($template == '') && file_exists(TEMPLATEPATH . '/index.php') )
			$template = TEMPLATEPATH . '/index.php';

		if ( $template ) {
			if ( function_exists('is_attachment') && is_attachment() )
				add_filter('the_content', 'prepend_attachment');
			$file_contents = file_get_contents($template);
			if ( strpos($file_contents, 'Paginated_Comments_template()') === false ) {
				extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
				$inc_path = get_include_path();
				set_include_path($inc_path . PATH_SEPARATOR . TEMPLATEPATH);
				$file_contents = str_replace('comments_template()', 'Paginated_Comments_template()', $file_contents);
				$file_contents = str_replace('the_content(', 'Paginated_Comments_content(', $file_contents);
				eval('?'.'>'.trim($file_contents));
				set_include_path($inc_path);
				exit;
			}
		}
	}
}

/**
 * Paginated_Comments_title_modify() - Modifys the Post Title
 *
 * @since beta3
 * @param string $title Current Title (used by the hook)
 * @return string Modified Title Post.
 */
function Paginated_Comments_title_modify($title) {
	global $PdCs_Settings;
	if ( intval($_GET['cp']) > 0 )
		return $title . ' ' .  preg_replace('/%pnumber%/', intval($_GET['cp']), $PdCs_Settings['comments_page_title']);
	else
		return $title;
}

/**
 * Paginated_Comments_heads() - Description and Keywords.
 *
 * Calculates description and keywords for post.
 *
 * @since beta4
 */
function Paginated_Comments_heads() {
	global $pagers, $PdCs_Settings, $post;
	if ( Paginated_Comments() && isset($_GET['cp']) ) {
		echo "\n" . '<!-- Start Paginated Comments -->' . "\n";
		if ( !Paginated_Comments_show_all() ) {
			$post_title = $post->post_title;
			$post_number = intval($_GET['cp']);
			$default_desc = preg_replace('/%title%/', $post_title, $PdCs_Settings['comments_page_default_dk']);
			$default_desc = preg_replace('/%pnumber%/', $post_number, $default_desc);

			/** Descriptions */
			if ( $PdCs_Settings['comments_page_desc'] ) {
				$customdesc = strtolower(Paginated_Comments_get_custom('pcp_description'));
				$description = attribute_escape( strip_tags( $default_desc . ( !empty($customdesc) ? ( ' ' . $customdesc ) : '' ) ) );
				echo '<meta name="description" content="' .  $description . '" />' . "\n";
			}

			/** Keywords */
			if ( $PdCs_Settings['comments_page_keys'] ) {
				$customkeys = strtolower(Paginated_Comments_get_custom('pcp_keywords'));
				$keywords = attribute_escape( strip_tags( $default_desc . ( !empty($customkeys) ? ( ' ' . $customkeys ) : '' ) ) );
				$keywords = trim( preg_replace( '/ /', ',', $keywords ) );
				echo '<meta name="keywords" content="' . $keywords . '" />' . "\n";
			}
		} else {
			echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
		}
		echo '<!-- End Paginated Comments -->' . "\n";
	}
}

/**
 * Paginated_Comments_content() - Modifys the_content()
 *
 * Mimic the_content, the_excerpt, and adds show nothing capabilities.
 *
 * @since beta2
 * @param string $more_link_text The link text to display for the "more" link. Defaults to '(more...)'.
 * @param bool $stripteaser Should the text before the "more" link be hidden (TRUE) or displayed (FALSE). Defaults to FALSE.
 * @param string $more_file File the "more" link points to. Defaults to the current file. (V2.0: Currently the 'more_file' parameter doesn't work).
 */
function Paginated_Comments_content($more_link_text = '(more...)', $stripteaser = 0, $more_file = '') {
	echo get_Paginated_Comments_content($more_link_text, $stripteaser, $more_file);
}

/**
 * get_Paginated_Comments_content() - Legacy/Plugin/Hacks Support.
 *
 * @since beta5
 * @param string $more_link_text The link text to display for the "more" link. Defaults to '(more...)'.
 * @param bool $stripteaser Should the text before the "more" link be hidden (TRUE) or displayed (FALSE). Defaults to FALSE.
 * @param string $more_file File the "more" link points to. Defaults to the current file. (V2.0: Currently the 'more_file' parameter doesn't work).
 * @return string the_content()
 */
function get_Paginated_Comments_content($more_link_text = '(more...)', $stripteaser = 0, $more_file = '') {
	global $PdCs_Settings, $post;
	$content = '';
	$id = (int) $post->ID;
	if ( !Paginated_Comments_show_all() && isset($_GET['cp']) && ($PdCs_Settings['comments_page'] != 'full') ) {
		if ($PdCs_Settings['comments_page'] == 'excerpt') {
			$content = apply_filters('the_excerpt', get_the_excerpt());
		} else {
			// Check for Password Protected Post
			if ( post_password_required($id) )
				$content = get_the_password_form();
			else
				$content = '<a href="' . get_permalink($id) . '" class="more-link">' . $more_link_text . '</a>';
		}
	} else {
		$content = get_the_content($more_link_text, $stripteaser, $more_file);
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
	}
	return $content;
}

/**
 * Paginated_Comments_ordering() - Comments Ordering
 *
 * @since beta1
 * @return string Asc or Desc.
 */
function Paginated_Comments_ordering() {
	global $pagers, $PdCs_Settings;
	if ( Paginated_Comments_show_all() )
		return $PdCs_Settings['show_all_link_ordering'];
	else
		return $PdCs_Settings['comments_ordering'];
}

/**
 * Paginated_Comments_calculation() - Calculate Paginated Comments.
 *
 * @since 1.0.3
 * @todo Introduce a minimalistic cache system or prepare the system for 3rd party solutions.
 * @param bool $separate_comments Optional, whether to separate the comments by comment type. Default is false.
 * @param bool $justcalculate If set to true it will just calculate pagers and wont do it the full comment query. Defaults to FALSE.
 * @param int $post_ID The ID of the post we want to look, Defaults to NULL.
 * @return object comments as objects.
 */
function Paginated_Comments_calculation($separate_comments = false, $justcalculate = false, $post_ID = null) {
	global $pagers, $PdCs_Settings, $comment_count, $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

	if ( isset($post_ID) )
		$post->ID = (int) $post_ID;

	$condition = '';
	$comment_count = 0;
	$commenter = wp_get_current_commenter();
	extract($commenter, EXTR_SKIP);

	/*
	 * Calculate condition
	 */
	if ( $user_ID )
		$condition = $wpdb->prepare("(comment_approved = '1' OR ( user_id = %d AND comment_approved = '0' ) )", $user_ID);
	else if ( empty($comment_author) )
		$condition = "comment_approved = '1'";
	else
		$condition = $wpdb->prepare("( comment_approved = '1' OR ( comment_author = %s AND comment_author_email = %s AND comment_approved = '0' ) )", $comment_author, $comment_author_email);

	/*
	 * Calculate Comments to show
	 */
	$comment_count = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND {$condition}", $post->ID) );
	if ( ($PdCs_Settings['comments_pagination'] == 'size') && !Paginated_Comments_show_all() ) {
		$comments_lengths = $wpdb->get_results( $wpdb->prepare("SELECT comment_ID, LENGTH(comment_content) as 'length' FROM $wpdb->comments WHERE comment_post_ID = %d AND {$condition} ORDER BY comment_date " . Paginated_Comments_ordering(), $post->ID) );
		$images = $wpdb->get_results( $wpdb->prepare("SELECT comment_ID, comment_content FROM $wpdb->comments WHERE comment_post_ID = %d AND {$condition} AND comment_content REGEXP '<img' ORDER BY comment_date " . Paginated_Comments_ordering(), $post->ID) );
		$pagers->opts = Paginated_Comments_by_size_calculations($comments_lengths, $images);
	}
	Paginated_Comments_init_pager($comment_count);

	/*
	 * If we just want to calculate pagers then don't do the full comment query.
	 */
	if ( !$justcalculate ) {
		$limit_clause = ( Paginated_Comments_show_all() ) ? '' : ' LIMIT '. Paginated_Comments_sql_limit();
		$comments = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND {$condition} ORDER BY comment_date " . Paginated_Comments_ordering() . $limit_clause, $post->ID) );

		if ( function_exists('separate_comments') && $separate_comments ) {
			$wp_query->comments_by_type = &separate_comments($comments);
			$comments_by_type = &$wp_query->comments_by_type;
		}

		$overridden_cpage = FALSE;
		if ( '' == get_query_var('cpage') && get_option('page_comments') ) {
			set_query_var( 'cpage', 'newest' == get_option('default_comments_page') ? get_comment_pages_count() : 1 );
			$overridden_cpage = TRUE;
		}

		return $comments;
	}
}

/**
 * Paginated_Comments_template() - Prints Comments Paged.
 *
 * Based on comments_template() in wp-includes/comment-template.php
 *
 * @since beta1
 * @param string $file Optional, default '/paginated-comments.php'. The file to load
 * @param bool $separate_comments Optional, whether to separate the comments by comment type. Default is false.
 * @return null Returns null if no comments appear
 */
function Paginated_Comments_template( $file = '/paginated-comments.php', $separate_comments = false ) {
	global $pagers, $PdCs_Settings, $comment_count, $comment_number, $comment_delta, $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

	if ( ! (is_single() || is_page() || $withcomments) )
		return;

	/*
	 * Where is paginated-comments.php ?
	 */
	$include = apply_filters('comments_template', TEMPLATEPATH . $file);
	if ( !file_exists($include) ) {
		$include = dirname(__FILE__) . '/themes/' . get_template() . '/paginated-comments.php';
		if ( !file_exists($include) )
			$include = dirname(__FILE__) . '/themes/default/paginated-comments.php';
	}

	if ( !Paginated_Comments() || !file_exists($include) ) {
		comments_template();
		return;
	}

	$req = get_option('require_name_email');
	$commenter = wp_get_current_commenter();
	extract($commenter, EXTR_SKIP);

	/*
	 * Calculate Comment Pagination Data
	 */
	$comments = Paginated_Comments_calculation($separate_comments);

	/*
	 * Calculate Comments Numeration
	 */
	if ( $PdCs_Settings['comments_pagination'] == 'number' ) {
		$comment_number = ($pagers->pager->get_current_page() - 1) * $pagers->pager->get_items_per_page();
		$comment_mod = $comment_count % $pagers->pager->get_items_per_page();
		if ( Paginated_Comments_ordering() == 'desc' ) {
			if ( $PdCs_Settings['fill_last_comment_page'] && !Paginated_Comments_show_all() && ($comment_mod != 0) )
				$comment_number += $comment_mod;
			else
				$comment_number += count($comments);
			$comment_delta = -1;
		} else {
			if ( $PdCs_Settings['fill_last_comment_page'] && !Paginated_Comments_show_all() && ($comment_mod != 0) && ($comment_number != 0) )
				$comment_number -= $pagers->pager->get_items_per_page() - $comment_mod - 1;
			else
				$comment_number += 1;
			$comment_delta = 1;
		}
	} else {
		$limits = $pagers->opts[1];
		if ( Paginated_Comments_ordering() == 'desc' ) {
			$current = $pagers->pager->num_pages() - $pagers->pager->get_current_page();
			$exp_limits = explode(',', $limits[$current]);
			if ( !Paginated_Comments_show_all() )
				$comment_number = $comment_count - $exp_limits[0];
			else
				$comment_number = $comment_count;
			$comment_delta = -1;
		} else {
			$current = $pagers->pager->get_current_page() - 1;
			$exp_limits = explode(',', $limits[$current]);
			if ( !Paginated_Comments_show_all() )
				$comment_number = $exp_limits[0] + 1;
			else
				$comment_number = 1;
			$comment_delta = 1;
		}
	}

	define('COMMENTS_TEMPLATE', true);
	require($include);
}

/**
 * Paginated_Comments_per_post_settings() - Overwrites default settings with the per post settings.
 *
 * @since beta1
 */
function Paginated_Comments_per_post_settings() {
	global $PdCs_Settings;

	/*
	 * Custom Field for Comments Pagination
	 */
	$val = strtolower( Paginated_Comments_get_custom('pcp_method') );
	if ( !empty($val) )
		$PdCs_Settings['comments_pagination'] = ( preg_match('/\Anumber|size\Z/', $val) ) ? $val : 'number';

	/*
	 * Custom Field for Comments Per Page
	 */
	$val = intval( Paginated_Comments_get_custom('pcp_perpage') );
	if ( !empty($val) )
		$PdCs_Settings['comments_per_page'] = ( $val > 0 ) ? $val : 10;

	/*
	 * Custom Field for Comments Per Size
	 */
	$val = intval( Paginated_Comments_get_custom('pcp_persize') );
	if ( !empty($val) )
		$PdCs_Settings['comments_per_size'] = ( $val > 0 ) ? $val : 102400;

	/*
	 * Custom Field for Comment Ordering
	 */
	$val = strtolower( Paginated_Comments_get_custom('pcp_ordering') );
	if ( !empty($val) )
		$PdCs_Settings['comments_ordering'] = ( preg_match('/\Aasc|desc\Z/', $val) ) ? $val : 'desc';
}

/**
 * Paginated_Comments_init_pager() - Initializes Pagers Objects.
 *
 * @since beta1
 * @param string $total_comments Total Number of comments for calculations.
 */
function Paginated_Comments_init_pager($total_comments) {
	global $pagers, $PdCs_Settings;

	/*
	 * Override Settings with the per post ones.
	 */
	Paginated_Comments_per_post_settings();

	if ( Paginated_Comments_show_all() ) {
		$total_comments = ( $total_comments > 0 ? $total_comments : 1 );
		$pagers->main =& new Paginated_Comments_Pager( $total_comments, $total_comments );
	} else {
		if ( $PdCs_Settings['comments_pagination'] == 'number' ) {
			$pagers->main =& new Paginated_Comments_Pager( $PdCs_Settings['comments_per_page'], $total_comments );
		} else {
			$PdCs_Settings['comments_per_page'] = ceil ( $total_comments / ( ( $pagers->opts[0] <= 0 ) ? 1 : $pagers->opts[0] ) );
			$pagers->main =& new Paginated_Comments_Pager( ( ( $PdCs_Settings['comments_per_page'] <= 0 ) ? 1 : $PdCs_Settings['comments_per_page'] ), $total_comments );
		}
	}

	$pagers->pager =& $pagers->main;
	if ( (Paginated_Comments_show_all() && $PdCs_Settings['show_all_link_ordering'] == 'desc') || $PdCs_Settings['comments_ordering'] == 'desc' ) {
		$pagers->pager =& new Paginated_Comments_InvertedPager($pagers->pager);
	}

	$page = intval($_GET['cp']);
	if ( $page > 0 ) {
		$pagers->pager->set_current_page($page);
	} elseif ( $PdCs_Settings['default_page'] != 'auto' ) {
		if ( $PdCs_Settings['default_page'] == 'first' )
			$pagers->main->set_current_page(1);
		else
			$pagers->main->set_current_page($pagers->pager->num_pages());
	}
}

/**
 * Paginated_Comments_getImageLength() - Get Image Length
 *
 * Gets a image length using HTTP HEAD Revisions
 * Uses cURL or raw sockets.
 *
 * @since beta4
 * @param string $image Total URI/URL of the image
 * @return int Image Length in bytes.
 */
function Paginated_Comments_getImageLength($image) {
	$user_agent = 'Paginated Comments (WordPress Plugin)';

	if ( !preg_match('/\bhttp:\/\/([-A-Z0-9.]+)(\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?(\?[-A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $image) )
		return 0;

	if ( extension_loaded('curl') ) {
		$ch = @curl_init($image);
		@curl_setopt($ch, CURLOPT_HEADER, true);
		@curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
		@curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		@curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		@curl_setopt($ch, CURLOPT_REFERER, $image);
		$result = @curl_exec($ch);
		@curl_close($ch);

		if ( preg_match('%HTTP/[0-9.x]+ 200 OK%', $result) ) {
			preg_match_all('/Content-Length: ([0-9]+)/', $result, $content, PREG_PATTERN_ORDER);
			if ( !empty($content[1][0]) )
				return $content[1][0];
			else
				return 0;
		} else {
			return 0;
		}
	} else {
		/*
		 * TODO: Enforce usage parse_url() over regular expressions?
		 */
		preg_match_all('/\bhttp:\/\/([-A-Z0-9.]+)(\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?(\?[-A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $image, $parts, PREG_PATTERN_ORDER);
		$host = $parts[1][0];
		$query = $parts[2][0] . $parts[3][0];
		if ( !empty($host) && !empty($query) ) {
			$socket = @fsockopen($host, 80, $errno, $errstr, 1);
			if ( $socket ) {
				$header = "HEAD {$query} HTTP/1.1\r\n";
				$header .= "Host: {$host}\r\n";
				$header .= "User-Agent: {$user_agent}\r\n";
				$header .= "Referer: http://{$host}{$query}\r\n";
				$header .= "Connection: Close\r\n\r\n";
				@fwrite($socket, $header);
				while ( !feof($socket) )
					$result .= fgets($socket);
				@fclose($socket);

				if ( preg_match('%HTTP/[0-9.x]+ 200 OK%', $result) ) {
					preg_match_all('/Content-Length: ([0-9]+)/', $result, $content, PREG_PATTERN_ORDER);
					if ( !empty($content[1][0]) )
						return $content[1][0];
					else
						return 0;
				} else {
					return 0;
				}
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
}

/**
 * Paginated_Comments_by_size_calculations() - Calculates Limits when paging by size.
 *
 * Just a simply iterative calculation algorithm.
 *
 * @since beta3
 * @todo Improve algorithm, specially the image length algorithm.
 * @param Object $comments_lengths Object with comments length and IDs
 * @param Object $images Object with images and comments IDs
 * @return array With Number of pages and limits (for use in mysql).
 */
function Paginated_Comments_by_size_calculations($comments_lengths, $images) {
	global $PdCs_Settings;
	$i = 0;
	$cp = 0;
	$temps = 0;
	$limits = array();
	$offset = 0;
	$size = 1;

	foreach ( (array) $comments_lengths as $length ) {
		$templ = 0;
		foreach ( (array) $images as $image ) {
			if ( $length->comment_ID == $image->comment_ID ) {
				preg_match_all('/<img[^>]*src\s*=\s*"(.*?)"/i', $image->comment_content, $matchs, PREG_PATTERN_ORDER);
				foreach ( (array) $matchs[1] as $iurls )
					$templ += Paginated_Comments_getImageLength($iurls);
			}
		}
		$templ += $length->length;
		$temps += $templ;
		if ( $temps >= $PdCs_Settings['comments_per_size'] ) {
			$cp++;
			$temps = 0;
			$limits[] = "$offset,$size";
			$offset = $i + 1;
			$size = 0;
		}
		$i++;
		$size++;
	}
	if ( $temps > 0 ) {
		$cp++;
		$size--;
		$limits[] = "$offset,$size";
	}
	
	return array($cp, $limits);
}

/**
 * Paginated_Comments_sql_limit() - Generates MySQL Limits
 *
 * @since beta1
 * @return string MySQL suitable Limits.
 */
function Paginated_Comments_sql_limit() {
	global $pagers, $PdCs_Settings;
	
	if ( $PdCs_Settings['comments_pagination'] == 'number' ) {
		$remainder = $pagers->pager->get_total_items() % $PdCs_Settings['comments_per_page'];
		$offset = ($pagers->main->get_current_page() - 1) * $PdCs_Settings['comments_per_page'];
		
		if ($remainder == 0)
			return $offset . ',' . $PdCs_Settings['comments_per_page'];
	
		if ( $PdCs_Settings['comments_ordering'] == 'desc') {
			if ( $PdCs_Settings['fill_last_comment_page'] )
				return $offset . ',' . $PdCs_Settings['comments_per_page'];
			elseif ( $pagers->pager->get_current_page() == $pagers->pager->num_pages() )
				return '0,' . $remainder;
			else
				return $offset + $remainder - $PdCs_Settings['comments_per_page'] . ',' . $PdCs_Settings['comments_per_page'];
		} else {
			if ( $PdCs_Settings['fill_last_comment_page'] && $pagers->pager->is_first_page() )
				return '0,' . $remainder;
			elseif ( $PdCs_Settings['fill_last_comment_page'] )
				return $offset - ($PdCs_Settings['comments_per_page'] - $remainder) . ',' . $PdCs_Settings['comments_per_page'];
			else
				return $offset . ',' . $PdCs_Settings['comments_per_page'];
		}
	} else {
		$current = $pagers->main->get_current_page() - 1;
		$limits = $pagers->opts[1];
		return $limits[$current];
	}
}

/**
 * Paginated_Comments_redirect_location()- Correct WordPress Redirection
 *
 * @since 1.0.3
 * @param string Original redirect location.
 * @param object Last inserted comment as object.
 * @return string Location of the latest comment.
 */
function Paginated_Comments_redirect_location($location, $comment) {
	global $comment, $pagers, $wp_rewrite, $PdCs_Settings, $multipage, $page;

	Paginated_Comments_calculation(false, true, $comment->comment_post_ID);

	$multipage_fancy = '';
	$multipage_classic = '';
	if ($multipage && $page) {
		$multipage_fancy = '/' . $page;
		$multipage_classic = 'page=' . $page . '&';
	}

	$permalink = get_permalink($comment->comment_post_ID);
	$default_page = $PdCs_Settings['default_page'];
	$ordering = $PdCs_Settings['comments_ordering'];
	$slug = $PdCs_Settings['comments_page_slug'];

	if ( isset($pagers->pager) ) {
		$total_pages = $pagers->pager->num_pages();
	} else {
		$default_page = 'last';
		$ordering = 'asc';
	}

	if ( $PdCs_Settings['fancy_url'] && $wp_rewrite->using_permalinks() ) {
		$permalinks = rtrim($permalink, '/') . $multipage_fancy;
		if ( ( ($ordering == 'asc') && ( ($default_page == 'first') || ($default_page == 'auto') ) ) || ( ($ordering == 'desc') && ($default_page == 'last') ) )
			$redirect = $permalinks . '/' . $slug . '-' . $total_pages . '/#comment-' . $comment->comment_ID;
		else
			$redirect = $permalinks . ( ( '/' == substr($permalink, -1) ) ? '/' : '' ) . '#comment-' . $comment->comment_ID;
	} else {
		$permalink = rtrim($permalink, '?') . '?' . $multipage_classic;
		if ( ( ($ordering == 'asc') && ( ($default_page == 'first') || ($default_page == 'auto') ) ) || ( ($ordering == 'desc') && ($default_page == 'last') ) )
			$redirect = $permalink . 'cp=' . $total_pages . '#comment-' . $comment->comment_ID;
		else
			$redirect = $permalink . '#comment-' . $comment->comment_ID;
	}

	return $redirect;
}

/**
 * Paginated_Comments_have_pages() - Conditional Template Tag for Comments Pages
 *
 * @since beta5
 * @return bool TRUE if have more than one page.
 */
function Paginated_Comments_have_pages() {
	global $pagers;

	if ( $pagers->pager->num_pages() > 1 )
		return true;
	else
		return false;
}

/**
 * Paginated_Comments_numeration() - Template Tag for Comments Enumeration
 *
 * @since beta5
 */
function Paginated_Comments_numeration() {
	global $comment_number, $comment_delta;
	echo $comment_number;
	$comment_number += $comment_delta;
}

/**
 * Paginated_Comments_URL() - Template Tag for Paged Comments URLs
 *
 * Generates URIs for every comment page based on the settings.
 *
 * @since beta1
 * @param string $fragment string to append with the comment ID during generation. Defaults to 'comments'
 * @param string $cpage Type of Link
 * @return string URI/URL of the comment page
 */
function Paginated_Comments_URL($fragment='comments', $cpage=null) {
	global $pagers, $PdCs_Settings, $wp_rewrite, $post, $multipage, $page;
	if ( !isset($cpage) && isset($pagers->pager) ) $cpage = $pagers->pager->get_current_page();
	$id = (int) $post->ID;
	$qparam = is_page() ? 'page_id' : 'p';
	$multipage_fancy = '';
	$multipage_classic = '';
	$permalink = '';
	$flagit = false;

	if ($multipage && $page) {
		$multipage_fancy = '/' . $page;
		$multipage_classic = '&amp;page=' . $page;
	}

	if ( ($PdCs_Settings['default_page'] == 'auto') || ($PdCs_Settings['default_page'] == 'first') ) {
		if ( ($PdCs_Settings['comments_ordering'] == 'desc') && $pagers->pager->is_last_page() )
			$flagit = true;
		elseif ( ($PdCs_Settings['comments_ordering'] == 'asc') && $pagers->pager->is_first_page() )
			$flagit = true;
		else
			$flagit = false;
	} else {
		if ( ($PdCs_Settings['comments_ordering'] == 'desc') && $pagers->pager->is_first_page() )
			$flagit = true;
		elseif ( ($PdCs_Settings['comments_ordering'] == 'asc') && $pagers->pager->is_last_page() )
			$flagit = true;
		else
			$flagit = false;
	}

	if ( $PdCs_Settings['fancy_url'] && $wp_rewrite->using_permalinks() ) {
		$permalink = rtrim(get_permalink($id), '/');
		if ( $cpage == 'all' )
			return $permalink . $multipage_fancy .'/'. $PdCs_Settings['show_all_link_slug'] . '/#' . $fragment;
		elseif ( (($PdCs_Settings['comments_pagination'] == 'size') && ($pagers->opts[0] == 1)) || ( ($flagit) && ($cpage != '=placeholder=') ) )
			return $permalink . $multipage_fancy . ( ( '/' == substr(get_permalink($id), -1) ) ? '/' : '' ) . '#' . $fragment;
		else
			return $permalink . $multipage_fancy .'/'. $PdCs_Settings['comments_page_slug'] . '-' . $cpage . '/#' . $fragment;
	} else {
		if ( $cpage == 'all' )
			return get_option('home') . '/?' . $qparam . '=' . $id . $multipage_classic . '&amp;cp=all#' . $fragment;
		elseif ( (($PdCs_Settings['comments_pagination'] == 'size') && ($pagers->opts[0] == 1)) || ( ($flagit) && ($cpage != '=placeholder=') ) )
			return get_option('home') . '/?' . $qparam . '=' . $id . $multipage_classic . '#' . $fragment;
		else
			return get_option('home') . '/?' . $qparam . '=' . $id . $multipage_classic . '&amp;cp=' . $cpage . '#' . $fragment;
	}
}

/**
 * Paginated_Comments_URL() - Template Tag for Display the Pages of the comments.
 *
 * Generates Pages for the comments.
 *
 * @since beta1
 * @param string $sep Separator between pages. Defaults to '&nbsp;'
 * @param string $sel_left Left string to append to the current page. Defaults to '<strong>['
 * @param string $sel_right Right string to append to the current page. Defaults to ']</strong>'
 * @param string $left Left Nav Link. Defaults to '&laquo;'
 * @param string $right Right Nab Link. Defaults to '&raquo;'
 * @param string $all String to be used for Show ALL Link. Defaults to 'Show All'
 * @param string $older_alt Older Alternate text. Defaults to 'Older Comments'
 * @param string $newer_alt Newer Alternate text. Defaults to 'Newer Comments'
 * @param string $all_alt Show ALL Link Alternate text. Defaults to 'Show All Comments'
 */
function Paginated_Comments_print_pages($sep='&nbsp;', $sel_left='<strong>[', $sel_right=']</strong>', $left='&laquo;', $right='&raquo;', $all=null, $older_alt=null, $newer_alt=null, $all_alt=null) {
	global $pagers, $PdCs_Settings, $post, $wp_rewrite;
	if ( !isset($all) ) $all = __('Show All', 'paginated-comments');
	if ( !isset($older_alt) ) $older_alt = __('Older Comments', 'paginated-comments');
	if ( !isset($newer_alt) ) $newer_alt = __('Newer Comments', 'paginated-comments');
	if ( !isset($all_alt) ) $all_alt = __('Show All Comments', 'paginated-comments');
	$page_links = '';
	$id = (int) $post->ID;
	$url = Paginated_Comments_url('comments', '=placeholder=');
	$url = str_replace('%', '%%', $url);
	$url = str_replace('=placeholder=', '%u', $url);
/*
* Get that #comments off of the menu item for SEO and indexing.
*/	
	if (substr($url, -9) == "#comments")
	 $url = str_replace('#comments', '', $url);
	

	$allurl = Paginated_Comments_url('comments', 'all');
	$printer =& new Paginated_Comments_PagePrinter($pagers->pager, $url, $PdCs_Settings['page_range']);
	
	$link_left = ($PdCs_Settings['comments_ordering'] == 'asc') ? $printer->get_prev_link($left, $older_alt) : $printer->get_next_link($left, $newer_alt);
	if ( !empty($link_left) )
		$page_links .= $link_left . $sep;
	
	$page_links .= $printer->get_links($sep, $sel_left, $sel_right);
	
	$link_right = ($PdCs_Settings['comments_ordering'] == 'asc') ? $printer->get_next_link($right, $newer_alt) : $printer->get_prev_link($right, $older_alt);
	if (!empty($link_right))
		$page_links .= $sep . $link_right;

	if ( $PdCs_Settings['show_all_link'] )
		$page_links .= $sep . '<a href="' . $allurl . '" title="' . $all_alt . '">' . $all . '</a>';

	/*
	 * Calculate Comment Page Principal Replacement
	 */
	$cppr = 'epc';
	if ( ($PdCs_Settings['default_page'] == 'auto') || ($PdCs_Settings['default_page'] == 'first') ) {
		if ($PdCs_Settings['comments_ordering'] == 'desc')
			$cppr = $pagers->pager->num_pages();
		else
			$cppr = 1;
	} else {
		if ($PdCs_Settings['comments_ordering'] == 'desc')
			$cppr = 1;
		else
			$cppr = $pagers->pager->num_pages();
	}

	if ( $PdCs_Settings['fancy_url'] && $wp_rewrite->using_permalinks() )
		echo preg_replace('%' . ( ( '/' == substr(get_permalink($id), -1) ) ? '' : '/' ) . $PdCs_Settings['comments_page_slug'] . '-' . $cppr .'/#comments%', '', $page_links);
	else
		echo preg_replace('/&amp;cp=' . $cppr . '#comments/', '', $page_links);
}

/** WP < 2.7.0 Back-Compat function */
if ( !function_exists('post_password_required') ) :
/**
 * Whether post requires password and correct password has been provided.
 *
 * @since 2.7.0
 *
 * @param int|object $post An optional post.  Global $post used if not provided.
 * @return bool false if a password is not required or the correct password cookie is present, true otherwise.
 */
function post_password_required( $post = null ) {
	$post = get_post($post);

	if ( empty($post->post_password) )
		return false;

	if ( !isset($_COOKIE['wp-postpass_' . COOKIEHASH]) )
		return true;

	if ( $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password )
		return true;

	return false;
}
endif;

/*
 * Plugin Hooks
 */
register_activation_hook(__FILE__, 'Paginated_Comments_install');
register_deactivation_hook(__FILE__, 'Paginated_Comments_uninstall');
add_action('init', 'Paginated_Comments_init');
add_action('admin_menu', 'Paginated_Comments_menu_add');
add_action('template_redirect', 'Paginated_Comments_alter_source', 15);
add_action('wp_head', 'Paginated_Comments_heads');
add_filter('comment_post_redirect', 'Paginated_Comments_redirect_location', 1, 2);
?>