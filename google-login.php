<html lang="en">
    <head>
	<meta name="google-signin-scope" content="profile email">
	<meta name="google-signin-client_id" content="113746533551-vfb1hkj4bm29k7kqrqlk6h4ekqg61atu.apps.googleusercontent.com">
	<script src="https://apis.google.com/js/platform.js" async defer></script>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    </head>
    <body>
	<?php
	// Localize the script with new data
	$translation_array = array(
	    'ajaxurl' => admin_url('admin-ajax.php'),
	    'nonce' => wp_create_nonce('esl-nonce')
	);

	if ($esl_login_option == "js") {
	    ?>
	    <div class="g-signin2" data-onsuccess="onSignIn"></div>

	    <script>
		function onSignIn(googleUser) {
		    var profile = googleUser.getBasicProfile();

		    jQuery.ajax({
			url: '<?php echo $translation_array['ajaxurl']; ?>',
			type: 'post',
			data: {
			    action: 'google_login_data',
			    nonce: '<?php echo $translation_array['nonce']; ?>',
			    profileData: {
				id: profile.getId(),
				'first_name': profile.getGivenName(),
				'last_name': profile.getFamilyName(),
				'full_name': profile.getName(),
				'email': profile.getEmail(),
				'image_url': profile.getImageUrl()
			    }
			},
			dataType: 'json',
			success: function (response) {
			    if (response.success == '1') {
				window.location = '/';
			    }
			}
		    });
		}
	    </script>
	    <?php
	} else {
	    require_once 'vendor/autoload.php';

	    // init configuration
	    $clientID = 'CLIENT-ID.apps.googleusercontent.com';
	    $clientSecret = 'CLIENT-SECRET';
	    $redirectUri = get_site_url() . '/google-login';

	    // create Client Request to access Google API
	    $client = new Google_Client();
	    $client->setClientId($clientID);
	    $client->setClientSecret($clientSecret);
	    $client->setRedirectUri($redirectUri);
	    $client->addScope("email");
	    $client->addScope("profile");

	    // authenticate code from Google OAuth Flow
	    if (isset($_GET['code'])) {
		$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
		$client->setAccessToken($token['access_token']);

		// get profile info
		$google_oauth = new Google_Service_Oauth2($client);
		$google_account_info = $google_oauth->userinfo->get();
		$esl_email = $google_account_info->email;
		$esl_name = $google_account_info->name;
		$esl_firstname = $google_account_info->first_name;
		$esl_lastname = $google_account_info->last_name;
		$esl_googleID = $google_account_info->id;

		// now you can use this profile info to create account in your website and make user logged in.
		if (!is_user_logged_in()) {
		    $esl_user = get_users(array('meta_key' => 'google_social_id', 'meta_value' => $esl_googleID, 'number' => 1, 'count_total' => false));
		    if (!empty($esl_user)) {
			wp_clear_auth_cookie();
			wp_set_current_user($esl_user[0]->ID);
			wp_set_auth_cookie($esl_user[0]->ID);

			//$redirect_to = user_admin_url();
			//wp_safe_redirect($redirect_to);
			//echo json_encode(["success" => 1, "message" => "login Successful!!!!"]);
			//exit();
			header('Location: /');
		    }
		    if (!empty($esl_email)) {
			$esl_user = get_user_by('email', $esl_email);
			if (!empty($esl_user)) {
			    wp_clear_auth_cookie();
			    wp_set_current_user($esl_user->ID);
			    wp_set_auth_cookie($esl_user->ID);

			    //$redirect_to = user_admin_url();
			    //wp_safe_redirect($redirect_to);
			    //echo json_encode(["success" => 1, "message" => "login Successful!!!!"]);
			    //exit();
			    header('Location: /');
			    exit;
			} else {

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
				'show_admin_bar_front' => "false", //(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
			    );

			    $esl_user_id = wp_insert_user($esl_userdata);
			    if (!empty($esl_user_id) && $esl_user_id > 0) {
				add_user_meta($esl_user_id, 'google_social_id', $esl_googleID, true);

				wp_clear_auth_cookie();
				wp_set_current_user($esl_user_id);
				wp_set_auth_cookie($esl_user_id);

				//$redirect_to = user_admin_url();
				//wp_safe_redirect($redirect_to);
				header('Location: /');
				exit;
			    } else {
				header('Location: /register');
				exit;
			    }
			}
		    } else {
			header('Location: /register');
			exit;
		    }
		}
	    } else {
		header('Location: ' . $client->createAuthUrl());
		exit;
	    }
	}
	?>
    </body>
</html>