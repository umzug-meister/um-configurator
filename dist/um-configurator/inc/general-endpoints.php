<?php
/**
 * Umzugmeister Konfigurator
 *
 * Dekalration der Endpoints.
 *
 * @package UmConfigurator
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/options/(?P<option>[a-z]+)',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_option',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/options/(?P<option>[a-z]+)',
			array(
				'methods'             => 'PUT',
				'callback'            => 'umconf_set_option',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/options',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_all_options',
			)
		);
	}
);

/**
 * Return config option value.
 *
 * @param Object $request A request object.
 *
 * @return array          Answer.
 */
function umconf_get_option( $request ) {
	$partname = sanitize_text_field( $request['option'] );
	$option   = htmlspecialchars_decode( get_option( UM_CONFIG_OPTION_PREFIX . $partname ) );
	if ( $option ) {

		return array( 'value' => $option );
	} else {
		return new WP_Error(
			'no_value',
			'Einstellung ist nicht gesetzt.',
			array( 'status' => 404 )
		);
	}
}

/**
 * Return all config options.
 *
 * @return array          Answer.
 */
function umconf_get_all_options() {
	//phpcs:disable
	global $wpdb;
	$sql          = "SELECT * FROM `" . $wpdb->prefix . "options` WHERE `option_name` LIKE '%_umconf_opt_%'";
	$results      = $wpdb->get_results($sql);
	$return_array = array();
	foreach ( $results as $result ) {
		$key                = str_replace( '_umconf_opt_', '', $result->option_name );
		$return_array[$key] = htmlspecialchars_decode( $result->option_value );
	}
	//phpcs:enable
	return $return_array;
}

/**
 * Set config option value.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_set_option( $request ) {
	$partname = sanitize_text_field( $request['option'] );
	$value    = sanitize_text_field( $request->get_param( 'value' ) );
	update_option( UM_CONFIG_OPTION_PREFIX . $partname, $value );
	$data     = array(
		'message' => 'Einstellung aktualisiert.',
		'value'   => $value,
	);
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}


