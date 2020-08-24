# Easy Social Login

This plugin is meant to integrate customized social login in a WordPress website. This plugin enables Google, Twitter login.

## Getting Started

Download the plugin. Install and activate it on your WordPress website. Then run `composer install` from command line inside your plugin directory to install vendors.

### Prerequisites

* Should have composer installed on your server.
* Knowledge of how to install packages via composer
* [Google](https://console.cloud.google.com) and [Twitter](https://developer.twitter.com/en/apps) apps on your developers account, and have keys available to use.
* Two WordPress pages with URL google-login.php and twitter-login.php


### Installing

What things you need to install the plugin and how to install them:

Go to this plugins folder, and run the following command from command line:

```
composer install
```

Then go to files google-login.php and twitter-login.php to add your client key and secret.

Once the composer has installed the required packages and you have updated the keys, you can use links `/google-login` and `/twitter-login` on anchor tag to take users for authentication and login to your site.

```
<a href="/google-login">Sign in with Google</a>
<a href="/twitter-login">Sign in with Twitter</a>
```

Plugin will login the existing users, while register and sign-in the new users.

## Authors

* **[Abhishek Jain](https://geekabhi.com)** - *Initial work*

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

