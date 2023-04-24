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
				'methods'             => 'PUT',
				'callback'            => 'umconf_set_option',
				'permission_callback' => function () {
					if ( UM_CONFIG_DO_AUTH ) {
						return is_user_logged_in();
					} else {
						return true;
					}
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
		'components' => 'country:de|country:at|country:ch|country:cz',
	);

	$query = $endpoint . http_build_query( $querydatadata );

	// Making request to google .
	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );

	$return  = array();
	$counter = 0;
	foreach ( $resultsbody['predictions'] as $prediction ) {

		if ( 6 === $counter ) {
			break;
		}

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

	if ( isset( $_SESSION['umconf'] ) ) {

		$inputinformations = $_SESSION['umconf'];
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
 * @SuppressWarnings(PHPMD)
 */
function umconf_put_inputinformations( $request ) {

	if ( ! isset( $_SESSION ) ) {
		session_start();
	}
	$_SESSION['umconf'] = array();
	setcookie( 'umconf-status', 0, time() + 3600, '/' );

	// Date.
	$date      = sanitize_text_field( $request->get_param( 'date' ) );
	$date_from = sanitize_text_field( $request->get_param( 'dateFrom' ) );
	$date_to   = sanitize_text_field( $request->get_param( 'dateTo' ) );

	// Addresses.
	$origin_address      = sanitize_text_field( $request->get_param( 'originAddress' ) );
	$destination_address = sanitize_text_field( $request->get_param( 'destinationAddress' ) );

	// Address Id's.
	$start_place_id       = get_option( UM_CONFIG_OPTION_PLACE_ID );
	$origin_place_id      = umconf_get_place_id( $origin_address );
	$destination_place_id = umconf_get_place_id( $destination_address );

	// Return error if its the fist attempt with wrong address.
	if ( -1 === $origin_place_id && 0 === umconf_get_attempts( $origin_address ) ) {
		$data     = array(
			'message'                => 'Adresse nicht korrekt / nicht eindeutig.',
			'origin_address_message' => 'Prüfen Sie die Adresse.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 400 );
		return $response;
	}

	// Return error if its the fist attempt with wrong address.
	if ( -1 === $destination_place_id && 0 === umconf_get_attempts( $destination_address ) ) {
		$data     = array(
			'message'                     => 'Adresse nicht korrekt / nicht eindeutig.',
			'destination_address_message' => 'Prüfen Sie die Adresse.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 400 );
		return $response;
	}

	// Check for street number.
	$origin_place_details      = umconf_get_place_details( $origin_place_id );
	$destination_place_details = umconf_get_place_details( $destination_place_id );

	// Return error if its the fist attempt with wrong address.
	if ( ! isset( $origin_place_details['house_number'] ) ) {
		$data     = array(
			'message'                => 'Bitte geben Sie die Hausnummer ein.',
			'origin_address_message' => 'Bitte geben Sie die Hausnummer ein.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 400 );
		return $response;
	}

	// Return error if its the fist attempt with wrong address.
	if ( ! isset( $destination_place_details['house_number'] ) ) {
		$data     = array(
			'message'                     => 'Bitte geben Sie die Hausnummer ein.',
			'destination_address_message' => 'Bitte geben Sie die Hausnummer ein.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 400 );
		return $response;
	}

	// Formulate input infos.
	$inputinformations = array(
		'from'     => array(
			'address' => htmlspecialchars_decode( $origin_address ),
		),
		'to'       => array(
			'address' => htmlspecialchars_decode( $destination_address ),
		),
		'distance' => array(
			'unit' => 'm',
		),
	);

	if ( 1 === intval( $request->get_param( 'mode' ) ) ) {
		$inputinformations['date'] = $date;
	} else {
		$inputinformations['date_from'] = $date_from;
		$inputinformations['date_to']   = $date_to;
	}

	$inputinformations['from'] = array_merge( $inputinformations['from'], $origin_place_details );
	$inputinformations['to']   = array_merge( $inputinformations['to'], $destination_place_details );

	if ( -1 !== $origin_place_id && -1 !== $destination_place_id && '' !== $start_place_id ) {

		// Distance between start and origin.
		$inputinformations['distance']['start_origin']  = umconf_get_distance( $start_place_id, $origin_place_id );
		$inputinformations['distance']['origin_target'] = umconf_get_distance( $origin_place_id, $destination_place_id );
		$inputinformations['distance']['target_start']  = umconf_get_distance( $destination_place_id, $start_place_id );
		$inputinformations['distance']['total']         = $inputinformations['distance']['start_origin'] + $inputinformations['distance']['origin_target'] + $inputinformations['distance']['target_start'];
	}

	$_SESSION['umconf'] = $inputinformations;

	$data = array( 'location' => get_permalink( intval( get_option( '_umconf_page_for_configurator' ) ) ) );

	$response = new WP_REST_Response( $data );
	$response->set_status( 201 );
	return $response;
}

/**
 * Returns the distance between two places in meters.
 *
 * @param string $origin_place_id first address place id.
 * @param string $destination_place_id second address place id.
 *
 * @return int Distance.
 */
function umconf_get_distance( $origin_place_id, $destination_place_id ) {
	// Calculate the distance.
	$querydatadata = array(
		'origins'      => 'place_id:' . $origin_place_id,
		'destinations' => 'place_id:' . $destination_place_id,
		'language'     => 'de',
		'key'          => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
	);

	$endpoint = 'https://maps.googleapis.com/maps/api/distancematrix/json?';
	$query    = $endpoint . http_build_query( $querydatadata );

	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );

	return intval( $resultsbody['rows'][0]['elements'][0]['distance']['value'] );
}

/**
 * Returns place id or -1 if none or too many variants were found.
 *
 * @param string $address Address to check.
 *
 * @return mixed PlaceId or -1
 */
function umconf_get_place_id( $address ) {
	$endpoint = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';
	// Query first address.
	$querydatadata = array(
		'input'      => rawurldecode( $address ),
		'language'   => 'de',
		'types'      => 'address',
		'key'        => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
		'components' => 'country:de|country:at|country:ch|country:cz',
	);

	$query = $endpoint . http_build_query( $querydatadata );

	// Making request to google.
	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );
	if ( 0 === count( $resultsbody['predictions'] ) ) {
		return -1;
	} else {
		return $resultsbody['predictions'][0]['place_id'];
	}
}

/**
 * Get place details.
 *
 * @param string $place_id Google id of the place.
 *
 * @return array Details to be merged.
 * @SuppressWarnings(PHPMD)
 */
function umconf_get_place_details( $place_id ) {
	$endpoint = 'https://maps.googleapis.com/maps/api/place/details/json?';
	// Query first address.
	$querydatadata = array(
		'placeid'  => $place_id,
		'language' => 'de',
		'key'      => get_option( UM_CONFIG_OPTION_GOOGLE_API ),
	);

	$query = $endpoint . http_build_query( $querydatadata );

	// Making request to google.
	$response    = wp_remote_get( $query );
	$resultsbody = json_decode( $response['body'], true );

	$return = array();

	foreach ( $resultsbody['result']['address_components'] as $addresscomponent ) {
		if ( in_array( 'postal_code', $addresscomponent['types'], true ) ) {
			$return['postal_code'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'locality', $addresscomponent['types'], true ) ) {
			$return['city_name'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'route', $addresscomponent['types'], true ) ) {
			$return['street'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'street_number', $addresscomponent['types'], true ) ) {
			$return['house_number'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'country', $addresscomponent['types'], true ) ) {
			$return['country'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'administrative_area_level_1', $addresscomponent['types'], true ) ) {
			$return['administrative_area_level_1'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'administrative_area_level_2', $addresscomponent['types'], true ) ) {
			$return['administrative_area_level_2'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'sublocality_level_1', $addresscomponent['types'], true ) ) {
			$return['sublocality_level_1'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}

		if ( in_array( 'sublocality_level_2', $addresscomponent['types'], true ) ) {
			$return['sublocality_level_2'] = htmlspecialchars_decode( $addresscomponent['long_name'] );
		}
	}

	return $return;
}

/**
 * Return address attempts.
 *
 * @param string $address Used address.
 *
 * @return int Amount attempts.
 */
function umconf_get_attempts( $address ) {
	if ( ! isset( $_SESSION ) ) {
		session_start();
	}

	if ( ! isset( $_SESSION['umconf']['attempts'] ) ) {
		$_SESSION['umconf']['attempts'] = array();
	}

	if ( ! isset( $_SESSION['umconf']['attempts'][ $address ] ) ) {
		$_SESSION['umconf']['attempts'][ $address ] = -1;
	}

	$_SESSION['umconf']['attempts'][ $address ]++;

	return $_SESSION['umconf']['attempts'][ $address ];
}
