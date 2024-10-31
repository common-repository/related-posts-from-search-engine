<?php

// setup the ajax action hooks
if (function_exists('add_action')) {
	add_action('wp_ajax_drpp_display_discats', 'drpp_ajax_display_discats');
	add_action('wp_ajax_drpp_display_distags', 'drpp_ajax_display_distags');
	add_action('wp_ajax_drpp_display_demo_web', 'drpp_ajax_display_demo_web');
	add_action('wp_ajax_drpp_display_demo_rss', 'drpp_ajax_display_demo_rss');
}

function drpp_ajax_display_discats() {
	global $wpdb;
	$discats = explode(',',drpp_get_option('discats'));
	array_unshift($discats,' ');
	foreach ($wpdb->get_results("select $wpdb->terms.term_id, name from $wpdb->terms natural join $wpdb->term_taxonomy where $wpdb->term_taxonomy.taxonomy = 'category' order by name") as $cat) {
		echo "<input type='checkbox' name='discats[$cat->term_id]' value='true'". (array_search($cat->term_id,$discats) ? ' checked="checked"': '' )."  /> <label>$cat->name</label> ";//for='discats[$cat->term_id]' it's not HTML. :(
	}
	exit;
}

function drpp_ajax_display_distags() {
	global $wpdb;
	$distags = explode(',',drpp_get_option('distags'));
	array_unshift($distags,' ');
	foreach ($wpdb->get_results("select $wpdb->terms.term_id, name from $wpdb->terms natural join $wpdb->term_taxonomy where $wpdb->term_taxonomy.taxonomy = 'post_tag' order by name") as $tag) {
		echo "<input type='checkbox' name='distags[$tag->term_id]' value='true'". (array_search($tag->term_id,$distags) ? ' checked="checked"': '' )."  /> <label>$tag->name</label> ";// for='distags[$tag->term_id]'
	}
	exit;
}
	
function drpp_ajax_display_demo_web() {
	global $wpdb, $post, $userdata, $drpp_demo_time, $wp_query, $id, $page, $pages, $drpp_limit;
	
	header("Content-Type: text/html; charset=UTF-8");
	
	$drpp_limit = drpp_get_option('limit');
	$return = drpp_related(array('post'),array(),false,false,'demo_web');
	unset($drpp_limit);
	echo ereg_replace("[\n\r]",'',nl2br(htmlspecialchars($return)));
	exit;
}

function drpp_ajax_display_demo_rss() {
	global $wpdb, $post, $userdata, $drpp_demo_time, $wp_query, $id, $page, $pages, $drpp_limit;
	
	header("Content-Type: text/html; charset=utf-8");
	
	$drpp_limit = drpp_get_option('rss_limit');
	$return = drpp_related(array('post'),array(),false,false,'demo_rss');
	unset($drpp_limit);
	echo ereg_replace("[\n\r]",'',nl2br(htmlspecialchars($return)));
	exit;
}
