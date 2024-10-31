<?php
/**
 * Advanced settings of the plugin
 *
 * @package wpsec
 *
 * @since 2.0.0
 */

use WPSEC\Controllers\Settings;
use WPSEC\Controllers\Modules\Two_FA_Settings;

Settings::build_option(
	array(
		'title' => esc_html__( '2FA Settings', 'secured-wp' ),
		'id'    => '2fa-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'text' => esc_html__( 'Enables 2FA. Next time, when user logins s/he will be asked to add the site to Authenticator application of their choice, and from now on they must provide code from that App in order to login.', 'secured-wp' ),
		'type' => 'message',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => '2fa-settings',
		'title' => esc_html__( '2FA related settings', 'secured-wp' ),
	)
);

Settings::build_option(
	array(
		'name'    => esc_html__( 'Enable', 'secured-wp' ),
		'id'      => Two_FA_Settings::GLOBAL_SETTINGS_NAME,
		'toggle'  => '#2fa-menu-items',
		'type'    => 'checkbox',
		'default' => true,
	)
);

echo '<div id="2fa-menu-items">';

	Settings::build_option(
		array(
			'name'    => esc_html__( 'TOTP', 'secured-wp' ),
			'id'      => Two_FA_Settings::TOTP_SETTINGS_NAME,
			'type'    => 'checkbox',
			'default' => true,
			'hint'    => esc_html__( 'Login using Authenticator application (all authenticators are supported)', 'secured-wp' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Out of band e-mail', 'secured-wp' ),
			'id'      => Two_FA_Settings::OOB_SETTINGS_NAME,
			'type'    => 'checkbox',
			'default' => true,
			'hint'    => esc_html__( 'Send one time login links over e-mail', 'secured-wp' ),
		)
	);

	echo '</div><!-- #2fa-menu-items -->';
