<?php

global $wpdb, $drpp_value_options, $drpp_binary_options, $wp_version;

// check to see that templates are in the right place
$drpp_templateable = (count(glob(STYLESHEETPATH . '/drpp-template-*.php')) > 0);
if (!$drpp_templateable) {
  if (count(glob(WP_CONTENT_DIR.'/plugins/yet-another-related-posts-plugin/drpp-templates/drpp-template-*.php')))
  	echo "<div class='updated'>"
	  .str_replace("TEMPLATEPATH",STYLESHEETPATH,__("Please move the drpp template files into your theme to complete installation. Simply move the sample template files (currently in <code>wp-content/plugins/yet-another-related-posts-plugin/drpp-templates/</code>) to the <code>TEMPLATEPATH</code> directory.",'drpp'))
	  ."</div>";

  else 
  	echo "<div class='updated'>"
  	.str_replace('TEMPLATEPATH',STYLESHEETPATH,__("No drpp template files were found in your theme (<code>TEMPLATEPATH</code>)  so the templating feature has been turned off.",'drpp'))
  	."</div>";
  
  drpp_set_option('use_template',false);
  drpp_set_option('rss_use_template',false);
  
}

if ($_POST['myisam_override']) {
	drpp_set_option('myisam_override',1);
	echo "<div class='updated'>"
	.__("The MyISAM check has been overridden. You may now use the \"consider titles\" and \"consider bodies\" relatedness criteria.",'drpp')
	."</div>";
}

$drpp_myisam = true;
if (!drpp_get_option('myisam_override')) {
	$drpp_check_return = drpp_myisam_check();
	if ($drpp_check_return !== true) { // if it's not *exactly* true
		echo "<div class='updated'>"
		.sprintf(__("drpp's \"consider titles\" and \"consider bodies\" relatedness criteria require your <code>%s</code> table to use the <a href='http://dev.mysql.com/doc/refman/5.0/en/storage-engines.html'>MyISAM storage engine</a>, but the table seems to be using the <code>%s</code> engine. These two options have been disabled.",'drpp'),$wpdb->posts,$drpp_check_return)
		."<br />"
		.sprintf(__("To restore these features, please update your <code>%s</code> table by executing the following SQL directive: <code>ALTER TABLE `%s` ENGINE = MyISAM;</code> . No data will be erased by altering the table's engine, although there are performance implications.",'drpp'),$wpdb->posts,$wpdb->posts)
		."<br />"
		.sprintf(__("If, despite this check, you are sure that <code>%s</code> is using the MyISAM engine, press this magic button:",'drpp'),$wpdb->posts)
		."<br />"
		."<form method='post'><input type='submit' class='button' name='myisam_override' value='"
		.__("Trust me. Let me use MyISAM features.",'drpp')
		."'></input></form>"
		."</div>";
	
		drpp_set_option('title',1);
		drpp_set_option('body',1);
		$drpp_myisam = false;
	}
}

$drpp_twopointfive = true;
if (version_compare('2.5',$wp_version) > 0) {
	echo "$wp_version<div class='updated'>The \"consider tags\" and \"consider categories\" options require WordPress version 2.5. These two options have been disabled.</div>";

	drpp_set_option('categories',1);
	drpp_set_option('tags',1);
	$drpp_twopointfive = false;
}

if ($drpp_myisam) {
	if (!drpp_enabled()) {
		echo '<div class="updated"><p>';
		if (drpp_activate())
			_e('The drpp database had an error but has been fixed.','drpp');
		else 
			__('The drpp database has an error which could not be fixed.','drpp')
			.str_replace('<A>','<a href=\'http://111waystomakemoney.com/dynamic-related-posts/sql.php?prefix='.urlencode($wpdb->prefix).'\'>',__('Please try <A>manual SQL setup</a>.','drpp'));
		echo '</div></p>';
	}
}

drpp_reinforce(); // just in case, set default options, etc.

if (isset($_POST['update_drpp'])) {
	foreach (array_keys($drpp_value_options) as $option) {
    if (is_string($_POST[$option]))
      drpp_set_option($option,addslashes($_POST[$option]));
	}
	foreach (array('title','body','tags','categories') as $key) {
		if (!isset($_POST[$key])) drpp_set_option($key,1);
	}
	if (isset($_POST['discats'])) { 
		drpp_set_option('discats',implode(',',array_keys($_POST['discats']))); // discats is different
	} else {
		drpp_set_option('discats','');
	}

	if (isset($_POST['distags'])) { 
		drpp_set_option('distags',implode(',',array_keys($_POST['distags']))); // distags is also different
	} else {
		drpp_set_option('distags','');
	}
	//update_option('drpp_distags',implode(',',array_map('drpp_unmapthetag',preg_split('!\s*[;,]\s*!',strtolower($_POST['distags']))))); // distags is even more different
	
	foreach (array_keys($drpp_binary_options) as $option) {
		(isset($_POST[$option])) ? drpp_set_option($option,1) : drpp_set_option($option,0);
	}		
	echo '<div class="updated fade"><p>'.__('Options saved!','drpp').'</p></div>';
}
	
//compute $tagmap
$tagmap = array();
foreach ($wpdb->get_results("select $wpdb->terms.term_id, name from $wpdb->terms natural join $wpdb->term_taxonomy where $wpdb->term_taxonomy.taxonomy = 'category'") as $tag) {
	$tagmap[$tag->term_id] = strtolower($tag->name);
}

function drpp_mapthetag($id) {
	global $tagmap;
	return $tagmap[$id];
}
function drpp_unmapthetag($name) {
	global $tagmap;
	$untagmap = array_flip($tagmap);
	return $untagmap[$name];
}

function drpp_options_checkbox($option,$desc,$tr="<tr valign='top'>
			<th class='th-full' colspan='2' scope='row'>",$inputplus = '',$thplus='') {
	echo "			$tr<input $inputplus type='checkbox' name='$option' value='true'". ((drpp_get_option($option) == 1) ? ' checked="checked"': '' )."  /> $desc</th>$thplus
		</tr>";
}
function drpp_options_textbox($option,$desc,$size=2,$tr="<tr valign='top'>
			<th scope='row'>") {
	$value = stripslashes(drpp_get_option($option,true));
	echo "			$tr$desc</th>
			<td><input name='$option' type='text' id='$option' value='$value' size='$size' /></td>
		</tr>";
}
function drpp_options_importance($option,$desc,$type='word',$tr="<tr valign='top'>
			<th scope='row'>",$inputplus = '') {
	$value = drpp_get_option($option);
	
	// $type could be...
	__('word','drpp');
	__('tag','drpp');
	__('category','drpp');
	
	echo "		$tr$desc</th>
			<td>
			<input $inputplus type='radio' name='$option' value='1'". (($value == 1) ? ' checked="checked"': '' )."  /> ".__("do not consider",'drpp')."
			<input $inputplus type='radio' name='$option' value='2'". (($value == 2) ? ' checked="checked"': '' )."  /> ".__("consider",'drpp')."
			<input $inputplus type='radio' name='$option' value='3'". (($value == 3) ? ' checked="checked"': '' )."  /> 
			".sprintf(__("require at least one %s in common",'drpp'),__($type,'drpp'))."
			<input $inputplus type='radio' name='$option' value='4'". (($value == 4) ? ' checked="checked"': '' )."  /> 
			".sprintf(__("require more than one %s in common",'drpp'),__($type,'drpp'))."
			</td>
		</tr>";
}

function drpp_options_importance2($option,$desc,$type='word',$tr="<tr valign='top'>
			<th scope='row'>",$inputplus = '') {
	$value = drpp_get_option($option);

	echo "		$tr$desc</th>
			<td>
			<input $inputplus type='radio' name='$option' value='1'". (($value == 1) ? ' checked="checked"': '' )."  />
			".__("do not consider",'drpp')."
			<input $inputplus type='radio' name='$option' value='2'". (($value == 2) ? ' checked="checked"': '' )."  /> ".__("consider",'drpp')."
			<input $inputplus type='radio' name='$option' value='3'". (($value == 3) ? ' checked="checked"': '' )."  /> ".__("consider with extra weight",'drpp')."
			</td>
		</tr>";
}

function drpp_options_select($option,$desc,$type='word',$tr="<tr valign='top'>
			<th scope='row'>",$inputplus = '') {
	echo "		$tr$desc</th>
			<td>
			<input $inputplus type='radio' name='$option' value='1'". ((drpp_get_option($option) == 1) ? ' checked="checked"': '' )."  /> 
			".__("do not consider",'drpp')."
			<input $inputplus type='radio' name='$option' value='2'". ((drpp_get_option($option) == 2) ? ' checked="checked"': '' )."  />
			".__("consider",'drpp')."
			<input $inputplus type='radio' name='$option' value='3'". ((drpp_get_option($option) == 3) ? ' checked="checked"': '' )."  />
			".sprintf(__("require at least one %s in common",'drpp'),__($type,'drpp'))."
			<input $inputplus type='radio' name='$option' value='4'". ((drpp_get_option($option) == 4) ? ' checked="checked"': '' )."  />
			".sprintf(__("require more than one %s in common",'drpp'),__($type,'drpp'))."
			</td>
		</tr>";
}

?>
<script type="text/javascript">
//<!--

var rss=document.createElement("link");
rss.setAttribute("rel", "alternate");
rss.setAttribute("type", "application/rss+xml");
rss.setAttribute('title',"<?php _e("Dynamic Related Posts Plugin version history (RSS 2.0)",'drpp');?>");
rss.setAttribute("href", "http://111waystomakemoney.com/dynamic-related-posts/drpp.rss");
document.getElementsByTagName("head")[0].appendChild(rss);

var css=document.createElement("link");
css.setAttribute("rel", "stylesheet");
css.setAttribute("type", "text/css");
css.setAttribute("href", "../wp-content/plugins/yet-another-related-posts-plugin/options.css");
document.getElementsByTagName("head")[0].appendChild(css);

function load_display_demo_web() {
	jQuery.ajax({type:'POST',
	    url:'admin-ajax.php',
	    data:'action=drpp_display_demo_web',
	    beforeSend:function(){jQuery('#display_demo_web').eq(0).html('<img src="../wp-content/plugins/yet-another-related-posts-plugin/i/spin.gif" alt="loading..."/>')},
	    success:function(html){jQuery('#display_demo_web').eq(0).html('<pre>'+html+'</pre>')},
	    dataType:'html'}
	)
}

function load_display_demo_rss() {
	jQuery.ajax({type:'POST',
	    url:'admin-ajax.php',
	    data:'action=drpp_display_demo_rss',
	    beforeSend:function(){jQuery('#display_demo_rss').eq(0).html('<img src="../wp-content/plugins/yet-another-related-posts-plugin/i/spin.gif" alt="loading..."/>')},
	    success:function(html){jQuery('#display_demo_rss').eq(0).html('<pre>'+html+'</pre>')},
	    dataType:'html'}
	)
}

function load_display_distags() {
	jQuery.ajax({type:'POST',
	    url:'admin-ajax.php',
	    data:'action=drpp_display_distags',
	    beforeSend:function(){jQuery('#display_distags').eq(0).html('<img src="../wp-content/plugins/yet-another-related-posts-plugin/i/spin.gif" alt="loading..."/>')},
	    success:function(html){jQuery('#display_distags').eq(0).html(html)},
	    dataType:'html'}
	)
}

function load_display_discats() {
	jQuery.ajax({type:'POST',
	    url:'admin-ajax.php',
	    data:'action=drpp_display_discats',
	    beforeSend:function(){jQuery('#display_discats').eq(0).html('<img src="../wp-content/plugins/yet-another-related-posts-plugin/i/spin.gif" alt="loading..."/>')},
	    success:function(html){jQuery('#display_discats').eq(0).html(html)},
	    dataType:'html'}
	)
}
//-->
</script>

<div class="wrap">
		<h2>
			<?php _e('Dynamic Related Posts Plugin Options','drpp');?> <small><?php 
			
			$display_version = drpp_get_option('version');
      echo $display_version;
			?></small>
		</h2>

	<?php echo "<div id='drpp-version' style='display:none;'>".drpp_get_option('version')."</div>"; ?>
		
	<form method="post">

			<a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KSMA4U56YLF66&lc=US&item_name=111waystomakemoney&currency_code=USD&bn=PP%2dDonationsBF%3adonate%2epng%3aNonHostedGuest' target='_new'><img src="https://www.paypal.com/<?php echo paypal_directory(); ?>i/btn/btn_donate_SM.gif" name="submit" alt="<?php _e('Donate to Chetan for this plugin via PayPal');?>" title="<?php _e('Donate to Chetan for this plugin via PayPal','drpp');?>" style="float:right" /></a>

	<p><small><?php _e('by <a href="http://111waystomakemoney.com/">CHETAN</a>','drpp');?>. <?php _e('Follow <a href="http://twitter.com/111waystomakem">Dynamic Related Posts Plugin on Twitter</a>','drpp');?>.</small></p>


<!--	<div style='border:1px solid #ddd;padding:8px;'>-->
<div id="poststuff" class="metabox-holder">
<div class="meta-box-sortables">

	<!--The Pool-->
<script>
	jQuery(document).ready(function($) {
		$('.postbox').children('h3, .handlediv').click(function(){
			$(this).siblings('.inside').toggle();
		});
	});
</script>
<div class='postbox'>
    <div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>">
      <br/>
    </div>
	<h3 class='hndle'><span><?php _e('"The Pool"','drpp');?></span></h3>
<div class='inside'>
	<p><?php _e('"The Pool" refers to the pool of posts and pages that are candidates for display as related to the current entry.','drpp');?></p>
	
	<table class="form-table" style="margin-top: 0">
		<tbody>
			<tr valign='top'>
				<th scope='row'><?php _e('Disallow by category:','drpp');?></th><td><div id='display_discats' style="overflow:auto;max-height:100px;"></div></td></tr>
			<tr valign='top'>
				<th scope='row'><?php _e('Disallow by tag:','drpp');?></th>
				<td><div id='display_distags' style="overflow:auto;max-height:100px;"></div></td></tr>
<?php 
	drpp_options_checkbox('show_pass_post',__("Show password protected posts?",'drpp'));
	
	$recent_number = "<input name=\"recent_number\" type=\"text\" id=\"recent_number\" value=\"".stripslashes(drpp_get_option('recent_number',true))."\" size=\"2\" />";
	$recent_units = "<select name=\"recent_units\" id=\"recent_units\">
		<option value='day'". (('day'==drpp_get_option('recent_units'))?" selected='selected'":'').">".__('day(s)','drpp')."</option>
		<option value='week'". (('week'==drpp_get_option('recent_units'))?" selected='selected'":'').">".__('week(s)','drpp')."</option>
		<option value='month'". (('month'==drpp_get_option('recent_units'))?" selected='selected'":'').">".__('month(s)','drpp')."</option>
	</select>";
	drpp_options_checkbox('recent_only',str_replace('NUMBER',$recent_number,str_replace('UNITS',$recent_units,__("Show only posts from the past NUMBER UNITS",'drpp'))));
?>

		</tbody>
	</table>
	</div>
</div>

	<!-- Relatedness -->
<div class='postbox'>
    <div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>">
      <br/>
    </div>
	<h3 class='hndle'><span><?php _e('"Relatedness" options','drpp');?></span></h3>
<div class='inside'>

	
	
	<table class="form-table" style="margin-top: 0">
		<tbody>
	
<?php
	drpp_options_textbox('threshold',__('Match threshold:','drpp'));
	drpp_options_importance2('title',__("Titles: ",'drpp'),'word',"<tr valign='top'>
			<th scope='row'>",(!$drpp_myisam?' readonly="readonly" disabled="disabled"':''));
	drpp_options_importance2('body',__("Bodies: ",'drpp'),'word',"<tr valign='top'>
			<th scope='row'>",(!$drpp_myisam?' readonly="readonly" disabled="disabled"':''));
	drpp_options_importance('tags',__("Tags: ",'drpp'),'tag',"<tr valign='top'>
			<th scope='row'>",(!$drpp_twopointfive?' readonly="readonly" disabled="disabled"':''));
	drpp_options_importance('categories',__("Categories: ",'drpp'),'category',"<tr valign='top'>
			<th scope='row'>",(!$drpp_twopointfive?' readonly="readonly" disabled="disabled"':''));
	drpp_options_checkbox('cross_relate',__("Cross-relate posts and pages?",'drpp')." <a href='#' class='info'>".__('more&gt;','drpp')."<span>".__("When the \"Cross-relate posts and pages\" option is selected, the <code>related_posts()</code>, <code>related_pages()</code>, and <code>related_entries()</code> all will give the same output, returning both related pages and posts.",'drpp')."</span></a>");
	drpp_options_checkbox('past_only',__("Show only previous posts?",'drpp'));
?>
			</tbody>
		</table>
	</div>
</div>



		<!-- Display options -->
<div class='postbox'>
    <div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>">
      <br/>
    </div>
	<h3 class='hndle'><span><?php _e("Display options <small>for your website</small>",'drpp');?></span></h3>
<div class='inside'>
		
		<table class="form-table" style="margin-top: 0;width:100%">
<?php
drpp_options_checkbox('auto_display',__("Automatically display related posts?",'drpp')." <a href='#' class='info'>".__('more&gt;','drpp')."<span>".__("This option automatically displays related posts right after the content on single entry pages. If this option is off, you will need to manually insert <code>related_posts()</code> or variants (<code>related_pages()</code> and <code>related_entries()</code>) into your theme files.",'drpp')."</span></a>","<tr valign='top'>
			<th class='th-full' colspan='2' scope='row'>",'','<td rowspan="11" style="border-left:8px transparent solid;"><b>'.__("",'drpp').'</b><br /><small>'.__("",'drpp').'</small><br/>'
."<div id='display_demo_web' style='overflow:auto;width:350px;max-height:500px;'></div></td>");?>

	<?php drpp_options_textbox('limit',__('Maximum number of related posts:','drpp'))?>
	<?php drpp_options_checkbox('use_template',__("Display using a custom template file",'drpp')." <a href='#' class='info'>".__('more&gt;','drpp')."<span>".__("This advanced option gives you full power to customize how your related posts are displayed. Templates (stored in your theme folder) are written in PHP.",'drpp')."</span></a>","<tr valign='top'><th colspan='2'>",' class="template" onclick="javascript:template()"'.(!$drpp_templateable?' disabled="disabled"':'')); ?>
			<tr valign='top' class='templated'>
				<th><?php _e("Template file:",'drpp');?></th>
				<td>
					<select name="template_file" id="template_file">
						<?php foreach (glob(STYLESHEETPATH . '/drpp-template-*.php') as $template): ?>
						<option value='<?php echo htmlspecialchars(basename($template))?>'<?php echo (basename($template)==drpp_get_option('template_file'))?" selected='selected'":'';?>><?php echo htmlspecialchars(basename($template))?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr valign='top' class='not_templated'>
				<th><?php _e("Before / after related entries:",'drpp');?></th>
				<td><input name="before_related" type="text" id="before_related" value="<?php echo stripslashes(drpp_get_option('before_related',true)); ?>" size="10" /> / <input name="after_related" type="text" id="after_related" value="<?php echo stripslashes(drpp_get_option('after_related',true)); ?>" size="10" /><em><small> <?php _e("For example:",'drpp');?> &lt;ol&gt;&lt;/ol&gt;<?php _e(' or ','drpp');?>&lt;div&gt;&lt;/div&gt;</small></em>
				</td>
			</tr>
			<tr valign='top' class='not_templated'>
				<th><?php _e("Before / after each related entry:",'drpp');?></th>
				<td><input name="before_title" type="text" id="before_title" value="<?php echo stripslashes(drpp_get_option('before_title',true)); ?>" size="10" /> / <input name="after_title" type="text" id="after_title" value="<?php echo stripslashes(drpp_get_option('after_title',true)); ?>" size="10" /><em><small> <?php _e("For example:",'drpp');?> &lt;li&gt;&lt;/li&gt;<?php _e(' or ','drpp');?>&lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>
	<?php drpp_options_checkbox('show_excerpt',__("Show excerpt?",'drpp'),"<tr class='not_templated' valign='top'><th colspan='2'>",' class="show_excerpt" onclick="javascript:excerpt()"'); ?>
	<?php drpp_options_textbox('excerpt_length',__('Excerpt length (No. of words):','drpp'),null,"<tr class='excerpted' valign='top'>
				<th>")?>
	
			<tr class="excerpted" valign='top'>
				<th><?php _e("Before / after (Excerpt):",'drpp');?></th>
				<td><input name="before_post" type="text" id="before_post" value="<?php echo stripslashes(drpp_get_option('before_post',true)); ?>" size="10" /> / <input name="after_post" type="text" id="after_post" value="<?php echo stripslashes(drpp_get_option('after_post')); ?>" size="10" /><em><small> <?php _e("For example:",'drpp');?> &lt;li&gt;&lt;/li&gt;<?php _e(' or ','drpp');?>&lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>

			<tr valign='top'>
				<th><?php _e("Order results:",'drpp');?></th>
				<td><select name="order" id="order">
					<option value="score DESC" <?php echo (drpp_get_option('order')=='score DESC'?' selected="selected"':'')?>><?php _e("score (high relevance to low)",'drpp');?></option>
					<option value="score ASC" <?php echo (drpp_get_option('order')=='score ASC'?' selected="selected"':'')?>><?php _e("score (low relevance to high)",'drpp');?></option>
					<option value="post_date DESC" <?php echo (drpp_get_option('order')=='post_date DESC'?' selected="selected"':'')?>><?php _e("date (new to old)",'drpp');?></option>
					<option value="post_date ASC" <?php echo (drpp_get_option('order')=='post_date ASC'?' selected="selected"':'')?>><?php _e("date (old to new)",'drpp');?></option>
					<option value="post_title ASC" <?php echo (drpp_get_option('order')=='post_title ASC'?' selected="selected"':'')?>><?php _e("title (alphabetical)",'drpp');?></option>
					<option value="post_title DESC" <?php echo (drpp_get_option('order')=='post_title DESC'?' selected="selected"':'')?>><?php _e("title (reverse alphabetical)",'drpp');?></option>
				</select>
				</td>
			</tr>
	
	<?php drpp_options_textbox('no_results',__('Default display if no results:','drpp'),'40',"<tr class='not_templated' valign='top'>
				<th>")?>
	
		</table>
		</div>
	</div>

		<!-- Display options for RSS -->
<div class='postbox'>
    <div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>">
      <br/>
    </div>
	<h3 class='hndle'><span><?php _e("Display options <small>for RSS</small>",'drpp');?></span></h3>
<div class='inside'>
		
		<table class="form-table" style="margin-top: 0;width:100%">
<?php

drpp_options_checkbox('rss_display',__("Display related posts in feeds?",'drpp')." <a href='#' class='info'>".__('more&gt;','drpp')."<span>".__("This option displays related posts at the end of each item in your RSS and Atom feeds. No template changes are needed.",'drpp')."</span></a>","<tr valign='top'><th colspan='3'>",' class="rss_display" onclick="javascript:rss_display();"');
drpp_options_checkbox('rss_excerpt_display',__("Display related posts in the descriptions?",'drpp')." <a href='#' class='info'>".__('more&gt;','drpp')."<span>".__("This option displays the related posts in the RSS description fields, not just the content. If your feeds are set up to only display excerpts, however, only the description field is used, so this option is required for any display at all.",'drpp')."</span></a>","<tr class='rss_displayed' valign='top'>
			<th class='th-full' colspan='2' scope='row'>",'','<td rowspan="9" style="border-left:8px transparent solid;"><b>'.__("",'drpp').'</b><br /><small>'.__("",'drpp').'</small><br/>'
."<div id='display_demo_rss' style='overflow:auto;width:350px;max-height:500px;'></div></td>"); ?>
	<?php drpp_options_textbox('rss_limit',__('Maximum number of related posts:','drpp'),2)?>
	<?php drpp_options_checkbox('rss_use_template',__("Display using a custom template file",'drpp')." <!--<span style='color:red;'>".__('NEW!','drpp')."</span>--> <a href='#' class='info'>".__('more&gt;','drpp')."<span>".__("This advanced option gives you full power to customize how your related posts are displayed. Templates (stored in your theme folder) are written in PHP.",'drpp')."</span></a>","<tr valign='top'><th colspan='2'>",' class="rss_template" onclick="javascript:rss_template()"'.(!$drpp_templateable?' disabled="disabled"':'')); ?>
			<tr valign='top' class='rss_templated'>
				<th><?php _e("Template file:",'drpp');?></th>
				<td>
					<select name="rss_template_file" id="rss_template_file">
						<?php foreach (glob(STYLESHEETPATH . '/drpp-template-*.php') as $template): ?>
						<option value='<?php echo htmlspecialchars(basename($template))?>'<?php echo (basename($template)==drpp_get_option('rss_template_file'))?" selected='selected'":'';?>><?php echo htmlspecialchars(basename($template))?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr class='rss_not_templated' valign='top'>
				<th><?php _e("Before / after related entries display:",'drpp');?></th>
				<td><input name="rss_before_related" type="text" id="rss_before_related" value="<?php echo stripslashes(drpp_get_option('rss_before_related',true)); ?>" size="10" /> / <input name="rss_after_related" type="text" id="rss_after_related" value="<?php echo stripslashes(drpp_get_option('rss_after_related',true)); ?>" size="10" /><em><small> <?php _e("For example:",'drpp');?> &lt;ol&gt;&lt;/ol&gt;<?php _e(' or ','drpp');?>&lt;div&gt;&lt;/div&gt;</small></em>
				</td>
			</tr>
			<tr class='rss_not_templated' valign='top'>
				<th><?php _e("Before / after each related entry:",'drpp');?></th>
				<td><input name="rss_before_title" type="text" id="rss_before_title" value="<?php echo stripslashes(drpp_get_option('rss_before_title',true)); ?>" size="10" /> / <input name="rss_after_title" type="text" id="rss_after_title" value="<?php echo stripslashes(drpp_get_option('rss_after_title',true)); ?>" size="10" /><em><small> <?php _e("For example:",'drpp');?> &lt;li&gt;&lt;/li&gt;<?php _e(' or ','drpp');?>&lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>
	<?php drpp_options_checkbox('rss_show_excerpt',__("Show excerpt?",'drpp'),"<tr class='rss_not_templated' valign='top'><th colspan='2'>",' class="rss_show_excerpt" onclick="javascript:rss_excerpt()"'); ?>
	<?php drpp_options_textbox('rss_excerpt_length',__('Excerpt length (No. of words):','drpp'),null,"<tr class='rss_excerpted' valign='top'>
				<th>")?>
	
			<tr class="rss_excerpted" valign='top'>
				<th><?php _e("Before / after (excerpt):",'drpp');?></th>
				<td><input name="rss_before_post" type="text" id="rss_before_post" value="<?php echo stripslashes(drpp_get_option('rss_before_post',true)); ?>" size="10" /> / <input name="rss_after_post" type="text" id="rss_after_post" value="<?php echo stripslashes(drpp_get_option('rss_after_post')); ?>" size="10" /><em><small> <?php _e("For example:",'drpp');?> &lt;li&gt;&lt;/li&gt;<?php _e(' or ','drpp');?>&lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>

			<tr class='rss_displayed' valign='top'>
				<th><?php _e("Order results:",'drpp');?></th>
				<td><select name="rss_order" id="rss_order">
					<option value="score DESC" <?php echo (drpp_get_option('rss_order')=='score DESC'?' selected="selected"':'')?>><?php _e("score (high relevance to low)",'drpp');?></option>
					<option value="score ASC" <?php echo (drpp_get_option('rss_order')=='score ASC'?' selected="selected"':'')?>><?php _e("score (low relevance to high)",'drpp');?></option>
					<option value="post_date DESC" <?php echo (drpp_get_option('rss_order')=='post_date DESC'?' selected="selected"':'')?>><?php _e("date (new to old)",'drpp');?></option>
					<option value="post_date ASC" <?php echo (drpp_get_option('rss_order')=='post_date ASC'?' selected="selected"':'')?>><?php _e("date (old to new)",'drpp');?></option>
					<option value="post_title ASC" <?php echo (drpp_get_option('rss_order')=='post_title ASC'?' selected="selected"':'')?>><?php _e("title (alphabetical)",'drpp');?></option>
					<option value="post_title DESC" <?php echo (drpp_get_option('rss_order')=='post_title DESC'?' selected="selected"':'')?>><?php _e("title (reverse alphabetical)",'drpp');?></option>
				</select>
				</td>
			</tr>
	
	<?php drpp_options_textbox('rss_no_results',__('Default display if no results:','drpp'),'40',"<tr valign='top' class='rss_not_templated'>
			<th scope='row'>")?>
	<?php drpp_options_checkbox('rss_promote_drpp',__("Help promote Dynamic Related Posts Plugin?",'drpp')." <a href='#' class='info'>".__('more&gt;','drpp')."<span>"
	.sprintf(__("This option will add the code %s. Try turning it on, updating your options, and see the code in the code example to the right. These links and donations are greatly appreciated.", 'drpp'),"<code>".htmlspecialchars(__("Related posts brought to you by <a href='http://111waystomakemoney.com/dynamic-related-posts/'>Dynamic Related Posts Plugin</a>.",'drpp'))."</code>")	."</span></a>","<tr valign='top' class='rss_displayed'>
			<th class='th-full' colspan='2' scope='row'>"); ?>
		</table>
		</div>
	</div>
	
	<div>
		<p class="submit">
			<input type="submit" class='button-primary' name="update_drpp" value="<?php _e("Update options",'drpp')?>" />
			<input type="submit" onclick='return confirm("<?php _e("Do you really want to reset your configuration?",'drpp');?>");' class="drpp_warning" name="reset_drpp" value="<?php _e('Reset options','drpp')?>" />
		</p>
	</div>

</div></div> <!--closing metabox containers-->

</form>

<?php

?>