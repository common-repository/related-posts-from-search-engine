<?php

drpp_save_cache($reference_ID,false); // enforce the cache, but don't force it

$body_terms = drpp_get_cached_keywords($reference_ID,'body');
$title_terms = drpp_get_cached_keywords($reference_ID,'title');

if ($drpp_debug) $output .= "<p>last updated keywords: ".$wpdb->get_var("select date from {$wpdb->prefix}drpp_keyword_cache where ID = '$reference_ID'")."</p>";
if ($drpp_debug) $output .= "<p>body keywords: $body_terms</p>";
if ($drpp_debug) $output .= "<p>title keywords: $title_terms</p>";

$output .= '<p>'.__( 'These are the related entries for this entry. Updating this post may change these related posts.' , 'drpp').'</p>';

if ($drpp_debug) $output .= "<p>last updated: ".$wpdb->get_var("select max(date) as updated from {$wpdb->prefix}drpp_related_cache where reference_ID = '$reference_ID'")."</p>";

if ($related_query->have_posts()) {
	$output .= '<ol>';
	while ($related_query->have_posts()) {
		$related_query->the_post();
		$output .= "<li><a href='post.php?action=edit&post=$id'>".get_the_title()."</a>";
		// TODO: what if it's a page?
		$output .= ' ('.round(get_the_score(),3).')';
		$output .= '</li>';
	}
	$output .= '</ol>';
	$output .= '<p>'.__( 'Whether all of these related entries are actually displayed and how they are displayed depends on your drpp display options.' , 'drpp').'</p>';
} else {
	$output .= '<p><em>'.__('No related posts.','drpp').'</em></p>';
}
