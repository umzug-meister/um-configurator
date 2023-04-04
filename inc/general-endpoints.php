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
			'/autocomplete/(?P<address>.+)',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_handle_autocomplete',
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
				'methods'  => 'PUT',
				'callback' => 'umconf_set_option',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/inputinformations',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_inputinformations',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/inputinformations',
			array(
				'methods'  => 'PUT',
				'callback' => 'umconf_put_inputinformations',
			)
		);
	}
);



/**
 * Handle autocomplete.
 *
 * @param Object $request A request object.
 *
 * @return array          Answer.
 */
function umconf_handle_autocomplete( $request ) {

	$results = umconf_get_address_variants( $request['address'] );

	return array( 'results' => $results );
}

/**
 * Get variants for address chunk.
 *
 * @param string $address Address chunk.
 *
 * @return array          Answer.
 */
function umconf_get_address_variants( $address ) {
	$endpoint      = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';
	$querydatadata = array(
		'input'      => rawurldecode( $address ),
		'language'   => 'de',
		'types'      => 'address',
		'key'        => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
		'components' => 'country:de',
	);

	$query = $endpoint . http_build_query( $querydatadata );

	// Making request to google .
	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );

	$return = array();
	foreach ( $resultsbody['predictions'] as $prediction ) {

		$prediction['description'] = str_replace(
			', Deutschland',
			'',
			$prediction['description']
		);

		$return[] = array(
			'description' => $prediction['description'],
			'place_id'    => $prediction['place_id'],
		);
	}

	return $return;
}

/**
 * Return config option value.
 *
 * @param Object $request A request object.
 *
 * @return array          Answer.
 */
function umconf_get_option( $request ) {
	$partname = sanitize_text_field( $request['option'] );
	$option   = get_option( UM_CONFIG_OPTION_PREFIX . $partname );
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
	$data     = array( 'message' => 'Einstellung aktualisiert.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

/**
 * Get inputinformation.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_inputinformations( $request ) {

	$request = null;

	if ( ! isset( $_SESSION ) ) {
		session_start();
	}

	if (
		isset( $_SESSION['origin_address'] ) &&
		isset( $_SESSION['destination_address'] ) &&
		isset( $_SESSION['distance'] ) &&
		isset( $_SESSION['duration'] ) &&
		isset( $_SESSION['date'] )
	) {
		$inputinformations = array(
			'originAddress'      => $_SESSION['origin_address'],
			'destinationAddress' => $_SESSION['destination_address'],
			'distance'           => $_SESSION['distance'],
			'duration'           => $_SESSION['duration'],
			'date'               => $_SESSION['date'],
		);

		return $inputinformations;
	} else {
		$data     = array( 'message' => 'Informationen sind nicht gesetzt.' );
		$response = new WP_REST_Response( $data );
		$response->set_status( 404 );
		return $response;
	}
}

/**
 * Set inputinformation.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_put_inputinformations( $request ) {

	$origin_address       = sanitize_text_field( $request->get_param( 'originAddress' ) );
	$destination_address  = sanitize_text_field( $request->get_param( 'destinationAddress' ) );
	$origin_place_id      = null;
	$destination_place_id = null;
	$date                 = sanitize_text_field( $request->get_param( 'date' ) );

	$endpoint = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';

	$querydatadata = array(
		'input'      => rawurldecode( $origin_address ),
		'language'   => 'de',
		'types'      => 'address',
		'key'        => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
		'components' => 'country:de',
	);

	$query = $endpoint . http_build_query( $querydatadata );

	// Making request to google.
	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );

	if ( 1 !== count( $resultsbody['predictions'] ) ) {
		$data     = array(
			'message'                => 'Adresse nicht korrekt / nicht eindeutig.',
			'origin_address_message' => 'Prüfen Sie die Adresse.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 400 );
		return $response;
	} else {
		$origin_place_id = $resultsbody['predictions'][0]['place_id'];
	}

	$querydatadata = array(
		'input'      => $destination_address,
		'language'   => 'de',
		'types'      => 'address',
		'key'        => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
		'components' => 'country:de',
	);

	// Making request to google.
	$query = $endpoint . http_build_query( $querydatadata );

	// Making request to google .
	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );

	if ( 1 !== count( $resultsbody['predictions'] ) ) {
		$data     = array(
			'message'                     => 'Adresse nicht korrekt / nicht eindeutig.',
			'destination_address_message' => 'Prüfen Sie die Adresse.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 400 );
		return $response;
	} else {
		$destination_place_id = $resultsbody['predictions'][0]['place_id'];
	}

	// Calculate the distance.
	$querydatadata = array(
		'origins'      => 'place_id:' . $origin_place_id,
		'destinations' => 'place_id:' . $destination_place_id,
		'language'     => 'de',
		'key'          => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
		'components'   => 'country:de',
	);

	$endpoint = 'https://maps.googleapis.com/maps/api/distancematrix/json?';
	$query    = $endpoint . http_build_query( $querydatadata );

	$response            = wp_remote_get( $query );
	$resultsbody         = json_decode( $response['body'], true );
	$resultsbody['date'] = $date;

	if ( ! isset( $_SESSION ) ) {
		session_start();
	}

	$_SESSION['origin_address']      = $origin_address;
	$_SESSION['destination_address'] = $destination_address;
	$_SESSION['distance']            = $resultsbody['rows'][0]['elements'][0]['distance']['value'];
	$_SESSION['duration']            = $resultsbody['rows'][0]['elements'][0]['duration']['value'];
	$_SESSION['date']                = $date;

	$data = array( 'location' => get_permalink( intval( get_option( '_umconf_page_for_configurator' ) ) ) );

	$response = new WP_REST_Response( $data );
	$response->set_status( 201 );
	return $response;
}
