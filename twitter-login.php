<?php

require 'vendor/autoload.php';

session_start();

use Abraham\TwitterOAuth\TwitterOAuth;

define('TWITTER_CONSUMER_KEY', 'CONSUMER-KEY');
define('TWITTER_CONSUMER_SECRET', 'CONSUMER-SECRET');
define('OAUTH_CALLBACK', get_site_url() . '/twitter-login');

$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);

/* If the oauth_token is old redirect to the connect page. */
if (!empty($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] == $_REQUEST['oauth_token']) {

    /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

    /* Request access tokens from twitter */
    $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $_REQUEST['oauth_verifier']]);

    /* Save the access tokens. Normally these would be saved in a database for future use. */
    $_SESSION['access_token'] = $access_token;

    $oauth_token = $access_token['oauth_token'];
    $oauth_token_secret = $access_token['oauth_token_secret'];
    $screen_name = $access_token['screen_name'];
    $user_id = $access_token['user_id'];

    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

    $profile = $connection->get('account/verify_credentials', array('user_id' => $user_id, "include_email" => 'true'));

    $esl_name_arr = explode(' ', $profile->name);
    $esl_firstname = addslashes($esl_name_arr[0]);
    if (!empty($esl_name_arr[1])) {
	$esl_lastname = addslashes($esl_name_arr[1]);
    } else {
	$esl_lastname = "";
    }
    $esl_name = addslashes(sanitize_text_field($profile->name));
    $esl_email = sanitize_email($profile->email);
    $esl_twitterID = $profile->id;

    if (!is_user_logged_in()) {
	$esl_user = get_users(array('meta_key' => 'twitter_social_id', 'meta_value' => $esl_twitterID, 'number' => 1, 'count_total' => false));
	if (!empty($esl_user)) {
	    wp_clear_auth_cookie();
	    wp_set_current_user($esl_user[0]->ID);
	    wp_set_auth_cookie($esl_user[0]->ID);

	    header('Location: /');
	    exit;
	} else if (!empty($esl_email)) {
	    $esl_user = get_user_by('email', $esl_email);
	    if (!empty($esl_user)) {
		wp_clear_auth_cookie();
		wp_set_current_user($esl_user->ID);
		wp_set_auth_cookie($esl_user->ID);

		header('Location: /');
		exit;
	    }

	    $esl_username = strtolower($esl_firstname . '-' . $esl_lastname) . '-' . substr($esl_twitterID, 0, 3);

	    $esl_userdata = array(
		'user_pass' => $esl_twitterID, //(string) The plain-text user password.
		'user_login' => $esl_username, //(string) The user's login username.
		'user_nicename' => $esl_username, //(string) The URL-friendly user name.
		'user_email' => $esl_email, //(string) The user email address.
		'display_name' => $esl_name, //(string) The user's display name. Default is the user's username.
		'nickname' => $esl_firstname, //(string) The user's nickname. Default is the user's username.
		'first_name' => $esl_firstname, //(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
		'last_name' => $esl_lastname, //(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
		'user_registered' => date('Y-m-d H:i:s'), //(string) Date the user registered. Format is 'Y-m-d H:i:s'.
		'show_admin_bar_front' => "false", //(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
	    );

	    $esl_user_id = wp_insert_user($esl_userdata);
	    if (!empty($esl_user_id) && $esl_user_id > 0) {
		add_user_meta($esl_user_id, 'twitter_social_id', $esl_twitterID, true);

		wp_clear_auth_cookie();
		wp_set_current_user($esl_user_id);
		wp_set_auth_cookie($esl_user_id);

		header('Location: /');
		exit;
	    } else {
		header('Location: /register');
		exit;
	    }
	} else {
	    header('Location: /register');
	    exit;
	}
    } else {
	header('Location: /');
    }
} else {

    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));

    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
    header("Location: " . $url);
}