<?php
/*
Plugin Name: Related Posts From Search Engine
Plugin URI: http://111waystomakemoney.com/dynamic-related-posts/
Description: Returns a list of related entries based on a unique algorithm for display on your blog and RSS feeds. A templating feature allows customization of the display.
Version: 2.00
Author: ramireddy
Author URI: http://111waystomakemoney.com
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KSMA4U56YLF66&lc=US&item_name=111waystomakemoney&currency_code=USD&bn=PP%2dDonationsBF%3adonate%2epng%3aNonHostedGuest
*/

define('drpp_VERSION','1.0');
define('drpp_DIR',dirname(__FILE__));

require_once(drpp_DIR.'/includes.php');
require_once(drpp_DIR.'/related-functions.php');
require_once(drpp_DIR.'/template-functions.php');

add_action('admin_menu','drpp_admin_menu');
add_action('admin_print_scripts','drpp_upgrade_check');
add_filter('the_content','drpp_default',1200);
add_filter('the_content_rss','drpp_rss',600);
add_filter('the_excerpt_rss','drpp_rss_excerpt',600);
register_activation_hook(__FILE__,'drpp_activate');

// new in 3.1: clear cache when updating certain settings.
add_action('update_option_drpp_distags','drpp_clear_cache');
add_action('update_option_drpp_discats','drpp_clear_cache');
add_action('update_option_drpp_show_pass_post','drpp_clear_cache');
add_action('update_option_drpp_recent_only','drpp_clear_cache');
add_action('update_option_drpp_threshold','drpp_clear_cache');
add_action('update_option_drpp_title','drpp_clear_cache');
add_action('update_option_drpp_body','drpp_clear_cache');
add_action('update_option_drpp_categories','drpp_clear_cache');
add_action('update_option_drpp_tags','drpp_clear_cache');
add_action('update_option_drpp_tags','drpp_clear_cache');

load_plugin_textdomain('drpp', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)).'/lang',dirname(plugin_basename(__FILE__)).'/lang');

// new in 2.0: add as a widget
add_action('widgets_init', 'widget_drpp_init');
// new in 3.0: add meta box
add_action( 'admin_menu', 'drpp_add_metabox');

// update cache on save
add_action('save_post','drpp_save_cache');

add_filter('posts_join','drpp_join_filter');
add_filter('posts_where','drpp_where_filter');
add_filter('posts_orderby','drpp_orderby_filter');
add_filter('posts_fields','drpp_fields_filter');
add_filter('posts_request','drpp_demo_request_filter');
add_filter('post_limits','drpp_limit_filter');
add_action('parse_query','drpp_set_score_override_flag'); // sets the score override flag. 

// set $drpp_debug
if (isset($_REQUEST['drpp_debug']))
  $drpp_debug = true;
