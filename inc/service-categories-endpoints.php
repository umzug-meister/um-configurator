<?php
/**
 * Umzugmeister Konfigurator
 *
 * Dekalration der Endpoints.
 *
 * @package UmConfigurator
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );

// Service Categories.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service-category/all',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_all_service_categories',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service-category/(?P<id>\d+)',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_service_category',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service-category/',
			array(
				'methods'  => 'POST',
				'callback' => 'umconf_create_service_category',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service-category/(?P<id>\d+)',
			array(
				'methods'  => 'PUT',
				'callback' => 'umconf_update_service_category',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/service-category/(?P<id>\d+)',
			array(
				'methods'  => 'DELETE',
				'callback' => 'umconf_delete_service_category',
			)
		);
	}
);

/**
 * Get all service categories.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_all_service_categories( $request ) {

	$terms = get_terms(
		array(
			'taxonomy'   => 'service-categories',
			'hide_empty' => false,
		)
	);

	$return = array();
	foreach ( $terms as $term ) {
		$return[] = array(
			'id'   => $term->term_id,
			'slug' => $term->slug,
			'name' => $term->name,
		);
	}
	$request = '';
	return $return;
}

/**
 * Get specific service category.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_service_category( $request ) {

	$term_id = intval( $request['id'] );

	$terms = get_terms(
		array(
			'taxonomy'         => 'service-categories',
			'hide_empty'       => false,
			'term_taxonomy_id' => $term_id,
		)
	);

	$return = array();
	foreach ( $terms as $term ) {
		$return = array(
			'id'   => $term->term_id,
			'slug' => $term->slug,
			'name' => $term->name,
		);
	}

	$request = '';

	if ( ! $return ) {
		return new WP_Error(
			'no_value',
			'Kategorie nicht gefunden.',
			array( 'status' => 404 )
		);
	}

	return $return;
}

/**
 * Creates a new service category.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_create_service_category( $request ) {
	$term_name   = sanitize_text_field( $request->get_param( 'name' ) );
	$insert_data = wp_insert_term(
		$term_name,
		'service-categories',
		array(
			'description' => '',
			'parent'      => 0,
		)
	);

	if ( ! is_wp_error( $insert_data ) ) {

		$terms = get_terms(
			array(
				'taxonomy'         => 'service-categories',
				'hide_empty'       => false,
				'term_taxonomy_id' => $insert_data['term_id'],
			)
		);

		$return = array();
		foreach ( $terms as $term ) {
			$return = array(
				'id'   => $term->term_id,
				'slug' => $term->slug,
				'name' => $term->name,
			);
		}

		return $return;
	} else {
		return new WP_Error(
			$insert_data->get_error_code(),
			'Etwas ist schief gelaufen. ' . $insert_data->get_error_message(),
			array( 'status' => 400 )
		);
	}
}

/**
 * Updates a specific service category.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_update_service_category( $request ) {

	$term_id     = intval( $request['id'] );
	$term_name   = sanitize_text_field( $request->get_param( 'name' ) );
	$update_data = array( 'name' => $term_name );

	if ( $request->get_param( 'slug' ) ) {
		$term_slug           = sanitize_text_field( $request->get_param( 'slug' ) );
		$update_data['slug'] = $term_slug;
	}

	$insert_data = wp_update_term(
		$term_id,
		'service-categories',
		$update_data
	);

	if ( ! is_wp_error( $insert_data ) ) {

		$data     = array( 'message' => 'Kategorie aktualisiert.' );
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;

	} else {

		return new WP_Error(
			$insert_data->get_error_code(),
			'Etwas ist schief gelaufen. ' . $insert_data->get_error_message(),
			array( 'status' => 400 )
		);

	}
}

/**
 * Updates a specific service category.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_delete_service_category( $request ) {

	$term_id          = intval( $request['id'] );
	$operation_status = wp_delete_term(
		$term_id,
		'service-categories'
	);

	if ( ! is_wp_error( $operation_status ) ) {

		$data     = array( 'message' => 'Kategorie gelöscht.' );
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;

	} else {

		return new WP_Error(
			$insert_data->get_error_code(),
			'Etwas ist schief gelaufen. ' . $insert_data->get_error_message(),
			array( 'status' => 400 )
		);

	}
}
