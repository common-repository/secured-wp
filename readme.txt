=== Secured WP ===
Contributors: wpsecuredcom
Tags: 2FA, 2 factor authentication, secure WP, secured WordPress, login redirect
Requires at least: 6.0
Tested up to: 6.5.5
Requires PHP: 8.0
Stable tag: 2.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add two-factor authentication (2FA) for all your users with this easy to use plugin. Harden your website login page. Add whole new layer of security.

== Description ==
Adds layer of security for your WordPress site. Adds custom login page slug, enables 2FA, removes security issues. Adds remember device, counts login attempts and lock usernames if the password is wrong. Out of band e-mail is also supported - instead of entering codes, your user can use simple login link from within their e-mail client.

<strong>Woocommerce</strong>

Woocommerce is also supported for 2FA, just enable the plugin and all your customers will be asked to enable two-factor authentication. 

List with currently supported features:

1. <strong>Login redirection</strong> - redirects the default wp-login.php to a slug of your choice
2. <strong>Login attempts</strong> - counts the unsuccessful attempts, and locks user if there are too many
3. <strong>2FA settings</strong> - gives the ability to use two factor authentication and Out Of Band email link
4. <strong>Remember devices</strong> - current device could be remembered for given amount of days and user wont be asked to login again before that
5. <strong>Removes XML-RPC</strong> from your WordPress site
6. Custom shortcode ([wps_custom_settings]) can be used to give the users without access to the dashboard to setup the 2FA

<strong>Login Redirection</strong>

You can change the default wp-login.php to slug of your choice. That will prevent most common hacker attacks and will harden your WordPress installation. You can redirect the original wp-login.php to the slug of your choice.

<strong>2FA login</strong>

Enable two-factor authentication for your WordPress site, and to enforce your website users, or some of them to use 2FA. Next time user logins s/he will be asked to enable the 2FA using their favorite application. Once the process is completed, every time the user logs, s/he will be asked to provide the 2FA code.

<strong>Login Attempts</strong>

This gives you the ability to prevent brute force attacks if the hacker knows the username and tries to guess the password. With this enabled, after the given amount of tries that specific user will be marked as locked, and any further attempt to use that username for login will be postponed for given amount of time.

<strong>Remember device setting</strong>

With that, user can use given device for the given amount of days without being asked to reenter the username/pass. The devices can be removed or checked from the default user settings page.
That setting is based on current setting (global) for the current moment, which means that when the day value (in settings) is changed globally, that wont reflect the already set cookies and user devices.
Example: If you set that to 10 days and there is a user which decide to use Remember Device functionality, when you change that value to 15 days, that wont increase the time for that user. Same applies for decreasing the value.

== Installation ==
Manual Installation

1. Download the "secured-wp.zip" file with the plugin to a location of your choice
2. Upload "secured-wp.zip" by going yo plugins -> Upload plugin and then select the plugin location from step one
3. Activate the plugin through the \"Plugins\" menu in WordPress.

Install from within WordPress

1. Go to Plugins -> Add new
2. Search for "Secured WP"
3. Install and activate the plugin through the "Plugins" menu in WordPress.


== Frequently Asked Questions ==
= Can I disable some of the modules =
Every single module can be enabled/disabled from its settings tab.

= Can I exclude some user =
Yes - go to users menu - select users by pressing the check box next to the username, and from the drop down menu select the action you want to perform and click Apply.

== Screenshots ==
1. Main screen of the plugin, settings tabs and information about the settings
2. 2FA login screen, user does not have enabled 2FA yet
3. E-mail with the Out of Band link
4. Woocommerce My Profile page
5. Extends default Users menu bulk actions
6. Extends default Users list by adding new filter drop-down from where you can easily filer users by user role
7. Adds new column to the standard Users menu - you can see the status of every user, and sort the Locked and Logged in users 

== Changelog ==
= 2.1.1 =
Small bug fixes with redirection

= 2.1.0 =
Removed all jQuery dependency when custom page (or post) with shortcode is used for user's settings manipulation. Fixed lots of bugs

= 2.0.3 =
* Missing class fix, uninstall script fix

= 2.0.2 =
* Added missing constants file

= 2.0.1 =
* Fixed bugs and problems, added blueprint.json

= 2.0.0 =
* Most of the plugin has been rewritten

= 1.0.0 =
* Initial release.