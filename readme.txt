=== Related Posts From Search Engine - Better related posts ===
Contributors: ramireddy
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KSMA4U56YLF66&lc=US&item_name=111waystomakemoney&currency_code=USD&bn=PP%2dDonationsBF%3adonate%2epng%3aNonHostedGuest
Tags: google, plugin, posts,related, posts, post, pages, page, RSS, feed, feeds
Requires at least: 2.3
Tested up to: 2.7.1
Stable tag: 3.00

When someone is referred from a search engine like Google, the plugin show your blog content matched the terms they search for.

== Description ==
For detailed description of the plugin visit plugin page at[Related post plugin](http://111waystomakemoney.com/dynamic-related-posts/).

Dynamic Related Posts Plugin (drpp) gives you a list of posts and/or pages related to the current entry, introducing the reader to other relevant content on your site. Key features include:

1. **An advanced and versatile algorithm**: Using a customizable algorithm considering post titles, content, tags, and categories, drpp calculates a "match score" for each pair of posts on your blog. You choose the threshold limit for relevance and you get more related posts if there are more related posts and less if there are less.
2. **Templating**: **New in 1.0!** 
3. **Caching**: **Improved in 1.0!** drpp organically caches the related posts data as your site is visited, greatly improving performance.
4. **Related posts in RSS feeds**: Display related posts in your RSS and Atom feeds with custom display options.
5. **Disallowing certain tags or categories**: You can choose certain tags or categories as disallowed, meaning any page or post with such tags or categories will not be served up by the plugin.
6. **Related posts and pages**: Puts you in control of pulling up related posts, pages, or both.

This plugin requires that your database run on MySQL 4.1 or greater.

For detailed description of the plugin visit plugin page at[Related post plugin](http://111waystomakemoney.com/dynamic-related-posts/).

== Installation ==
For detailed description and installation details of the plugin visit plugin page at[Related post plugin](http://111waystomakemoney.com/dynamic-related-posts/).

= Auto display on your website =

1. Copy the folder `dynamic-related-posts` into the directory `wp-content/plugins/` and (optionally) the sample templates inside `drpp-templates` folder into your active theme.

2. Activate the plugin.

= Auto display in your feeds =

Make sure the "display related posts in feeds" option is turned on if you would like to show related posts in your RSS and Atom feeds. The "display related posts in feeds" option can be used regardless of whether you auto display them on your website (and vice versa).

= Widget =

Related posts can also be displayed as a widget. Go to the Design > Widgets options page and add the Related Posts widget. The widget will only be displayed on single entry (permalink) pages. The widget can be used even if the "auto display" option is turned off.

For detailed description and installation details of the plugin visit plugin page at[Related post plugin](http://111waystomakemoney.com/dynamic-related-posts/).


== Frequently Asked Questions ==

If your question isn't here, ask your own question at [111waystomakemoney](http://111waystomakemoney.com). *Please do not email or tweet with questions.*

= How can I move the related posts display? =

If you do not want to show the Related Posts display in its default position (right below the post content), first go to drpp options and turn off the "automatically display" option in the "website" section. If you would like to instead display it in your sidebar and you have a widget-aware theme, drpp provides a Related Posts widget which you can add under "Appearance" > "Widgets".

If you would like to add the Related Posts display elsewhere, follow these directions: (*Knowledge of PHP and familiarity with editing your WordPress theme files is required.*)

Edit your relevant theme file (most likely something like `single.php`) and add the PHP code `related_posts();` within [The Loop](http://codex.wordpress.org/The_Loop) where you want to display the related posts. 

This method can also be used to display drpp on pages other than single-post displays, such as on archive pages. There is a little more information on the [manual installation page](http://111waystomakemoney.com/dynamic-related-posts/).

= Does drpp slow down my blog/server? =

A little bit, yes. However, drpp 1.0 introduced a new caching mechanism which greatly reduces the hit of the computationally intensive relatedness computation. In addition, *I highly recommend all drpp users use a page-caching plugin, such as [WP-SuperCache](http://111waystomakemoney.com/Dynamic-super-cache/).*

If you find that the drpp database calls are still too database-intensive, try the following:

* turning off "cross relate posts and pages";
* turning on "show only previous posts";
* not considering tags and/or categories in the Relatedness formula;
* not excluding any tags and/or categories in The Pool.

All of these can improve database performance.

= Every page just says "no related posts"! What's up with that? =

Most likely you have "no related posts" right now as the default "match threshold" is too high. Here's what I recommend to find an appropriate match threshold: first, lower your match threshold in the drpp prefs to something very low, like 1. Most likely the really low threshold will pull up many posts that aren't actually related (false positives), so look at some of your posts' related posts and their match scores. This will help you find an appropriate threshold. You want it lower than what you have now, but high enough so it doesn't have many false positives.

= How do I turn off the match score next to the related posts? =

The match score display is only for administrators... you can log out of `wp-admin` and check out the post again and you will see that the score is gone.


= I use DISQUS for comments. I can't access the drpp options page! =

The DISQUS plugin loads some JavaScript voodoo which is interacting in weird ways with the AJAX in drpp's options page. You can fix this by going to the DISQUS plugin advanced settings and turning on the "Check this if you have a problem with comment counts not showing on permalinks" option.

= I use DISQUS for comments. My RSS feed is now invalid and cannot be parsed by some clients! =

The DISQUS plugin loads some JavaScript voodoo when related posts are displayed, even in the RSS feed. You can fix this by going to the DISQUS plugin advanced settings and turning on the "Check this if you have a problem with comment counts not showing on permalinks" option.

= I get a PHP error saying "Cannot redeclare `related_posts()`" =

You most likely have another related posts plugin activated at the same time. Please disactivate those other plugins first before using drpp.

= I turned off one of the relatedness criteria (titles, bodies, tags, or categories) and now every page says "no related posts"! =

This has to do with the way the "match score" is computed. Every entry's match score is the weighted sum of its title-score, body-score, tag-score, and category-score. If you turn off one of the relatedness criteria, you will no doubt have to lower your match threshold to get the same number of related entries to show up. Alternatively, you can consider one of the other criteria "with extra weight".

It is recommended that you tweak your match threshold whenever you make changes to the "makeup" of your match score (i.e., the settings for the titles, bodies, tags, and categories items).

= Are there any plugins that are incompatible with drpp? =

Aside from the DISQUS plugin (see above), currently the only known incompatibility is [with the SEO_Pager plugin](http://wordpress.org/support/topic/267966) and the [Pagebar 2](http://www.elektroelch.de/hacks/wp/pagebar/) plugin. Users of SEO Pager are urged to turn off the automatic display option in SEO Pager and instead add the code manually. There are reports that the [WP Contact Form III plugin and Contact Form Plugin](http://wordpress.org/support/topic/392605) may also be incompatible with drpp. Other related posts plugins, obviously, may also be incompatible.

= Does drpp work with full-width characters or languages that don't use spaces between words? =

drpp works fine with full-width (double-byte) characters, assuming your WordPress database is set up with Unicode support. 99% of the time, if you're able to write blog posts with full-width characters and they're displayed correctly, drpp will work on your blog.

However, drpp does have difficulty with languages that don't place spaces between words (Chinese, Japanese, etc.). For these languages, the "consider body" and "consider titles" options in the "Relatedness options" may not be very helpful. Using only tags and categories may work better for these languages.

= Things are weird after I upgraded. =

I highly recommend you disactivate drpp, replace it with the new one, and then reactivate it.
If your question isn't here, ask your own question at [111waystomakemoney](http://111waystomakemoney.com). *Please do not email or tweet with questions.*

== Localizations ==



For detailed description and installation details of the plugin visit plugin page at[Related post plugin](http://111waystomakemoney.com/dynamic-related-posts/)


drpp is currently localized in the following languages:

	* Egyptian Arabic (`ar_EG`) by Bishoy Antoun (drpp-ar at mitcho dot com) of [cdmazika.com](http://www.cdmazika.com).
	* Standard Arabic (`ar`) by [led](http://led24.de) (drpp-ar at mitcho dot com)
  * Belarussian (`by_BY`) by [Fat Cow](http://www.fatcow.com)
  * Simplified Chinese (`zh_CN`) by Jor Wang (mail at jorwang dot com) of [jorwang.com](http://jorwang.com)
  * Cypriot Greek (`el_CY`) by Aristidis Tonikidis (drpp-el at mitcho dot com) of [akouseto.gr](http://www.akouseto.gr)
  * Dutch (`nl_NL`) by Sybrand van der Werf (drpp-nl at mitcho dot com)
  * French (`fr_FR`) by Lionel Chollet (drpp-fr at mitcho dot com)
  * German (`de_DE`) by Michael Kalina (drpp-de at mitcho dot com) of [3th.be](http://3th.be) - **we are now looking for a new German translator**
  * Greek (`el_EL`) by Aristidis Tonikidis (drpp-el at mitcho dot com) of [akouseto.gr](http://www.akouseto.gr)
  * Hebrew (`he_IL`) by Mickey Zelansky (drpp-he at mitcho dot com) of [simpleidea.us](http://simpleidea.us)
  * Hindi (`hi_IN`) by [Outshine Solutions](http://outshinesolutions.com/) (drpp-hi at mitcho dot com)
  * Italian (`it_IT`) by Gianni Diurno (drpp-it at mitcho dot com) of [gidibao.net](http://gidibao.net)
  * Japanese (`ja`) by myself (drpp at mitcho dot com)
  * Korean (`ko_KR`) by [Jong-In Kim](http://incommunity.codex.kr) (drpp-ko at mitcho dot com)
  * Latvian (`lv_LV`) by [Mike](http://antsar.info) (drpp-lv at mitcho dot com)
  * Lithuanian (`lt_LT`) by [Karolis Vy?ius](http://vycius.co.cc) (drpp-lt at mitcho dot com)
  * Polish (`pl_PL`) by [Perfecta](http://perfecta.pro/wp-pl/)
  * Brazilian Portuguese (`pt_BR`) by Rafael Fischmann (drpp-ptBR at mitcho.com) of [macmagazine.br](http://macmagazine.com.br/)
  * Russian (`ru_RU`) by Marat Latypov (drpp-ru at mitcho.com) of [blogocms.ru](http://blogocms.ru)
  * Swedish (`sv_SE`) by Max Elander (drpp-sv at mitcho dot com)
  * Turkish (`tr_TR`) by Nurullah (drpp-tr at mitcho.com) of [ndemir.com](http://www.ndemir.com)
  * Vietnamese (`vi_VN`) by Vu Nguyen (drpp-vi at mitcho dot com) of [Rubik Integration](http://rubikintegration.com/)
  * Ukrainian (`uk_UA`) by [Onore](http://Onore.kiev.ua) (Alexander Musevich) (drpp-uk at mitcho dot com)
  * Uzbek (`uz_UZ`) by Ali Safarov (drpp-uz at mitcho dot com) of [comfi.com](http://www.comfi.com/)
	
We already have localizers lined up for the following languages:

  * Danish
  * Spanish
  * Catalan
  * Indonesian
  * Hungarian
  * Romanian
  * Thai

Thanks!



For detailed description and installation details of the plugin visit plugin page at[Related post plugin](http://111waystomakemoney.com/dynamic-related-posts/)

== Changelog ==

= 1.0 =
* Initial release

= 1.3 =
* small bugs fixed
