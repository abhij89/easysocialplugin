<?php

/**
 * Plugin Name: Easy Social Login
 * Plugin URI: https://github.com/abhij89/easysociallogin
 * Description: Easy Social Login for your website. No remembering of password. One click login.
 * Version: 1.0.0
 * Author: abhij89
 * Author URI: https://geekabhi.com
 * Text Domain: easy-social-login
 *
 *
 * @package   Easy Social Login
 * @since     1.0.0
 * @version   1.0.0
 * @author    abhij89 <abhij89@gmail.com>
 * @link      https://geekabhi.com
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $esl_version;
$esl_version = '1.0.0';

/**
 * Class ESL
 *
 * Handles the loading of plugin and other core functionality
 */
class ESL {

    /**
     * Performs plugin activation tasks.
     *
     * @since  1.0.0
     * @access public
     */
    public static function esl_options_install() {
	global $wpdb;
	global $esl_version;

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	add_option('esl_version', $esl_version);
    }

    /**
     * Performs Trigger Wizards update check.
     *
     * @since  1.0.0
     * @access public
     */
    public static function esl_update_check() {
	global $esl_version;
	global $wpdb;

	if (get_site_option('esl_version') != $esl_version) {
	    $wpdb->show_errors();
	    //Check for Exclude Pages
	    self::esl_options_install();
	}
	update_option('esl_version', $esl_version);
    }

    /*
     * Enqueue style-file, if it exists.
     */

    public static function esl_enqueue_assets($hook) {
	// if any
    }

    /*
     * This function loads plugins translation files
     */

    public static function esl_load_translation_files() {
	load_plugin_textdomain('easy-social-login', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public static function esl_links() {
	// This submenu is HIDDEN, however, we need to add it anyways
	add_submenu_page(
		null, __('Google Login', 'easy-social-login'), __('Google Login', 'easy-social-login'), 'manage_options', 'esl-google-login', array('ESL', 'esl_google_login')
	);
    }

    public static function esl_google_login() {
	echo "hello";
    }

}

register_activation_hook(__FILE__, array('ESL', 'esl_options_install'));

add_action('plugins_loaded', array('ESL', 'esl_update_check'));
add_action('plugins_loaded', array('ESL', 'esl_load_translation_files'));
add_action('admin_enqueue_scripts', array('ESL', 'esl_enqueue_assets'));
add_action('admin_menu', array('ESL', 'esl_links'));

add_filter('template_include', 'google_login_page', 99);

function google_login_page($template) {

    if (is_page('google-login')) {
	include plugin_dir_path(__FILE__) . 'google-login.php';
	return '';
    }

    return $template;
}

add_filter('template_include', 'twitter_login_page', 99);

function twitter_login_page($template) {

    if (is_page('twitter-login')) {
	include plugin_dir_path(__FILE__) . 'twitter-login.php';
	return '';
    }

    return $template;
}

//add_action('wp_ajax_google_login_data', 'google_login_data');
add_action('wp_ajax_nopriv_google_login_data', 'google_login_data');

function google_login_data() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
	/* it's an AJAX call */
	$esl_nonce = $_REQUEST['nonce'];
	if (!wp_verify_nonce($esl_nonce, 'esl-nonce')) {
	    echo json_encode(["success" => 0, "message" => "Something went wrong!!!!"]);
	    exit;
	}

	$esl_firstname = sanitize_text_field($_POST['profileData']['first_name']);
	$esl_lastname = sanitize_text_field($_POST['profileData']['last_name']);
	$esl_name = sanitize_text_field($_POST['profileData']['full_name']);
	$esl_email = sanitize_email($_POST['profileData']['email']);
	$esl_imageurl = sanitize_url($_POST['profileData']['image_url']);
	$esl_googleID = sanitize_text_field($_POST['profileData']['id']);

	if (!is_user_logged_in()) {
	    $esl_user = get_users(array('meta_key' => 'google_social_id', 'meta_value' => $esl_googleID, 'number' => 1, 'count_total' => false));
	    if (!empty($esl_user)) {
		wp_clear_auth_cookie();
		wp_set_current_user($esl_user[0]->ID);
		wp_set_auth_cookie($esl_user[0]->ID);

		//$redirect_to = user_admin_url();
		//wp_safe_redirect($redirect_to);
		echo json_encode(["success" => 1, "message" => "login Successful!!!!"]);
		exit();
	    }
	    $esl_user = get_user_by('email', $esl_email);
	    if (!empty($esl_user)) {
		wp_clear_auth_cookie();
		wp_set_current_user($esl_user->ID);
		wp_set_auth_cookie($esl_user->ID);

		//$redirect_to = user_admin_url();
		//wp_safe_redirect($redirect_to);
		echo json_encode(["success" => 1, "message" => "login Successful!!"]);
		exit();
	    }

	    $esl_username = strtolower($esl_firstname . '-' . $esl_lastname) . '-' . substr($esl_googleID, 0, 3);

	    $esl_userdata = array(
		'user_pass' => $esl_googleID, //(string) The plain-text user password.
		'user_login' => $esl_username, //(string) The user's login username.
		'user_nicename' => $esl_username, //(string) The URL-friendly user name.
		'user_email' => $esl_email, //(string) The user email address.
		'display_name' => $esl_name, //(string) The user's display name. Default is the user's username.
		'nickname' => $esl_firstname, //(string) The user's nickname. Default is the user's username.
		'first_name' => $esl_firstname, //(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
		'last_name' => $esl_lastname, //(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
		'user_registered' => date('Y-m-d H:i:s'), //(string) Date the user registered. Format is 'Y-m-d H:i:s'.
		'show_admin_bar_front' => false, //(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
	    );

	    $esl_user_id = wp_insert_user($esl_userdata);

	    add_user_meta($esl_user_id, 'google_social_id', $esl_googleID, true);

	    wp_clear_auth_cookie();
	    wp_set_current_user($esl_user_id);
	    wp_set_auth_cookie($esl_user_id);

	    //$redirect_to = user_admin_url();
	    //wp_safe_redirect($redirect_to);
	    echo json_encode(["success" => 1, "message" => "login Successful!!!"]);
	    exit();
	}
    }
    exit;
}

// Function to implement shortcode
function esl_shortcode($atts) {
    global $wpdb;
    if (!empty($atts['type'])) {
	$esl_type = $atts['type'];
	$esl_login_option = $atts['mode'];
	if ($esl_type == "google") {
	    ob_start();
	    include('google-login.php');
	    return ob_get_clean();
	} else if ($esl_type == "twitter") {
	    ob_start();
	    include('twitter-login.php');
	    return ob_get_clean();
	}
    }
}

add_shortcode('esl', 'esl_shortcode');
