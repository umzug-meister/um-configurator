<?php
/**
 * Umzugmeister Konfigurator
 *
 * Dekalration der Endpoints.
 *
 * @package UmConfigurator
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );

// service Categories.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service/all',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_all_service',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service/(?P<id>\d+)',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_service',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service/',
			array(
				'methods'  => 'POST',
				'callback' => 'umconf_create_service',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service/(?P<id>\d+)',
			array(
				'methods'  => 'PUT',
				'callback' => 'umconf_update_service',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service/(?P<id>\d+)',
			array(
				'methods'  => 'DELETE',
				'callback' => 'umconf_delete_service',
			)
		);
	}
);

/**
 * Get all service.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_all_service( $request ) {

	$args = array(
		'post_type'      => 'services',
		'posts_per_page' => -1,
	);

	$args = umconf_attach_filter_to_query_arg( $args, $request );

	$query = new WP_Query( $args );

	$services = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = get_the_content();
			$jsonarray = json_decode( $body, true );

			$jsonarray['name'] = get_the_title();
			$jsonarray['id']   = get_the_ID();

			$servicecategories = get_the_terms( get_the_ID(), 'service-categories' );

			if ( $servicecategories ) {
				$jsonarray['categoryRefs'] = array();

				foreach ( $servicecategories as $servicecategory ) {
					$jsonarray['categoryRefs'][] = array(
						'id'   => $servicecategory->term_id,
						'name' => $servicecategory->name,
						'slug' => $servicecategory->slug,
					);
				}
			}

			$services[] = $jsonarray;
		}

		wp_reset_postdata();
	}

	$request = null;
	return $services;
}

/**
 * Get specific service.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_service( $request ) {

	$service_id = intval( $request['id'] );

	$args  = array(
		'p'         => $service_id,
		'post_type' => 'services',
	);
	$query = new WP_Query( $args );

	$services = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = get_the_content();
			$jsonarray = json_decode( $body, true );

			$jsonarray['name'] = get_the_title();
			$jsonarray['id']   = get_the_ID();

			$servicecategories = get_the_terms( get_the_ID(), 'service-categories' );

			if ( $servicecategories ) {
				$jsonarray['categoryRefs'] = array();

				foreach ( $servicecategories as $servicecategory ) {
					$jsonarray['categoryRefs'][] = array(
						'id'   => $servicecategory->term_id,
						'name' => $servicecategory->name,
						'slug' => $servicecategory->slug,
					);
				}
			}

			$services[] = $jsonarray;
		}

		wp_reset_postdata();

		$request = null;
		return $services[0];

	} else {

		return new WP_Error(
			'no_value',
			'Zusatzleistung nicht gefunden.',
			array( 'status' => 404 )
		);

	}
}

/**
 * Creates a new service.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_create_service( $request ) {
	$jsonarray  = json_decode( $request->get_body(), true );
	$categories = array();

	if ( $jsonarray['categoryRefs'] ) {
		foreach ( $jsonarray['categoryRefs'] as $catref ) {
			$categories[] = $catref['id'];
		}
	}
	$servicename = sanitize_text_field( $jsonarray['name'] );

	unset( $jsonarray['categoryRefs'] );
	unset( $jsonarray['name'] );
	unset( $jsonarray['id'] );

	// Create a new service.
	$newpostid = wp_insert_post(
		array(
			'post_title'   => $servicename,
			'post_status'  => 'publish',
			'post_type'    => 'services',
			'post_content' => wp_json_encode( $jsonarray ),
		)
	);

	if ( $newpostid && $categories ) {
		wp_set_post_terms( $newpostid, $categories, 'service-categories', false );
	}

	$data     = array( 'message' => 'Zusatzleistung erstellt.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

/**
 * Updates a specific service.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_update_service( $request ) {

	$service_id = intval( $request['id'] );

	$jsonarray = json_decode( $request->get_body(), true );
	if ( $jsonarray['categoryRefs'] ) {
		foreach ( $jsonarray['categoryRefs'] as $catref ) {
			$categories[] = $catref['id'];
		}
	}
	$servicename = sanitize_text_field( $jsonarray['name'] );

	unset( $jsonarray['categoryRefs'] );
	unset( $jsonarray['name'] );
	unset( $jsonarray['id'] );

	// Create a new service.
	wp_update_post(
		array(
			'ID'           => $service_id,
			'post_title'   => $servicename,
			'post_status'  => 'publish',
			'post_type'    => 'services',
			'post_content' => wp_json_encode( $jsonarray ),
		)
	);

	if ( $categories ) {
		wp_set_post_terms( $service_id, $categories, 'service-categories', false );
	}

	$data     = array( 'message' => 'Zusatzleistung aktualisiert.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

/**
 * Updates a specific service.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_delete_service( $request ) {

	$service_id = intval( $request['id'] );
	wp_delete_post( $service_id );
	$data     = array( 'message' => 'Zusatzleistung gelöscht.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}
