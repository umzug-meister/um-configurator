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
		name="<?php echo esc_attr( UM_CONFIG_OPTION_PLACE_ID ); ?>"
		type="text"
		readonly
		id="<?php echo esc_attr( UM_CONFIG_OPTION_PLACE_ID ); ?>"
		value="<?php echo esc_attr( get_option( UM_CONFIG_OPTION_PLACE_ID ) ); ?>"
		class="regular-text ltr">
