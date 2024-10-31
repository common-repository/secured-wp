<?php
/**
 * Advanced settings of the plugin
 *
 * @package wpsec
 *
 * @since 2.0.0
 */

use WPSEC\Controllers\Settings;
use WPSEC\Controllers\Modules\Login;
use WPSEC\Controllers\Modules\Login_Attempts;

Settings::build_option(
	array(
		'title' => esc_html__( 'Login Settings', 'secured-wp' ),
		'id'    => 'login-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => 'login-redirection',
		'title' => esc_html__( 'Login redirection', 'secured-wp' ),
	)
);

Settings::build_option(
	array(
		'text' => esc_html__( 'Hides the standard wp-login.php with your personal slug of choice. Redirects the original login to slug of your choice', 'secured-wp' ),
		'type' => 'message',
	)
);

Settings::build_option(
	array(
		'name'    => esc_html__( 'Enable', 'secured-wp' ),
		'id'      => Login::GLOBAL_SETTINGS_NAME,
		'toggle'  => '#login-redirection-items',
		'type'    => 'checkbox',
		'default' => false,
	)
);

echo '<div id="login-redirection-items">';

	Settings::build_option(
		array(
			'name'    => esc_html__( 'New Login redirection slug', 'secured-wp' ),
			'id'      => Login::NEW_LOGIN_SLUG_SETTINGS_NAME,
			'type'    => 'text',
			'default' => Login::NEW_LOGIN_SLUG_DEFAULT_VALUE,
			'hint'    => esc_html__( 'New login slug, must be URL friendly (once enabled the users should use that slug to login)', 'secured-wp' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Redirect original login to', 'secured-wp' ),
			'id'      => Login::OLD_LOGIN_SLUG_SETTINGS_NAME,
			'type'    => 'text',
			'default' => '404',
			'hint'    => esc_html__( 'If someone goes to the original WP login, where it should be redirected to', 'secured-wp' ),
		)
	);

	echo '</div><!-- #login-redirection-items -->';

	Settings::build_option(
		array(
			'type'  => 'header',
			'id'    => 'login-attempts',
			'title' => esc_html__( 'Login attempts', 'secured-wp' ),
		)
	);

	Settings::build_option(
		array(
			'text' => esc_html__( 'Counts incorrect login attempts and locks the user - use standard user menu to check locked users.', 'secured-wp' ),
			'type' => 'message',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Enable', 'secured-wp' ),
			'id'      => Login_Attempts::GLOBAL_SETTINGS_NAME,
			'toggle'  => '#login-attempts-items',
			'type'    => 'checkbox',
			'default' => false,
		)
	);

	echo '<div id="login-attempts-items">';

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Login Attempts', 'secured-wp' ),
			'id'      => 'login_attempts',
			'type'    => 'number',
			'default' => '5',
			'min'     => 1,
			'max'     => 15,
			'hint'    => esc_html__( 'Number of login attempts before locking the user out', 'secured-wp' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Lock time', 'secured-wp' ),
			'id'      => Login_Attempts::LOGIN_LOCK_SETTINGS_NAME,
			'type'    => 'number',
			'default' => '15',
			'min'     => 1,
			'max'     => 180,
			'hint'    => esc_html__( 'If locked, how many minutes before give the user ability to login again', 'secured-wp' ),
		)
	);

	echo '</div><!-- #login-attempts-items -->';
