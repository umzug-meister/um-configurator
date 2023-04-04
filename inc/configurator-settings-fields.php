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
	name="<?php echo esc_attr( UM_CONFIG_OPTION_GOOGLE_API ); ?>"
	type="text"
	id="<?php echo esc_attr( UM_CONFIG_OPTION_GOOGLE_API ); ?>"
	value="<?php echo esc_attr( get_option( UM_CONFIG_OPTION_GOOGLE_API ) ); ?>"
	class="regular-text ltr">
