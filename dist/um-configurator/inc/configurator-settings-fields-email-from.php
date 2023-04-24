<?php
/**
 * Umzugmeister Konfigurator Settings Page Fields
 *
 * Fields HTML for Settingspage.
 *
 * @package UmConfigurator
 */

?>

<input
	name="<?php echo esc_attr( UM_CONFIG_OPTION_EMAIL_FROM ); ?>"
	type="text"
	id="<?php echo esc_attr( UM_CONFIG_OPTION_EMAIL_FROM ); ?>"
	value="<?php echo esc_attr( get_option( UM_CONFIG_OPTION_EMAIL_FROM ) ); ?>"
	class="regular-text ltr">
