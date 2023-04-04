<?php
/**
 * Umzugmeister Konfigurator Settings Page
 *
 * Settingspage html for the configurator.
 *
 * @package UmConfigurator
 */

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Einstellungen des Konfigurators', 'um-configurator' ); ?></h1>

		<form action="options.php" method="post" id="um_configurator_form">
			<?php settings_fields( UM_CONFIG_OPTION_GROUP ); ?>

			<?php do_settings_sections( UM_CONFIG_OPTION_PAGE ); ?>

			<?php submit_button(); ?>
		</form>
	</div>
</div>
