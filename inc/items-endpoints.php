<?php
/**
 * Umzugmeister Konfigurator
 *
 * Dekalration der Endpoints.
 *
 * @package UmConfigurator
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );

// Item Categories.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/item/all',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_all_item',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/item/(?P<id>\d+)',
			array(
				'methods'  => 'GET',
				'callback' => 'umconf_get_item',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/item/',
			array(
				'methods'  => 'POST',
				'callback' => 'umconf_create_item',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/item/(?P<id>\d+)',
			array(
				'methods'  => 'PUT',
				'callback' => 'umconf_update_item',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'um-configurator/v1',
			'/item/(?P<id>\d+)',
			array(
				'methods'  => 'DELETE',
				'callback' => 'umconf_delete_item',
			)
		);
	}
);

/**
 * Get all Item.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_all_item( $request ) {

	$args = array(
		'post_type'      => 'items',
		'posts_per_page' => -1,
	);

	$args = umconf_attach_filter_to_query_arg( $args, $request );

	$query = new WP_Query( $args );

	$items = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = get_the_content();
			$jsonarray = json_decode( $body, true );

			$jsonarray['name'] = get_the_title();
			$jsonarray['id']   = get_the_ID();

			$itemcategories = get_the_terms( get_the_ID(), 'item-categories' );

			if ( $itemcategories ) {
				$jsonarray['categoryRefs'] = array();

				foreach ( $itemcategories as $itemcategory ) {
					$jsonarray['categoryRefs'][] = array(
						'id'   => $itemcategory->term_id,
						'name' => $itemcategory->name,
						'slug' => $itemcategory->slug,
					);
				}
			}

			$items[] = $jsonarray;
		}

		wp_reset_postdata();
	}

	$request = null;
	return $items;
}

/**
 * Get specific item.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_get_item( $request ) {

	$item_id = intval( $request['id'] );

	$args  = array(
		'p'         => $item_id,
		'post_type' => 'items',
	);
	$query = new WP_Query( $args );

	$items = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = get_the_content();
			$jsonarray = json_decode( $body, true );

			$jsonarray['name'] = get_the_title();
			$jsonarray['id']   = get_the_ID();

			$itemcategories = get_the_terms( get_the_ID(), 'item-categories' );

			if ( $itemcategories ) {
				$jsonarray['categoryRefs'] = array();

				foreach ( $itemcategories as $itemcategory ) {
					$jsonarray['categoryRefs'][] = array(
						'id'   => $itemcategory->term_id,
						'name' => $itemcategory->name,
						'slug' => $itemcategory->slug,
					);
				}
			}

			$items[] = $jsonarray;
		}

		wp_reset_postdata();

		$request = null;
		return $items[0];

	} else {

		return new WP_Error(
			'no_value',
			'Gegenstand nicht gefunden.',
			array( 'status' => 404 )
		);

	}
}

/**
 * Creates a new item.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_create_item( $request ) {
	$jsonarray  = json_decode( $request->get_body(), true );
	$categories = array();

	if ( $jsonarray['categoryRefs'] ) {
		foreach ( $jsonarray['categoryRefs'] as $catref ) {
			$categories[] = $catref['id'];
		}
	}
	$itemname = sanitize_text_field( $jsonarray['name'] );

	unset( $jsonarray['categoryRefs'] );
	unset( $jsonarray['name'] );
	unset( $jsonarray['id'] );

	// Create a new item.
	$newpostid = wp_insert_post(
		array(
			'post_title'   => $itemname,
			'post_status'  => 'publish',
			'post_type'    => 'items',
			'post_content' => wp_json_encode( $jsonarray ),
		)
	);

	if ( $newpostid && $categories ) {
		wp_set_post_terms( $newpostid, $categories, 'item-categories', false );
	}

	$data     = array( 'message' => 'Gegenstand erstellt.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

/**
 * Updates a specific item.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_update_item( $request ) {

	$item_id = intval( $request['id'] );

	$jsonarray = json_decode( $request->get_body(), true );
	if ( $jsonarray['categoryRefs'] ) {
		foreach ( $jsonarray['categoryRefs'] as $catref ) {
			$categories[] = $catref['id'];
		}
	}
	$itemname = sanitize_text_field( $jsonarray['name'] );

	unset( $jsonarray['categoryRefs'] );
	unset( $jsonarray['name'] );
	unset( $jsonarray['id'] );

	// Create a new item.
	wp_update_post(
		array(
			'ID'           => $item_id,
			'post_title'   => $itemname,
			'post_status'  => 'publish',
			'post_type'    => 'items',
			'post_content' => wp_json_encode( $jsonarray ),
		)
	);

	if ( $categories ) {
		wp_set_post_terms( $item_id, $categories, 'item-categories', false );
	}

	$data     = array( 'message' => 'Gegenstand aktualisiert.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

/**
 * Updates a specific item.
 *
 * @param Object $request   A request object.
 *
 * @return WP_REST_Response Answer.
 */
function umconf_delete_item( $request ) {

	$item_id = intval( $request['id'] );
	wp_delete_post( $item_id );
	$data     = array( 'message' => 'Gegenstand gelöscht.' );
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}
