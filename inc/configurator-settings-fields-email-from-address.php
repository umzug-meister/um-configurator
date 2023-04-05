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
	name="<?php echo esc_attr( UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS ); ?>"
	type="email"
	id="<?php echo esc_attr( UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS ); ?>"
	value="<?php echo esc_attr( get_option( UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS ) ); ?>"
	class="regular-text ltr">
