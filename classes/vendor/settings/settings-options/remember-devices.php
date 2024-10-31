<?php
/**
 * Advanced settings of the plugin
 *
 * @package wpsec
 *
 * @since 2.0.0
 */

use WPSEC\Controllers\Settings;
use WPSEC\Controllers\Modules\Remember_Me;

Settings::build_option(
	array(
		'title' => esc_html__( 'Remember Device Settings', 'secured-wp' ),
		'id'    => 'remember-devices-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'text' => esc_html__( 'When enabled, if user checks remember me on login, the device will be stored and the user wont be asked for credentials for the given amount of time.', 'secured-wp' ),
		'type' => 'message',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => 'remember-devices-settings',
		'title' => esc_html__( 'Remember devices related settings', 'secured-wp' ),
	)
);

Settings::build_option(
	array(
		'name'    => esc_html__( 'Enable', 'secured-wp' ),
		'id'      => Remember_Me::GLOBAL_SETTINGS_NAME,
		'toggle'  => '#remember-devices-menu-items',
		'type'    => 'checkbox',
		'default' => false,
	)
);

echo '<div id="remember-devices-menu-items">';

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Remember Device Time', 'secured-wp' ),
			'id'      => Remember_Me::TIME_REMEMBER_NAME,
			'type'    => 'number',
			'default' => 5,
			'min'     => 1,
			'max'     => 30,
			'hint'    => esc_html__( 'How many days to remember given device', 'secured-wp' ),
		)
	);

	echo '</div><!-- #remember-devices-menu-items -->';
