<?php

require_once(drpp_DIR.'/magic.php');
require_once(drpp_DIR.'/keywords.php');
require_once(drpp_DIR.'/intl.php');
require_once(drpp_DIR.'/services.php');

if ( !defined('WP_CONTENT_URL') )
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if ( !defined('drpp_UNLIKELY_DEFAULT') )
	define('drpp_UNLIKELY_DEFAULT', "There's no way this is going to be the string.");

global $drpp_value_options, $drpp_binary_options;
// here's a list of all the options drpp uses (except version), as well as their default values, sans the drpp_ prefix, split up into binary options and value options. These arrays are used in updating settings (options.php) and other tasks.
$drpp_value_options = array('threshold' => 5,
				'limit' => 5,
				'template_file' => '', // new in 2.2
				'excerpt_length' => 10,
				'recent_number' => 12,
				'recent_units' => 'month',
				'before_title' => '<li>',
				'after_title' => '</li>',
				'before_post' => ' <small>',
				'after_post' => '</small>',
				'before_related' => '<p>'.__('Related posts:','drpp').'</p><ol>',
				'after_related' => '</ol>',
				'no_results' => '<p>'.__('No related posts.','drpp').'</p>',
				'order' => 'score DESC',
				'rss_limit' => 3,
				'rss_template_file' => '', // new in 2.2
				'rss_excerpt_length' => 10,
				'rss_before_title' => '<li>',
				'rss_after_title' => '</li>',
				'rss_before_post' => ' <small>',
				'rss_after_post' => '</small>',
				'rss_before_related' => '<p>'.__('Related posts:','drpp').'<ol>',
				'rss_after_related' => '</ol></p>',
				'rss_no_results' => '<p>'.__('No related posts.','drpp').'</p>',
				'rss_order' => 'score DESC',
				'title' => '2',
				'body' => '2',
				'categories' => '2',
				'tags' => '2',
				'distags' => '',
				'discats' => '');
$drpp_binary_options = array('past_only' => true,
				'show_excerpt' => false,
				'recent_only' => false, // new in 3.0
				'use_template' => false, // new in 2.2
				'rss_show_excerpt' => false,
				'rss_use_template' => false, // new in 2.2
				'show_pass_post' => false,
				'cross_relate' => false,
				'auto_display' => true,
				'rss_display' => false, // changed default in 3.1.7
				'rss_excerpt_display' => true,
				'promote_drpp' => false,
				'rss_promote_drpp' => false);

function drpp_enabled() {
	global $wpdb;
	$indexdata = $wpdb->get_results("show index from $wpdb->posts");
	foreach ($indexdata as $index) {
		if ($index->Key_name == 'drpp_title') {
			// now check for the cache tables
			$tabledata = $wpdb->get_col("show tables");
			if (array_search("{$wpdb->prefix}drpp_related_cache",$tabledata) !== false and array_search("{$wpdb->prefix}drpp_keyword_cache",$tabledata) !== false)
				return 1;
			else
				return 0;
		};
	}
	return 0;
}

function drpp_reinforce() {
	if (!get_option('drpp_version'))
		drpp_activate();
	drpp_upgrade_check(true);
}

function drpp_activate() {
	global $drpp_version, $wpdb, $drpp_binary_options, $drpp_value_options;
	foreach (array_keys($drpp_value_options) as $option) {
		if (get_option("drpp_$option",drpp_UNLIKELY_DEFAULT) == drpp_UNLIKELY_DEFAULT)
			add_option("drpp_$option",$drpp_value_options[$option] . ' ');
	}
	foreach (array_keys($drpp_binary_options) as $option) {
		if (get_option("drpp_$option",drpp_UNLIKELY_DEFAULT) == drpp_UNLIKELY_DEFAULT)
			add_option("drpp_$option",$drpp_binary_options[$option]);
	}
	if (!drpp_enabled()) {
		if (!$wpdb->query("ALTER TABLE $wpdb->posts ADD FULLTEXT `drpp_title` ( `post_title`)")) {
			echo "<!--".__('MySQL error on adding drpp_title','drpp').": ";
			$wpdb->print_error();
			echo "-->";
		}
		if (!$wpdb->query("ALTER TABLE $wpdb->posts ADD FULLTEXT `drpp_content` ( `post_content`)")) {
			echo "<!--".__('MySQL error on adding drpp_content','drpp').": ";
			$wpdb->print_error();
			echo "-->";
		}
		if (!$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}drpp_keyword_cache` (
			`ID` bigint(20) unsigned NOT NULL default '0',
			`body` text collate utf8_unicode_ci NOT NULL,
			`title` text collate utf8_unicode_ci NOT NULL,
			`date` timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (`ID`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='drpp''s keyword cache table';")) {
			echo "<!--".__('MySQL error on creating drpp_keyword_cache table','drpp').": ";
			$wpdb->print_error();
			echo "-->";
		}
		if (!$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}drpp_related_cache` (
			`reference_ID` bigint(20) unsigned NOT NULL default '0',
			`ID` bigint(20) unsigned NOT NULL default '0',
			`score` float unsigned NOT NULL default '0',
			`date` timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY ( `score` , `date` , `reference_ID` , `ID` )
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;")) {
			echo "<!--".__('MySQL error on creating drpp_related_cache table','drpp').": ";
			$wpdb->print_error();
			echo "-->";
		}
		if (!drpp_enabled()) {
			return 0;
		}
	}
	add_option('drpp_version',drpp_VERSION);
	update_option('drpp_version',drpp_VERSION);
	return 1;
}

function drpp_myisam_check() {
	global $wpdb;
	$tables = $wpdb->get_results("show table status like '$wpdb->posts'");
	foreach ($tables as $table) {
		if ($table->Engine == 'MyISAM') return true;
		else return $table->Engine;
	}
	return 'UNKNOWN';
}

function drpp_upgrade_check($inuse = false) {
	global $wpdb, $drpp_value_options, $drpp_binary_options;

	foreach (array_keys($drpp_value_options) as $option) {
		if (get_option("drpp_$option",drpp_UNLIKELY_DEFAULT) == drpp_UNLIKELY_DEFAULT)
			add_option("drpp_$option",$drpp_value_options[$option].' ');
	}
	foreach (array_keys($drpp_binary_options) as $option) {
		if (get_option("drpp_$option",drpp_UNLIKELY_DEFAULT) == drpp_UNLIKELY_DEFAULT)
			add_option("drpp_$option",$drpp_binary_options[$option]);
	}

	// upgrade check

	if (get_option('threshold') and get_option('limit') and get_option('len')) {
		drpp_activate();
		drpp_upgrade_one_five();
		update_option('drpp_version','1.5');
	}
	
	if (version_compare('3.1.3',get_option('drpp_version')) > 0) {
		$wpdb->query("ALTER TABLE {$wpdb->prefix}drpp_related_cache DROP PRIMARY KEY ,
                  ADD PRIMARY KEY ( score , date , reference_ID , ID )");
	}

  update_option('drpp_version',drpp_VERSION);

	// just in case, try to add the index one more time.	
	if (!drpp_enabled()) {
		$wpdb->query("ALTER TABLE $wpdb->posts ADD FULLTEXT `drpp_title` ( `post_title`)");
		$wpdb->query("ALTER TABLE $wpdb->posts ADD FULLTEXT `drpp_content` ( `post_content`)");
	}
	
}

function drpp_admin_menu() {
	$hook = add_options_page(__('Dynamic Related Posts Plugin','drpp'),__('Dynamic Related Posts Plugin','drpp'), 'manage_options', 'dynamic-related-posts/options.php', 'drpp_options_page');
	add_action("load-$hook",'drpp_load_thickbox');
  // new in 3.0.12: add settings link to the plugins page
  add_filter('plugin_action_links', 'drpp_settings_link', 10, 2);
}

function drpp_settings_link($links, $file) {
  $this_plugin = dirname(plugin_basename(__FILE__)) . '/search-related-posts.php';
  if($file == $this_plugin) {
    $links[] = '<a href="options-general.php?page='.dirname(plugin_basename(__FILE__)).'/options.php">' . __('Settings', 'drpp') . '</a>';
  }
  return $links;
}

function drpp_load_thickbox() {
	wp_enqueue_script( 'thickbox' );
	if (function_exists('wp_enqueue_style')) {
		wp_enqueue_style( 'thickbox' );
	}
}

function drpp_options_page() {
	require(drpp_DIR.'/options.php');
}

function widget_drpp_init() {
  register_widget('drpp_Widget');
}

// vaguely based on code by MK Safi
// http://msafi.com/fix-yet-another-related-posts-plugin-drpp-widget-and-add-it-to-the-sidebar/
class drpp_Widget extends WP_Widget {
  function drpp_Widget() {
    parent::WP_Widget(false, $name = __('Dynamic Related Posts Plugin','drpp'));
  }
 
  function widget($args, $instance) {
  	global $post;
    if (!is_single())
      return;
      
    extract($args);
    
		$type = ($post->post_type == 'page' ? array('page') : array('post'));
		if (drpp_get_option('cross_relate'))
			$type = array('post','page');
    
    $title = apply_filters('widget_title', $instance['title']); 
    echo $before_widget;
		if ( !$instance['use_template'] ) {
			echo $before_title;
			if ($title)
				echo $title;
			else
				_e('Dynamic Related Posts Plugin','drpp');
			echo $after_title;
    }
//    var_dump($instance);
		echo drpp_related($type,$instance,false,false,'widget');
    echo $after_widget;
  }
 
  function update($new_instance, $old_instance) {
		// this starts with default values.
		$instance = array( 'promote_drpp' => 0, 'use_template' => 0 );
		foreach ( $instance as $field => $val ) {
			if ( isset($new_instance[$field]) )
				$instance[$field] = 1;
		}
		if ($instance['use_template']) {
			$instance['template_file'] = $new_instance['template_file'];
			$instance['title'] = $old_instance['title'];
		} else {
			$instance['template_file'] = $old_instance['template_file'];
			$instance['title'] = $new_instance['title'];
		}
    return $instance;
  }
  
  function form($instance) {				
    $title = esc_attr($instance['title']);
    $template_file = $instance['template_file'];
    ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

			<?php // if there are drpp templates installed...
				if (count(glob(STYLESHEETPATH . '/drpp-template-*.php'))): ?>

				<p><input class="checkbox" id="<?php echo $this->get_field_id('use_template'); ?>" name="<?php echo $this->get_field_name('use_template'); ?>" type="checkbox" <?php checked($instance['use_template'], true) ?> /> <label for="<?php echo $this->get_field_id('use_template'); ?>"><?php _e("Display using a custom template file",'drpp');?></label></p>
				<p id="<?php echo $this->get_field_id('template_file_p'); ?>"><label for="<?php echo $this->get_field_id('template_file'); ?>"><?php _e("Template file:",'drpp');?></label> <select name="<?php echo $this->get_field_name('template_file'); ?>" id="<?php echo $this->get_field_id('template_file'); ?>">
					<?php foreach (glob(STYLESHEETPATH . '/drpp-template-*.php') as $template): ?>
					<option value='<?php echo htmlspecialchars(basename($template))?>'<?php echo (basename($template)==$template_file)?" selected='selected'":'';?>><?php echo htmlspecialchars(basename($template))?></option>
					<?php endforeach; ?>
				</select><p>

			<?php endif; ?>

        <p><input class="checkbox" id="<?php echo $this->get_field_id('promote_drpp'); ?>" name="<?php echo $this->get_field_name('promote_drpp'); ?>" type="checkbox" <?php checked($instance['images'], true) ?> /> <label for="<?php echo $this->get_field_id('promote_drpp'); ?>"><?php _e("Help promote Dynamic Related Posts Plugin?",'drpp'); ?></label></p>

				<script type="text/javascript">
				jQuery(function() {
					function ensureTemplateChoice() {
						if (jQuery('#<?php echo $this->get_field_id('use_template'); ?>').attr('checked')) {
							jQuery('#<?php echo $this->get_field_id('title'); ?>').attr('disabled',true);
							jQuery('#<?php echo $this->get_field_id('template_file_p'); ?>').show();
						} else {
							jQuery('#<?php echo $this->get_field_id('title'); ?>').attr('disabled',false);
							jQuery('#<?php echo $this->get_field_id('template_file_p'); ?>').hide();
						}
					}
					jQuery('#<?php echo $this->get_field_id('use_template'); ?>').change(ensureTemplateChoice);
					ensureTemplateChoice();
				});
				</script>

    <?php
  }
}


function drpp_default($content) {
	global $wpdb, $post;
	
	if (is_feed())
		return drpp_rss($content,$type);
	
	$type = ($post->post_type == 'page' ? array('page') : array('post'));
	if (drpp_get_option('cross_relate'))
		$type = array('post','page');
	
	if (drpp_get_option('auto_display') and is_single())
		return $content.drpp_related($type,array(),false,false,'website');
	else
		return $content;
}

function drpp_rss($content) {
	global $wpdb, $post;
	
	$type = ($post->post_type == 'page' ? array('page') : array('post'));
	if (drpp_get_option('cross_relate'))
		$type = array('post','page');
	
	if (drpp_get_option('rss_display'))
		return $content.drpp_related($type,array(),false,false,'rss');
	else
		return $content;
}

function drpp_rss_excerpt($content) {
	global $wpdb, $post;

	$type = ($post->post_type == 'page' ? array('page') : array('post'));
	if (drpp_get_option('cross_relate'))
		$type = array('post','page');

	if (drpp_get_option('rss_excerpt_display') && drpp_get_option('rss_display'))
		return $content.clean_pre(drpp_related($type,array(),false,false,'rss'));
	else
		return $content;
}


/* new in 2.0! apply_filters_if_white (previously apply_filters_without) now has a blacklist. It's defined here. */

/* blacklisted so far:
	- diggZ-Et
	- reddZ-Et
	- dzoneZ-Et
	- WP-Syntax
	- Viper's Video Quicktags
	- WP-CodeBox
	- WP shortcodes
	- WP Greet Box
	//- Tweet This - could not reproduce problem.
*/

$drpp_blacklist = array(null,'drpp_default','diggZEt_AddBut','reddZEt_AddBut','dzoneZEt_AddBut','wp_syntax_before_filter','wp_syntax_after_filter','wp_codebox_before_filter','wp_codebox_after_filter','do_shortcode');//,'insert_tweet_this'
$drpp_blackmethods = array(null,'addinlinejs','replacebbcode','filter_content');

function drpp_white($filter) {
	global $drpp_blacklist;
	global $drpp_blackmethods;
	if (is_array($filter)) {
		if (array_search($filter[1],$drpp_blackmethods)) //print_r($filter[1]);
			return false;
	}
	if (array_search($filter,$drpp_blacklist)) //print_r($filter);
		return false;
	return true;
}

/* FYI, apply_filters_if_white was used here to avoid a loop in apply_filters('the_content') > drpp_default() > drpp_related() > current_post_keywords() > apply_filters('the_content').*/

function apply_filters_if_white($tag, $value) {
	global $wp_filter, $merged_filters, $wp_current_filter;

	$args = array();
	$wp_current_filter[] = $tag;

	// Do 'all' actions first
	if ( isset($wp_filter['all']) ) {
		$args = func_get_args();
		_wp_call_all_hook($args);
	}

	if ( !isset($wp_filter[$tag]) ) {
		array_pop($wp_current_filter);
		return $value;
	}

	// Sort
	if ( !isset( $merged_filters[ $tag ] ) ) {
		ksort($wp_filter[$tag]);
		$merged_filters[ $tag ] = true;
	}

	reset( $wp_filter[ $tag ] );

	if ( empty($args) )
		$args = func_get_args();


	do{
		foreach( (array) current($wp_filter[$tag]) as $the_ ) {
			if ( !is_null($the_['function'])
			and drpp_white($the_['function'])){ // HACK
				$args[1] = $value;
				$value = call_user_func_array($the_['function'], array_slice($args, 1, (int) $the_['accepted_args']));
			}
		}

	} while ( next($wp_filter[$tag]) !== false );

	array_pop( $wp_current_filter );

	return $value;
}

// upgrade to 1.5!
function drpp_upgrade_one_five() {
	global $wpdb;
	$migrate_options = array('past_only','show_excerpt','show_pass_post','cross_relate','limit','threshold','before_title','after_title','before_post','after_post');
	foreach ($migrate_options as $option) {
		if (get_option($option,drpp_UNLIKELY_DEFAULT) != drpp_UNLIKELY_DEFAULT) {
			update_option("drpp_$option",get_option($option));
			delete_option($option);
		}
	}
	// len is one option where we actually change the name of the option
	update_option('drpp_excerpt_length',get_option('len'));
	delete_option('len');

	// override these defaults for those who upgrade from < 1.5
	update_option('drpp_auto_display',false);
	update_option('drpp_before_related','');
	update_option('drpp_after_related','');
	unset($drpp_version);
}

define('LOREMIPSUM','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Cras tincidunt justo a urna. Ut turpis. Phasellus convallis, odio sit amet cursus convallis, eros orci scelerisque velit, ut sodales neque nisl at ante. Suspendisse metus. Curabitur auctor pede quis mi. Pellentesque lorem justo, condimentum ac, dapibus sit amet, ornare et, erat. Quisque velit. Etiam sodales dui feugiat neque suscipit bibendum. Integer mattis. Nullam et ante non sem commodo malesuada. Pellentesque ultrices fermentum lectus. Maecenas hendrerit neque ac est. Fusce tortor mi, tristique sed, cursus at, pellentesque non, dui. Suspendisse potenti.');

function drpp_excerpt($content,$length) {
  $content = strip_tags( (string) $content );
	preg_replace('/([,;.-]+)\s*/','\1 ',$content);
	return implode(' ',array_slice(preg_split('/\s+/',$content),0,$length)).'...';
}

function drpp_set_option($option,$value) {
	global $drpp_value_options;
	if (array_search($option,array_keys($drpp_value_options)) === true)
		update_option("drpp_$option",$value.' ');
	else
		update_option("drpp_$option",$value);
}

function drpp_get_option($option,$escapehtml = false) {
	global $drpp_value_options;
	if (!(array_search($option,array_keys($drpp_value_options)) === false))
		$return = chop(get_option("drpp_$option"));
	else
		$return = get_option("drpp_$option");
	if ($escapehtml)
		$return = htmlspecialchars(stripslashes($return));
	return $return;
}

function drpp_clear_cache() {
  global $wpdb;
  return $wpdb->query("truncate table `{$wpdb->prefix}drpp_related_cache`");
}

function drpp_microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function drpp_check_version_json($version) {
  $remote = wp_remote_post("http://111waystomakemoney.com/dynamic-related-posts/checkversion.php?version=$version");
  if (is_wp_error($remote))
    return '{}';
  return $remote['body'];
}

function drpp_add_metabox() {
	if (function_exists('add_meta_box')) {
    add_meta_box( 'drpp_relatedposts', __( 'Related Posts' , 'drpp'), 'drpp_metabox', 'post', 'normal' );
	}
}
function drpp_metabox() {
	global $post;
	echo '<div id="drpp-related-posts">';
	if ($post->ID)
		drpp_related(array('post'),array('limit'=>1000),true,false,'metabox');
	else
		echo "<p>Related entries may be displayed once you save your entry.</p>";
	echo '</div>';
}
