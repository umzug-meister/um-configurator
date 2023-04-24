<?php
/**
 * Umzugmeister Konfigurator Settings Page Fields
 *
 * Fields HTML for Settingspage.
 *
 * @package UmConfigurator
 */

// phpcs:disable
wp_dropdown_pages(
	array(
		'name'              => UM_CONFIG_OPTION_PAGE_FOR_SUCCESS,
		'show_option_none'  => '&mdash; Auswählen &mdash;',
		'option_none_value' => '0',
		'selected'          => get_option( UM_CONFIG_OPTION_PAGE_FOR_SUCCESS ),
	)
);
// phpcs:enable
