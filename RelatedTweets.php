<?php
/*
Plugin Name: Related Tweets (by BTE)
Plugin URI: http://www.blogtrafficexchange.com/related-tweets
Description: Randomly choose a post from the blog.  Search for related websites & posts via the Blog Traffic Exchange and tweet the most relevant related post.  Automatically adding hashtags <a href="options-general.php?page=BTE_RT_admin.php">Configuration options are here.</a>  
Version: 1.6.4
Author: Blog Traffic Exchange
Author URI: http://www.blogtrafficexchange.com/
Donate: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1777819
License: GNU GPL
*/
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
require_once('BTE_RT_admin.php');
require_once('BTE_RT_core.php');
require_once('BTE_RT_ge.php');
if (!class_exists('xmlrpcmsg')) {
	require_once('lib/xmlrpc.inc');
}		

define ('BTE_RT_XMLRPC_URI', 'bteservice.com'); 
define ('BTE_RT_XMLRPC', 'bte.php'); 

define ('BTE_RT_KEYWORDS', 'freq'); 
define ('BTE_RT_DEBUG', false); 
define ('BTE_RT_VERSION', '1.0'); 
define ('BTE_RT_1_MINUTE', 60); 
define ('BTE_RT_5_MINUTES', 5*BTE_RT_1_MINUTE); 
define ('BTE_RT_10_MINUTES', 10*BTE_RT_1_MINUTE); 
define ('BTE_RT_20_MINUTES', 20*BTE_RT_1_MINUTE); 
define ('BTE_RT_30_MINUTES', 30*BTE_RT_1_MINUTE); 
define ('BTE_RT_1_HOUR', 60*BTE_RT_1_MINUTE); 
define ('BTE_RT_2_HOURS', 2*BTE_RT_1_HOUR); 
define ('BTE_RT_3_HOURS', 3*BTE_RT_1_HOUR); 
define ('BTE_RT_4_HOURS', 4*BTE_RT_1_HOUR); 
define ('BTE_RT_6_HOURS', 6*BTE_RT_1_HOUR); 
define ('BTE_RT_12_HOURS', 12*BTE_RT_1_HOUR); 
define ('BTE_RT_24_HOURS', 24*BTE_RT_1_HOUR); 
define ('BTE_RT_INTERVAL', BTE_RT_1_HOUR); 
define ('BTE_RT_INTERVAL_SLOP', BTE_RT_5_MINUTES); 
define ('BTE_RT_OMIT_CATS', ""); 
define ('BTE_RT_API_POST_STATUS', 'http://twitter.com/statuses/update.json');

register_activation_hook(__FILE__, 'bte_rt_activate');
register_deactivation_hook(__FILE__, 'bte_rt_deactivate');
add_action('init', 'bte_rt_related_tweets');
add_action('admin_menu', 'bte_rt_options_setup');
add_action('admin_head', 'bte_rt_head_admin');
add_filter('plugin_action_links', 'bte_rt_plugin_action_links', 10, 2);

function bte_rt_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$settings_link = '<a href="options-general.php?page=BTE_RT_admin.php">'.__('Settings', 'RelatedTweets').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

function bte_rt_deactivate() {
	delete_option('bte_rt_last_update');
}

function bte_rt_activate() {
	add_option('bte_rt_befriend',false);
	add_option('bte_rt_interval',BTE_RT_INTERVAL);
	add_option('bte_rt_interval_slop',BTE_RT_INTERVAL_SLOP);
	add_option('bte_rt_omit_cats',BTE_RT_OMIT_CATS);
}
?>