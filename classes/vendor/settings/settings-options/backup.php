<?php
/**
 * Import/Export settings of the plugin
 *
 * @package wpsec
 *
 * @since 2.0.0
 */

use WPSEC\Controllers\Settings;

Settings::build_option(
	array(
		'title' => esc_html__( 'Export/Import Plugin Options', 'secured-wp' ),
		'id'    => 'export-settings-tab',
		'type'  => 'tab-title',
	)
);

if ( isset( $_REQUEST['import'] ) ) {

	Settings::build_option(
		array(
			'text' => esc_html__( 'The plugin options have been imported successfully.', 'secured-wp' ),
			'type' => 'message',
		)
	);
}

Settings::build_option(
	array(
		'title' => esc_html__( 'Export', 'secured-wp' ),
		'id'    => 'export-settings',
		'type'  => 'header',
	)
);

?>

<div class="option-item">

	<p><?php esc_html_e( 'When you click the button below the plugin will create a .dat file for you to save to your computer.', 'secured-wp' ); ?>
	</p>
	<p><?php esc_html_e( 'Once you’ve saved the download file, you can use the Import function in another WordPress installation to import the plugin options from this site.', 'secured-wp' ); ?>
	</p>

	<p><a class="secwp-primary-button button button-primary button-hero"
			href="
			<?php
			print \esc_url(
				\wp_nonce_url(
					\admin_url( 'admin.php?page=' . Settings::MENU_SLUG . '&export-settings' ),
					'export-plugin-settings',
					'export_nonce'
				)
			);
			?>
				"><?php esc_html_e( 'Download Export File', 'secured-wp' ); ?></a>
	</p>
</div>

<?php

	Settings::build_option(
		array(
			'title' => \esc_html__( 'Import', 'secured-wp' ),
			'id'    => 'import-settings',
			'type'  => 'header',
		)
	);

	?>

<div class="option-item">

	<p><?php \esc_html_e( 'Upload your .dat plugin options file and we will import the options into this site.', 'secured-wp' ); ?>
	</p>
	<p><?php \esc_html_e( 'Choose a (.dat) file to upload, then click Upload file and import.', 'secured-wp' ); ?></p>

	<p>
		<label for="upload"><?php \esc_html_e( 'Choose a file from your computer:', 'secured-wp' ); ?></label>
		<input type="file" name="<?php echo \esc_attr( Settings::SETTINGS_FILE_FIELD ); ?>" id="secwp-import-file" />
	</p>

	<p>
		<input type="submit" name="<?php echo \esc_attr( Settings::SETTINGS_FILE_UPLOAD_FIELD ); ?>" id="secwp-import-upload" class="button-primary"
			value="<?php \esc_html_e( 'Upload file and import', 'secured-wp' ); ?>" />
	</p>
</div>

<?php
Settings::build_option(
	array(
		'id'   => 'reset-settings-hint',
		'type' => 'hint',
		'hint' => esc_html__( 'This is destructive operation, which can not be undone! You may want to export your current settings first.', 'secured-wp' ),
	)
);

?>

	<div class="option-item">
		<a id="secwp-reset-settings" class="secwp-primary-button button button-primary button-hero secwp-button-red" href="<?php print \esc_url( \wp_nonce_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '&reset-settings' ), 'reset-plugin-settings', 'reset_nonce' ) ); ?>" data-message="<?php esc_html_e( 'This action can not be undone. Clicking "OK" will reset your plugin options to the default installation. Click "Cancel" to stop this operation.', 'secured-wp' ); ?>"><?php esc_html_e( 'Reset All Settings', 'secured-wp' ); ?></a>
	</div>

