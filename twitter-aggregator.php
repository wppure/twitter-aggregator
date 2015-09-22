<?php


// include twitter api php class
require_once( 'lib/twitter-api/TwitterAPIExchange.php' );



// settings for twitter api interaction
$settings = array(
    'consumer_key' => "UFBxe5cHwmGbDxHf3H9jDAGar",
    'consumer_secret' => "HSozmjgxMvNa74D8Sz5RL6Nav56uK0LKLvIvUu6FAgjNH7uClt",
    'oauth_access_token' => "29196496-q1Wllv60i94w1Wlpt6Ztzimfu5IvQOxOcxt8uwEN1",
    'oauth_access_token_secret' => "SziLDM5qOVAqGrPMvqTKEEWQ7Z4qgmA66aLJh1uOeOfVT",
    'usernames' => "jpederson",
    'limit' => "10"
);



// get twitter timelines and return them
function twitter_aggregator_get_timeline( $instance_settings ) {

	// include global settings
	global $settings;

	// merge instance settings with global settings, overriding global if passed here
	$all_settings = array_merge( $settings, $instance_settings );
	
	// split apart usernames
	$usernames = explode( ",", $all_settings['usernames'] );

	// empty array to place results into
	$response_final = array();

	// loop through usernames
	if ( !empty( $usernames ) ) {
		foreach ( $usernames as $username ) {

			// pull some statuses
			$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

			// put together an array of query string arguments
			$query_args = array(
				'screen_name' => trim( $username ),
				'skip_status' => 1,
				'exclude_replies' => 1,
				'count' => $all_settings['limit']
			);

			// build the query string
			$query = '?' . http_build_query( $query_args );

			// use the get method
			$method = 'GET';

			// open up a twitter API object for us
			$twitter = new TwitterAPIExchange( $all_settings );

			// execute response
			$response = $twitter->setGetfield( $query )->buildOauth( $url, $method )->performRequest();

			// convert the response from json to an array
			$response_array = json_decode( $response );

			// loop through response, and set up the array by date
			if ( isset( $response_array->errors ) ) {
				return array(
					'error' => 1,
					'error_message' => $response_array->errors[0]->message
				);
			} else {
				foreach ( $response_array as $result ) {
					$date = strtotime( $result->created_at );
					$response_final[ $date ] = $result;
				}
			}
		}
	} else {
		return array(
			'error' => 1,
			'error_message' => "No usernames specified."
		);
	}

	// sort response array in reverse order
	krsort( $response_final );

	// return the tweets
	return $response_final;
}



// output widget with settings parameter
function twitter_aggregator_widget( $instance_settings ) {

	// get the timelines
	$tweets = twitter_aggregator_get_timeline( $instance_settings );

	// output timeline
	if ( isset( $tweets['error'] ) ) {
		print $tweets['error_message'];
	} else {
		$tweet_count = 0;
		foreach ( $tweets as $tweet ) {
			if ( $tweet_count <= $instance_settings['limit'] ) {
			?>
		<div class="twitter-aggregator-tweet">
			<div class="twitter-aggregator-tweet-profile-pic"><a href="https://twitter.com/<?php print $tweet->user->screen_name ?>"><img src="<?php print $tweet->user->profile_image_url ?>"></a></div>
			<div class="twitter-aggregator-tweet-profile-name"><a href="https://twitter.com/<?php print $tweet->user->screen_name ?>"><?php print $tweet->user->name ?></a></div>
			<div class="twitter-aggregator-tweet-time"><?php print ago( $tweet->created_at ); ?> ago</div>
			<div class="twitter-aggregator-tweet-text"><?php print make_clickable( $tweet->text ); ?></div>
		</div>
			<?php
			}
			$tweet_count++;
		}		
	}

}



// time ago function
if ( !function_exists( 'ago' ) ) {
	function ago( $tm, $rcs = 0 ) {
		if ( is_string( $tm ) ) $tm = strtotime( $tm );

		$cur_tm = time();
		$dif = $cur_tm - $tm;
		$pds = array( 'second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade' );
		$lngh = array( 1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600 );
		for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);

		$no = floor( $no ); if( $no <> 1 ) $pds[$v] .='s';
		$x=sprintf("%d %s ",$no,$pds[$v]);
		if ( ( $rcs == 1 ) && ( $v >= 1 ) && ( ( $cur_tm - $_tm ) > 0 ) ) $x .= time_ago($_tm);
		return $x;
	}
}



?>