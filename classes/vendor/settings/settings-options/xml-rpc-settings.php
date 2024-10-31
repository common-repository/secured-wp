<?php
/**
 * Advanced settings of the plugin
 *
 * @package wpsec
 *
 * @since 2.0.0
 */

use WPSEC\Controllers\Settings;
use WPSEC\Controllers\Modules\XML_RPC_Prevents;

Settings::build_option(
	array(
		'title' => esc_html__( 'XML-RPC Settings', 'secured-wp' ),
		'id'    => 'xml-rpc-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'text' => esc_html__( 'Disables XML-RPC. By default WordPress has that enabled - this is potentially security risk - that will disable XML-RPC on your site completely', 'secured-wp' ),
		'type' => 'message',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => 'xml-rpc-settings',
		'title' => esc_html__( 'XML-RPC Settings', 'secured-wp' ),
	)
);

Settings::build_option(
	array(
		'name'    => esc_html__( 'Enable', 'secured-wp' ),
		'id'      => XML_RPC_Prevents::GLOBAL_SETTINGS_NAME,
		'toggle'  => '#xml-rpc-menu-items',
		'type'    => 'checkbox',
		'default' => true,
	)
);

echo '<div id="xml-rpc-menu-items">';

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Disable XML-RPC', 'secured-wp' ),
			'id'      => 'xmlrpc-disabled',
			'type'    => 'checkbox',
			'default' => true,
			'hint'    => esc_html__( 'Removes the XML-RPC from your WP installation', 'secured-wp' ),
		)
	);

	echo '</div><!-- #xml-rpc-menu-items -->';
