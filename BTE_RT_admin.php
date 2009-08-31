<?php
/*  Copyright 2008-2009  Blog Traffic Exchange (email : kevin@blogtrafficexchange.com)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once('RelatedTweets.php');
require_once('BTE_RT_core.php');

function bte_rt_head_admin() {
	wp_enqueue_script('jquery-ui-tabs');
	$home = get_settings('siteurl');
	$base = '/'.end(explode('/', str_replace(array('\\','/BTE_RT_admin.php'),array('/',''),__FILE__)));
	$stylesheet = $home.'/wp-content/plugins' . $base . '/css/related_tweets.css';
	echo('<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />');
}

function bte_rt_options() {	 	
	$message = null;
	$message_updated = __("Related Tweets Options Updated.", 'bte_related_tweets');
	if (!empty($_POST['bte_rt_action'])) {
		$message = $message_updated;
		if (isset($_POST['bte_rt_twitter_username'])) {
			update_option('bte_rt_twitter_username',$_POST['bte_rt_twitter_username']);
		}
		if (isset($_POST['bte_rt_twitter_password'])) {
			update_option('bte_rt_twitter_password',$_POST['bte_rt_twitter_password']);
		}
		if (isset($_POST['bte_rt_interval'])) {
			update_option('bte_rt_interval',$_POST['bte_rt_interval']);
		}
		if (isset($_POST['bte_rt_interval_slop'])) {
			update_option('bte_rt_interval_slop',$_POST['bte_rt_interval_slop']);
		}
		if (isset($_POST['post_category'])) {
			update_option('bte_rt_omit_cats',implode(',',$_POST['post_category']));
		}
		else {
			update_option('bte_rt_omit_cats','');			
		}
		
		print('
			<div id="message" class="updated fade">
				<p>'.__('Related Tweets Options Updated.', 'RelatedWebsites').'</p>
			</div>');
	}
	$twitter_username = get_option('bte_rt_twitter_username');
	$twitter_password = get_option('bte_rt_twitter_password');
	$omitCats = get_option('bte_rt_omit_cats');
	if (!isset($omitCats)) {
		$omitCats = BTE_RT_OMIT_CATS;
	}
	$interval = get_option('bte_rt_interval');		
	if (!(isset($interval) && is_numeric($interval))) {
		$interval = BTE_RT_INTERVAL;
	}
	$slop = get_option('bte_rt_interval_slop');		
	if (!(isset($slop) && is_numeric($slop))) {
		$slop = BTE_RT_INTERVAL_SLOP;
	}
	
	print('
			<div class="wrap">
				<h2>'.__('Related Tweets by', 'RelatedTweets').' <a href="http://www.blogtrafficexchange.com">Blog Traffic Exchange</a></h2>
				<form id="bte_rt" name="bte_relatedtweets" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=BTE_RT_admin.php" method="post">
					<input type="hidden" name="bte_rt_action" value="bte_rt_update_settings" />
					<fieldset class="options">
						<div class="option">
							<label for="bte_rt_twitter_username">'.__('Twitter Username', 'RelatedTweets').'/'.__('Password', 'RelatedTweets').':</label>
							<input type="text" size="25" name="bte_rt_twitter_username" id="bte_rt_twitter_username" value="'.$twitter_username.'" autocomplete="off" />
							<input type="password" size="25" name="bte_rt_twitter_password" id="bte_rt_twitter_password" value="'.$twitter_password.'" autocomplete="off" />
							</div>
						<div class="option">
							<label for="bte_rt_interval">'.__('Minimum Interval Between Tweets: ', 'RelatedTweets').'</label>
							<select name="bte_rt_interval" id="bte_rt_interval">
									<option value="'.BTE_RT_10_MINUTES.'" '.bte_rt_optionselected(BTE_RT_10_MINUTES,$interval).'>'.__('10 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_20_MINUTES.'" '.bte_rt_optionselected(BTE_RT_20_MINUTES,$interval).'>'.__('20 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_30_MINUTES.'" '.bte_rt_optionselected(BTE_RT_30_MINUTES,$interval).'>'.__('30 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_1_HOUR.'" '.bte_rt_optionselected(BTE_RT_1_HOUR,$interval).'>'.__('1 Hour', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_2_HOUR.'" '.bte_rt_optionselected(BTE_RT_2_HOUR,$interval).'>'.__('2 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_3_HOUR.'" '.bte_rt_optionselected(BTE_RT_3_HOUR,$interval).'>'.__('3 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_4_HOURS.'" '.bte_rt_optionselected(BTE_RT_4_HOURS,$interval).'>'.__('4 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_6_HOURS.'" '.bte_rt_optionselected(BTE_RT_6_HOURS,$interval).'>'.__('6 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_12_HOURS.'" '.bte_rt_optionselected(BTE_RT_12_HOURS,$interval).'>'.__('12 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_24_HOURS.'" '.bte_rt_optionselected(BTE_RT_24_HOURS,$interval).'>'.__('24 Hours (1 day)', 'RelatedTweets').'</option>
							</select>
						</div>
						<div class="option">
							<label for="bte_rt_interval_slop">'.__('Randomness Interval (added to minimum interval): ', 'RelatedTweets').'</label>
							<select name="bte_rt_interval_slop" id="bte_rt_interval_slop">
									<option value="'.BTE_RT_1_MINUTE.'" '.bte_rt_optionselected(BTE_RT_1_MINUTE,$slop).'>'.__('Upto 1 Minute', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_5_MINUTES.'" '.bte_rt_optionselected(BTE_RT_5_MINUTES,$slop).'>'.__('Upto 5 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_10_MINUTES.'" '.bte_rt_optionselected(BTE_RT_10_MINUTES,$slop).'>'.__('Upto 10 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_20_MINUTES.'" '.bte_rt_optionselected(BTE_RT_20_MINUTES,$slop).'>'.__('Upto 20 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_30_MINUTES.'" '.bte_rt_optionselected(BTE_RT_30_MINUTES,$slop).'>'.__('Upto 30 Minutes', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_1_HOUR.'" '.bte_rt_optionselected(BTE_RT_1_HOUR,$slop).'>'.__('Upto 1 Hour', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_2_HOUR.'" '.bte_rt_optionselected(BTE_RT_2_HOUR,$slop).'>'.__('Upto 2 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_3_HOUR.'" '.bte_rt_optionselected(BTE_RT_3_HOUR,$slop).'>'.__('Upto 3 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_4_HOURS.'" '.bte_rt_optionselected(BTE_RT_4_HOURS,$slop).'>'.__('Upto 4 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_6_HOURS.'" '.bte_rt_optionselected(BTE_RT_6_HOURS,$slop).'>'.__('Upto 6 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_12_HOURS.'" '.bte_rt_optionselected(BTE_RT_12_HOURS,$slop).'>'.__('Upto 12 Hours', 'RelatedTweets').'</option>
									<option value="'.BTE_RT_24_HOURS.'" '.bte_rt_optionselected(BTE_RT_24_HOURS,$slop).'>'.__('Upto 24 Hours (1 day)', 'RelatedTweets').'</option>
									</select>
						</div>
							<ul id="category-tabs"> 
        						<li class="ui-tabs-selected"><a href="#categories-all" 
									tabindex="3">'.__('Categories to Omit from Tweeting: ', 'RelatedTweets').'</a></li> 
							</ul> 
						    	<div id="categories-all" class="ui-tabs-panel"> 
						    		<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
								');
	wp_category_checklist(0, 0, explode(',',$omitCats));
	print('				    		</ul>
								<div>
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update Related Tweets Options', 'RelatedTweets').'" />
					</p>
						<div class="option">
							<h4>Other Blog Traffic Exchange <a href="http://www.blogtrafficexchange.com/wordpress-plugins/">Wordpress Plugins</a></h4>
							<ul>
							<li><a href="http://www.blogtrafficexchange.com/related-websites/">Related Websites</a></li>
							<li><a href="http://www.blogtrafficexchange.com/related-tweets/">Related Tweets</a></li>
							<li><a href="http://www.blogtrafficexchange.com/wordpress-backup/">Wordpress Backup</a></li>
							<li><a href="http://www.blogtrafficexchange.com/blog-copyright/">Blog Copyright</a></li>
							<li><a href="http://www.blogtrafficexchange.com/old-post-promoter/">Old Post Promoter</a></li>
							<li><a href="http://www.blogtrafficexchange.com/related-posts/">Related Posts</a></li>
							</ul>
						</div>
				</form>' );

}

function bte_rt_optionselected($opValue, $value) {
	if($opValue==$value) {
		return 'selected="selected"';
	}
	return '';
}

function bte_rt_options_setup() {	
	add_options_page('RelatedTweets', 'Related Tweets', 10, basename(__FILE__), 'bte_rt_options');
}

?>