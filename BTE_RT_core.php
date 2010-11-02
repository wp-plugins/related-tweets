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
require_once('BTE_RT_ge.php');
require_once('RelatedTweets.php');

function bte_rt_related_tweets () {
	if (bte_rt_update_time()) {
		update_option('bte_rt_last_update', time());
		bte_rt_tweet_related();
	}
}

function bte_rt_tweet_related () {
	global $wpdb;
	$omitCats = get_option('bte_rt_omit_cats');
	if (!isset($omitCats)) {
		$omitCats = BTE_RT_OMIT_CATS;
	}
	$sql = "SELECT ID
            FROM $wpdb->posts
            WHERE post_type = 'post'
                  AND post_status = 'publish'
                  ";
    if ($omitCats!='') {
    	$sql = $sql."AND NOT(ID IN (SELECT tr.object_id 
                                    FROM $wpdb->terms  t 
                                          inner join $wpdb->term_taxonomy tax on t.term_id=tax.term_id and tax.taxonomy='category' 
                                          inner join $wpdb->term_relationships tr on tr.term_taxonomy_id=tax.term_taxonomy_id 
                                    WHERE t.term_id IN (".$omitCats.")))";
    }            
    $sql = $sql."            
            ORDER BY RAND() 
            LIMIT 1 ";
	$the_post = $wpdb->get_var($sql);   
	if (isset($the_post)) {
		bte_rt_tweet_related_post($the_post);
	}
}

function bte_rt_extract_keywords($content,$num_to_ret = 25) {
	$stopwords = array( '', 'a', 'an', 'the', 'and', 'of', 'i', 'to', 'is', 'in', 'with', 'for', 'as', 'that', 'on', 'at', 'this', 'my', 'was', 'our', 'it', 'you', 'we', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '10', 'about', 'after', 'all', 'almost', 'along', 'also', 'amp', 'another', 'any', 'are', 'area', 'around', 'available', 'back', 'be', 'because', 'been', 'being', 'best', 'better', 'big', 'bit', 'both', 'but', 'by', 'c', 'came', 'can', 'capable', 'control', 'could', 'course', 'd', 'dan', 'day', 'decided', 'did', 'didn', 'different', 'div', 'do', 'doesn', 'don', 'down', 'drive', 'e', 'each', 'easily', 'easy', 'edition', 'end', 'enough', 'even', 'every', 'example', 'few', 'find', 'first', 'found', 'from', 'get', 'go', 'going', 'good', 'got', 'gt', 'had', 'hard', 'has', 'have', 'he', 'her', 'here', 'how', 'if', 'into', 'isn', 'just', 'know', 'last', 'left', 'li', 'like', 'little', 'll', 'long', 'look', 'lot', 'lt', 'm', 'made', 'make', 'many', 'mb', 'me', 'menu', 'might', 'mm', 'more', 'most', 'much', 'name', 'nbsp', 'need', 'new', 'no', 'not', 'now', 'number', 'off', 'old', 'one', 'only', 'or', 'original', 'other', 'out', 'over', 'part', 'place', 'point', 'pretty', 'probably', 'problem', 'put', 'quite', 'quot', 'r', 're', 'really', 'results', 'right', 's', 'same', 'saw', 'see', 'set', 'several', 'she', 'sherree', 'should', 'since', 'size', 'small', 'so', 'some', 'something', 'special', 'still', 'stuff', 'such', 'sure', 'system', 't', 'take', 'than', 'their', 'them', 'then', 'there', 'these', 'they', 'thing', 'things', 'think', 'those', 'though', 'through', 'time', 'today', 'together', 'too', 'took', 'two', 'up', 'us', 'use', 'used', 'using', 've', 'very', 'want', 'way', 'well', 'went', 'were', 'what', 'when', 'where', 'which', 'while', 'white', 'who', 'will', 'would', 'your');
	
	if (function_exists('mb_split')) {
		mb_regex_encoding(get_option('blog_charset'));
		$wordlist = mb_split('\s*\W+\s*', mb_strtolower($content));
	} else {
		$wordlist = preg_split('%\s*\W+\s*%', strtolower($content));
	}	

	// Build an array of the unique words and number of times they occur.
	$a = array_count_values($wordlist);
	
	// Remove the stop words from the list.
	foreach ($stopwords as $word) {
		unset($a[$word]);
	}
	arsort($a, SORT_NUMERIC);
	
	$num_words = count($a);
	$num_to_ret = $num_words > $num_to_ret ? $num_to_ret : $num_words;
	
	$outwords = array_slice($a, 0, $num_to_ret);
	return implode(',', array_keys($outwords));
}

function bte_rt_get_tags($postMod, $ID, $guid, $title, $content, $cats, $tags) {
	$content_time = get_post_meta($ID,'_bte_last_content_update',true);
	if ($content_time>$postMod) {
		return get_post_meta($ID,'_bte_content',true);
	}
	
	global $bte_rt_encoder;
	if ($bte_rt_encoder==null)	{
		$bte_rt_encoder = new BTE_RT_GE;
	}
	$content = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $content );
	$content = preg_replace('/<iframe [^>]*>(.*?)<\/iframe>/s',' ',$content,1);
	$content = preg_replace('/<object [^>]*>(.*?)<\/object>/s',' ',$content,1);
	$content = strip_tags($title.' . '.$content.' . '.$cats.' . '.$tags);
	if ('utf8'!=DB_CHARSET) {
		$content = utf8_encode($content);
	}
	
	$tags=bte_rt_extract_keywords($content);

	if (BTE_RT_DEBUG) {
		error_log("[".date('Y-m-d H:i:s')."][bte_rwplugin.bte_rw_get_tags] tags: ".$tags);
	}	
	update_post_meta($ID,'_bte_content',$bte_rt_encoder->Encode($tags,$guid)) or add_post_meta($ID, '_bte_content', $bte_rw_encoder->Encode($tags,$guid));				
	update_post_meta($ID,'_bte_last_content_update',time()) or add_post_meta($ID, '_bte_last_content_update', time());
	return $bte_rt_encoder->Encode($tags,$guid);	
}


function bte_rt_tweet_related_post($post) {
	$consumer_key = get_option('bte_rt_twitter_consumer_key');
	$consumer_secret = get_option('bte_rt_twitter_consumer_secret');
	$oauth_token = get_option('bte_rt_twitter_oauth_token');
	$oauth_secret = get_option('bte_rt_twitter_oauth_secret');
	
	if (empty($consumer_key) 
		|| empty($consumer_secret) 
		|| empty($oauth_token) 
		|| empty($oauth_secret) 
	) {
		return 0;
	}
	
	global $wpdb;
	$post = get_post($post);
	$givecredit = get_option('bte_rt_give_credit');
	if (!isset($givecredit)) {
		$givecredit = 1;
	}
	if ($givecredit && rand()%999==0) {//~.01% of the time 
		$tweet = "Related Tweets http://bte.tc/b8E supplements this stream with relevant tweets";
		bte_rt_tweet($tweet);
	} else if (rand()%7==0) {//~14% of the time 
		$tags = bte_rt_get_tags($postMod,$post->ID,$post->guid,$post->post_title,$post->post_content,explode(',',get_the_category()),explode(',',get_the_tags()));
		global $bte_rt_encoder;
		if ($bte_rt_encoder==null)	{
			$bte_rt_encoder = new BTE_RT_GE;
		}
		$tags = $bte_rt_encoder->Decode($tags,$post->guid);
		$the_tags = explode(",",$tags);
		ini_set('user_agent', 'Related Tweets: http://www.blogtrafficexchange.com/related-tweets/');
		$retweets = json_decode(file_get_contents("http://search.twitter.com/search.json?q=from:$username+RT&rpp=100"));
		array_splice($the_tags, 6);
		shuffle($the_tags);
		array_splice($the_tags, 3);
		if (!bte_rt_tweet_most_popular_twit($user,$pass,implode("+",$the_tags),$retweets)) {
			array_splice($the_tags, 2);
			if (!bte_rt_tweet_most_popular_twit($user,$pass,implode("+",$the_tags),$retweets)) {
				array_splice($the_tags, 1);
				bte_rt_tweet_most_popular_twit($user,$pass,implode("+",$the_tags),$retweets);				
			}
		}
	} else {
		$wppost = array();
		$wppost["site"] = get_option('siteurl');
		$wppost["guid"] = $post->guid;
		$wppost["tags"] = bte_rt_get_tags($postMod,$post->ID,$post->guid,$post->post_title,$post->post_content,explode(',',get_the_category()),explode(',',get_the_tags()));
		$f=new xmlrpcmsg('bte.relatedtweet',
			array(php_xmlrpc_encode($wppost))
		);
		$c=new xmlrpc_client(BTE_RT_XMLRPC, BTE_RT_XMLRPC_URI, 80);
		if (BTE_RT_DEBUG) {
			$c->setDebug(1);
		}
		$r=&$c->send($f);
		if(!$r->faultCode()) {
			$sno=$r->value();
			if ($sno->kindOf()!="array") {
				$err="Found non-array as parameter 0";
			} else {
				for($i=0; $i<$sno->arraysize(); $i++)
				{
					$rec=$sno->arraymem($i);
					$tweet = $rec->structmem("tweet");
					if ($tweet!=null) {
						bte_rt_tweet($tweet->scalarval());
					}	
				}		
			}
		} else if (BTE_RT_DEBUG) {
			error_log("[".date('Y-m-d H:i:s')."][bte_rtplugin.updateContent] ".$post->guid." error code: ".htmlspecialchars($r->faultCode()));
			error_log("[".date('Y-m-d H:i:s')."][bte_rtplugin.updateContent] ".$post->guid." reason: ".htmlspecialchars($r->faultString()));
			echo("[".date('Y-m-d H:i:s')."][bte_rtplugin.updateContent] ".$post->guid." error code: ".htmlspecialchars($r->faultCode()));
			echo("[".date('Y-m-d H:i:s')."][bte_rtplugin.updateContent] ".$post->guid." reason: ".htmlspecialchars($r->faultString()));
		}
	}
}


function bte_rt_tweet($tweet) {
	$consumer_key = get_option('bte_rt_twitter_consumer_key');
	$consumer_secret = get_option('bte_rt_twitter_consumer_secret');
	$oauth_token = get_option('bte_rt_twitter_oauth_token');
	$oauth_secret = get_option('bte_rt_twitter_oauth_secret');
	
	if (empty($consumer_key) 
		|| empty($consumer_secret) 
		|| empty($oauth_token) 
		|| empty($oauth_secret) 
		|| empty($tweet)
	) {
		return;
	}
	
	require_once('twitteroauth.php');
	$connection = new TwitterOAuth(
			$consumer_key, 
			$consumer_secret, 
			$oauth_token, 
			$oauth_secret
		);
	$connection->useragent = 'Related Tweets http://www.blogtrafficexchange.com/related-tweets/';
	
	$connection->post(
		BTE_RT_API_POST_STATUS
		, array(
			'status' => $tweet
			, 'source' => 'Related Tweets'
		)
	);
	
	if (strcmp($connection->http_code, '200') == 0) {
		return true;
	}
	return false;
}


function bte_rt_update_time () {
	$consumer_key = get_option('bte_rt_twitter_consumer_key');
	$consumer_secret = get_option('bte_rt_twitter_consumer_secret');
	$oauth_token = get_option('bte_rt_twitter_oauth_token');
	$oauth_secret = get_option('bte_rt_twitter_oauth_secret');
	
	if (empty($consumer_key) 
		|| empty($consumer_secret) 
		|| empty($oauth_token) 
		|| empty($oauth_secret) 
	) {
		return 0;
	}
	
	if (BTE_RT_DEBUG) {
		echo '<h1>Update Time</h1>';
		return 1;
	}
	
	$last = get_option('bte_rt_last_update');		
	$interval = get_option('bte_rt_interval');		
	if (!(isset($interval) && is_numeric($interval))) {
		$interval = BTE_RT_INTERVAL;
	}
	$slop = get_option('bte_rt_interval_slop');		
	if (!(isset($slop) && is_numeric($slop))) {
		$slop = BTE_RT_INTERVAL_SLOP;
	}
	if (false === $last) {
		$ret = 1;
	} else if (is_numeric($last)) { 
		$ret = ( (time() - $last) > ($interval+rand(0,$slop)));
	}
	return $ret;
}

function bte_rt_issafe($tweet) {
	$pattern = '/(porn|dick|cock|nipple|boobs|tits|sex|shit|fuck|cunt)/';
	return !preg_match($pattern,$tweet);
}

function bte_rt_tweet_most_popular_twit($username, $password, $topic, $retweets, $resultcount = 40) {
	//$tweets = bte_rt_tweet_details($username,$password,"$topic+-RT+-porn+-dick+-cock+-nipple+-boobs+-tits+-sex+-shit+-fuck+-cunt", $resultcount);
	$tweets = bte_rt_tweet_details($username,$password,"$topic+-RT", $resultcount);
	$count = 0;
	$new_retweet = null;
	// Find the tweet from the most popular twit that has not already been retweeted
	foreach($tweets->results as $index => $tweet) {
		if(
			$tweet->user_data->friends_count > $count											// Check to see if we have a new max
			&& !preg_match("/@[^\s]*/", $tweet->text)											// Make sure we aren't tweeting  			
			&& !bte_rt_is_retweeted($tweet, $retweets)
			&& bte_rt_issafe($tweet->text)
			// Make sure it hasn't been retweeted already
		) {
			// Set the new retweet and friends count
			$new_retweet = $tweet;
			$count = (int)$tweet->user_data->friends_count;
		}
		if (get_option('bte_rt_befriend') && $tweet->user_data->followers_count>200 && $tweet->user_data->friends_count>200 && $tweet->user_data->friends_count / $tweet->user_data->followers_count > 0.9) {
			bte_rt_befriend($username, $password, $tweet->from_user);
		}
	}
	if (isset($new_retweet)) {
		bte_rt_tweet(bte_rt_get_retweet($username,$password, $new_retweet));
		return true;
	}
	return false;
}

function bte_rt_is_retweeted($tweet, $retweets) {
	foreach($retweets as $retweet) {
		if(bte_rt_get_retweet("","",$tweet) == bte_rt_get_retweet("","",$retweet))
			return true;
	}
	return false;
}

function bte_rt_get_retweet($username, $password, $tweet) {
	if(!$tweet->from_user && !$tweet->text)
		return;
	
	$message = "RT @" . $tweet->from_user . ": ";
	if(preg_match("/http:\/\/[^\s$]*/", $tweet->text, $url)) {
		$url = $url[0];
		$tweet = preg_replace("/http:\/\/[^\s$]*/", "", $tweet->text);
		$message .= $tweet;
		$message = substr($message, 0, 137-strlen($url));
		if(strlen($message) == 137-strlen($url))
			$message .= "..";
		$message .= " $url";
	}
	else {
		$message .= $tweet->text;
		$message = substr($message, 0, 140);
	}
	
	// Remove extra spaces
	$message = preg_replace("/\s{2,}/", " ", $message);
	
	return $message;
}

function bte_rt_tweet_details($username, $password, $topic, $tweet_count = 100) {
	$count = 0;
	$mh = curl_multi_init();
	$topic = urlencode(preg_replace("/\s/", "+", $topic));
	ini_set('user_agent', 'Related Tweets: http://www.blogtrafficexchange.com/related-tweets/');
	$tweets = json_decode(file_get_contents("http://search.twitter.com/search.json?q=$topic+-".urlencode($username)."&rpp=$tweet_count"));
	if (isset($tweets)) {
		foreach($tweets->results as $index => $tweet) {
			$tweets->results[$index]->user_url = "http://twitter.com/users/show/" . $tweet->from_user;
			$tweets->results[$index]->ch = curl_init();
			curl_setopt($tweets->results[$index]->ch, CURLOPT_URL, $tweets->results[$index]->user_url);
			curl_setopt($tweets->results[$index]->ch, CURLOPT_RETURNTRANSFER, 1);
			curl_multi_add_handle($mh, $tweets->results[$index]->ch);
		}
		$running=null;
		//execute the handles
		do {
			curl_multi_exec($mh,$running);
		} while($running > 0);
		
		foreach($tweets->results as $index => $tweet) {
			$tweets->results[$index]->user_data = @simplexml_load_string(curl_multi_getcontent($tweets->results[$index]->ch));
		}
	}
	return $tweets;
}

function bte_rt_befriend($username, $password, $newfriend) {
	/* Disabled until can be fixed to work with oauth
	$twitter_update = "http://twitter.com/friendships/create.xml?screen_name=";
	$twitter_message = $twitter_update . urlencode($newfriend);
	$curl_twitter = curl_init();
	ini_set('user_agent', 'Related Tweets: http://www.blogtrafficexchange.com/related-tweets/');
	curl_setopt($curl_twitter, CURLOPT_URL, $twitter_message);
	curl_setopt($curl_twitter, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl_twitter, CURLOPT_HEADER, false);
	curl_setopt($curl_twitter, CURLOPT_USERPWD, "$username:$password");
	curl_setopt($curl_twitter, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_twitter, CURLOPT_POST, true);
	$curl_result = curl_exec($curl_twitter);
	curl_close($curl_twitter);*/
}

?>