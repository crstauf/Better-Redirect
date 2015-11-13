<?php
/*
Plugin Name: Better Redirect
Plugin URI: 
Description: 
Version: 0.0.2
Author: Caleb Stauffer
Author URI: http://develop.calebstauffer.com
*/

if (
	defined('ABSPATH') && 
	(!defined('WP_INSTALLING') || !WP_INSTALLING) && 
	(!defined('WP_IMPORTING') || !WP_IMPORTING)
) {
	if (
		is_admin() && 
		(!defined('DOING_AJAX') || !DOING_AJAX) && 
		(!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE)
	) {
		if (isset($_POST) && is_array($_POST) && count($_POST) && isset($_POST['htaccess_textarea']))
			add_action('load-tools_page_better-redirect',array('css_better_redirect','submit'));
		add_action('load-post.php',array('css_better_redirect','edit_page'));
		add_action('admin_enqueue_scripts',array('css_better_redirect','depends'));
		add_action('admin_menu',array('css_better_redirect','admin_menu'));
		add_action('admin_footer-post.php',array('css_better_redirect','footer'));
	} else if (!is_admin())
		add_action('init',array('css_better_redirect','front'));
	add_action('wp_ajax_get_better_redirects',array('css_better_redirect','get_redirects'));
	add_action('wp_ajax_nopriv_get_better_redirects',array('css_better_redirect','get_redirects'));
}

class css_better_redirect {

	protected static $rules = array();
	
	protected static $htaccess = false;
	protected static $start = false;
	protected static $end = false;

	function __construct() {
		
	}

	public static function front() {
		if (!is_user_logged_in() || !is_admin_bar_showing()) return;
		add_action('wp_enqueue_scripts',array('css_better_redirect','depends'));
		add_action('admin_bar_menu',array('css_better_redirect','admin_bar'),1000);
		add_action('wp_footer',array('css_better_redirect','footer'));
	}

	public static function depends() {
		wp_enqueue_style('better-redirect',plugin_dir_url(__FILE__) . 'styles.css');
		wp_enqueue_script('better-redirect',plugin_dir_url(__FILE__) . 'scripts.js',array('jquery'));
	}

	public static function admin_menu() {
		$hook = add_management_page('Better Redirect','Better Redirect','manage_options','better-redirect',array(__CLASS__,'page'));
		add_filter('wp_dropdown_pages',array(__CLASS__,'wp_dropdown_pages'));
	}

	public static function get_rules($save = false) {
		if (file_exists(ABSPATH . '.htaccess')) {
			$contents = file_get_contents(ABSPATH . '.htaccess');
			if (true === $save) self::$htaccess = $contents;
			$start = strpos($contents,"\nRedirect");
			if (true === $save) self::$start = $start;
			$lastline = strrpos($contents,"\nRedirect");
			$end = strpos($contents,"\n",($lastline + 2));
			if (false === $end) $end = strlen($contents);
			if (true === $save) self::$end = $end;
			$length = $end - $start;
			unset($lastline,$end);
			if (0 == $length) $length = strlen($contents) - $start;
			foreach (explode("\n",substr($contents,$start,$length)) as $line)
				if (false !== strpos($line,'Redirect'))
					self::$rules[] = $line;
		}
	}

	public static function page() {
		self::get_rules(true);
		?>

		<h2>Better Redirect</h2>

		<input type="hidden" id="wp_url" value="<?php bloginfo('url') ?>" />

		<ul style="width: 70%;">
			<li style="width: 35%;">
				<input type="text" class="code" id="redirecting_url" style="width: 100%;" /><br />
				<span style="font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #777;" title="If you're running multisite with subdomains, redirects will effect those sites as well.">Starts with: <span class="code">http(s)://*.<?php echo str_replace('https://','',str_replace('http://','',get_bloginfo('url'))) ?>/</span></span>
			</li>
			<li style="width: 55%;">
				<input id="redirect_url" type="url" value="<?php bloginfo('url') ?>/" class="code" style="width: 100%;" />
				<br />
				<?php
				$args = array(
					'id' => 'dropdown_pages',
					'show_option_none' => ' ',
					'option_none_value' => get_bloginfo('url'),
					'walker' => new CSSBR_Walker_PageDropdown,
				);
				if (isset($_GET['post_id']))
					$args['selected'] = $_GET['post_id'];
				wp_dropdown_pages($args);
				?>
			</li>
			<li><input id="add_redirect" type="button" class="button button-primary" value="Add" style="margin-top: 2px;" /></li>
		</ul>
		<?php
		if (isset($_GET['post_id']))
			echo '<script type="text/javascript">jQuery(document).ready(function() { jQuery("#dropdown_pages").trigger("change"); });</script>';
		?>
		<br style="clear: both;" />

		<form method="post" action="<?php echo admin_url(add_query_arg('page','better-redirect','tools.php')) ?>">
			<textarea id="htaccess_before" name="htaccess_before" readonly="readonly" style="display: none;"><?php echo trim(substr(self::$htaccess,0,self::$start)) ?></textarea>
			<?php
			if (count(self::$rules))
				echo '<p><input type="checkbox" id="edit_textarea"> <label for="edit_textarea"><span>Enable</span> textarea manual editing</label></p><textarea name="htaccess_textarea" id="htaccess_textarea" class="code" style="width: 90%; max-width: 90%; height: 300px;" readonly="readonly">' . trim(implode("\n",self::$rules)) . '</textarea><br /><p style="width: 90%; text-align: right;"><input type="reset" value="Cancel" class="button" /> <input type="submit" value="Save" class="button button-primary" /></p>';
			echo '<textarea name="htaccess_after" id="htaccess_after" readonly="readonly" style="display: none;">' . trim(substr(self::$htaccess,self::$end)) . '</textarea>';
		echo '</form>';

	}

		public static function wp_dropdown_pages($output) {
			return $output;
		}

		public static function submit() {
			$new = trim(stripslashes(implode("\r\n\r\n",$_POST)));
			if (file_get_contents(ABSPATH . '.htaccess') !== $new) {
				rename(ABSPATH . '.htaccess',ABSPATH . '.htaccess-backup');
				file_put_contents(ABSPATH . '.htaccess',$new);
				wp_redirect(admin_url(add_query_arg('page','better-redirect','tools.php')));
				echo '<script type="text/javascript">window.location="' . admin_url(add_query_arg('page','better-redirect','tools.php')) . '"</script>';
				exit();
			}
		}

	public static function edit_page() {
		add_action('admin_bar_menu',array('css_better_redirect','admin_bar'),1000);
	}

	public static function admin_bar($bar) {
		global $post;
		$bar->add_node(array(
			'id' => 'better-redirect',
			'title' => 'Better Redirect',
			'href' => admin_url(add_query_arg('page','better-redirect','tools.php')),
		));
		$bar->add_node(array(
			'id' => 'add-better-redirect',
			'title' => 'Add New',
			'href' => admin_url(add_query_arg('post_id',$post->ID,add_query_arg('page','better-redirect','tools.php'))),
			'parent' => 'better-redirect',
			'meta' => array('class' => 'add-new'),
		));
		wp_enqueue_script('jquery');
	}

	public static function get_redirects() {
		self::get_rules();
		if (0 == count(self::$rules)) {
			?>
			<a class="ab-item" href="<?php echo admin_url(add_query_arg('page','better-redirect','tools.php')) ?>">aNo Redirects</a>
			<div class="ab-sub-wrapper">
				<ul id="wp-admin-bar-better-redirect-redirects" class="ab-submenu">
					<li id="wp-admin-bar-add-better-redirect"><a class="ab-item" href="<?php echo admin_url(add_query_arg('page','better-redirect',add_query_arg('post_id',$_REQUEST['post_id'],'tools.php'))) ?>">Add New</a></li>
				</ul>
			</div>
			<?php
			wp_die();
		}
		
		$permalink = '"' . get_permalink($_REQUEST['post_id']) . '"';
		$redirects = array();
		foreach (self::$rules as $rule) {
			$exploded = explode(' ',$rule);
			if (trim($exploded[3]) == $permalink)
				$redirects[] = $exploded[1] . ' ' . $exploded[2];
		}
		
		if (0 == count($redirects)) {
			?>
			<a class="ab-item" href="<?php echo admin_url(add_query_arg('page','better-redirect','tools.php')) ?>">No Redirects</a>
			<div class="ab-sub-wrapper">
				<ul id="wp-admin-bar-better-redirect-redirects" class="ab-submenu">
					<li id="wp-admin-bar-add-better-redirect"><a class="ab-item" href="<?php echo admin_url(add_query_arg('page','better-redirect',add_query_arg('post_id',$_REQUEST['post_id'],'tools.php'))) ?>">Add New</a></li>
				</ul>
			</div>
			<?php
			wp_die();
		}
		?>

		<a class="ab-item" aria-haspopup="true" href="<?php echo admin_url(add_query_arg('page','better-redirect','tools.php')) ?>"><?php echo sprintf(_n('1 Redirect','%s Redirects',count($redirects)),count($redirects)) ?></a>
		<div class="ab-sub-wrapper">
			<ul id="wp-admin-bar-better-redirect-redirects" class="ab-submenu">
				<?php
				foreach ($redirects as $i => $redirect)
					echo '<li id="wp-admin-bar-better-redirects-' . $i . '"><span class="ab-item ab-empty-item">' . $redirect . '</span></li>';
				?>
				<li id="wp-admin-bar-add-better-redirect"><a class="ab-item" href="<?php echo admin_url(add_query_arg('page','better-redirect',add_query_arg('post_id',$_REQUEST['post_id'],'tools.php'))) ?>">Add New</a></li>
			</ul>
		</div>
		<?php
		wp_die();
	}

	public static function footer() {
		global $post;
		if (!is_admin())
			echo '<input type="hidden" id="post_ID" value="' . $post->ID . '" />';
		?>
		<script type="text/javascript">
			jQuery(function($) {
				$.post("<?php echo admin_url('admin-ajax.php') ?>",{action: 'get_better_redirects',post_id: $("input#post_ID").val()},function(response) {
					$("li#wp-admin-bar-better-redirect").html(response);
				});
			});
		</script>
		<?php
	}

}

class CSSBR_Walker_PageDropdown extends Walker_PageDropdown {

	public function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);
		
		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( get_permalink( $page->ID ) ) . "\"";
		if ( $page->ID == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
	
		$title = $page->post_title;
		if ( '' === $title ) {
			$title = sprintf( __( '#%d (no title)' ), $page->ID );
		}
	
		$title = apply_filters( 'list_pages', $title, $page );
		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
	
}

?>