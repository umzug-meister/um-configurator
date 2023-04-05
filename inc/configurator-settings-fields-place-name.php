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
		name="<?php echo esc_attr( UM_CONFIG_OPTION_PLACE_NAME ); ?>"
		type="text"
		id="<?php echo esc_attr( UM_CONFIG_OPTION_PLACE_NAME ); ?>"
		value="<?php echo esc_attr( get_option( UM_CONFIG_OPTION_PLACE_NAME ) ); ?>"
		class="regular-text ltr">
