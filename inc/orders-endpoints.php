<?php
/**
 * Umzugmeister Konfigurator
 *
 * Dekalration der Endpoints.
 *
 * @package UmConfigurator
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );

// order Categories.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/order/all',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_all_order',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/order/(?P<id>\d+)',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_order',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/order/',
			array(
				'methods'  => 'POST',
				'callback' => 'umconf_create_order',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/order/(?P<id>\d+)',
			array(
				'methods'  => 'PUT',
				'callback' => 'umconf_update_order',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/order/(?P<id>\d+)',
			array(
				'methods'  => 'DELETE',
				'callback' => 'umconf_delete_order',
			)
		);
	}
);

/**
 * Get all order.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_all_order( $request ) {

	$args = array(
		'post_type'      => 'orders',
		'posts_per_page' => -1,
	);

	$args = umconf_attach_filter_to_query_arg( $args, $request );

	$query = new WP_Query( $args );

	$orders = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = get_the_content();
			$jsonarray = json_decode( $body, true );

			$jsonarray['name'] = get_the_title();
			$jsonarray['id']   = get_the_ID();

			$orders[] = $jsonarray;
		}

		wp_reset_postdata();
	}

	$request = null;
	return $orders;
}

/**
 * Get specific order.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_order( $request ) {

	$order_id = intval( $request['id'] );

	$args  = array(
		'p'         => $order_id,
		'post_type' => 'orders',
	);
	$query = new WP_Query( $args );

	$orders = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = get_the_content();
			$jsonarray = json_decode( $body, true );

			$jsonarray['name'] = get_the_title();
			$jsonarray['id']   = get_the_ID();

			$orders[] = $jsonarray;
		}

		wp_reset_postdata();

		$request = null;
		return $orders[0];

	} else {

		return new WP_Error(
			'no_value',
			'Auftrag nicht gefunden.',
			array( 'status' => 404 )
		);

	}
}

/**
 * Creates a new order.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_create_order( $request ) {

	$jsonarray = json_decode( $request->get_body(), true );
	$ordername = sanitize_text_field( $jsonarray['name'] );

	unset( $jsonarray['name'] );
	unset( $jsonarray['id'] );

	// Create a new order.
	wp_insert_post(
		array(
			'post_title'   => $ordername,
			'post_status'  => 'publish',
			'post_type'    => 'orders',
			'post_content' => wp_json_encode( $jsonarray ),
		)
	);

	$data     = array( 'message' => 'Auftrag erstellt.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

/**
 * Updates a specific order.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_update_order( $request ) {

	$order_id = intval( $request['id'] );

	$jsonarray = json_decode( $request->get_body(), true );

	$ordername = sanitize_text_field( $jsonarray['name'] );

	unset( $jsonarray['name'] );
	unset( $jsonarray['id'] );

	// Create a new order.
	$status = wp_update_post(
		array(
			'ID'           => $order_id,
			'post_title'   => $ordername,
			'post_status'  => 'publish',
			'post_type'    => 'orders',
			'post_content' => wp_json_encode( $jsonarray ),
		),
		true
	);

	if ( ! is_wp_error( $status ) ) {

		$data     = array( 'message' => 'Auftrag aktualisiert.' );
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;

	} else {
		return new WP_Error(
			$status->get_error_code(),
			'Etwas ist schief gelaufen. ' . $status->get_error_message(),
			array( 'status' => 400 )
		);
	}

}

/**
 * Updates a specific order.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_delete_order( $request ) {

	$order_id = intval( $request['id'] );
	wp_delete_post( $order_id );
	$data     = array( 'message' => 'Auftrag gelöscht.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}
