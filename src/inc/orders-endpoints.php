<?php
/**
 * Konfigurator
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
				'methods'             => 'PUT',
				'callback'            => 'umconf_update_order',
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
			'/order/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => 'umconf_delete_order',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
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

			$body      = htmlspecialchars_decode( get_the_content() );
			$jsonarray = maybe_unserialize( $body );

			$jsonarray['name'] = htmlspecialchars_decode( get_the_title() );
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

			$body      = htmlspecialchars_decode( get_the_content() );
			$jsonarray = maybe_unserialize( $body );

			$jsonarray['name'] = htmlspecialchars_decode( get_the_title() );
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
	$insert_id = wp_insert_post(
		array(
			'post_title'   => $ordername,
			'post_status'  => 'publish',
			'post_type'    => 'orders',
			'post_content' => maybe_serialize( $jsonarray ),
		)
	);

	$args  = array(
		'p'         => $insert_id,
		'post_type' => 'orders',
	);
	$query = new WP_Query( $args );

	$orders = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$body      = htmlspecialchars_decode( get_the_content() );
			$jsonarray = maybe_unserialize( $body );

			$jsonarray['name'] = htmlspecialchars_decode( get_the_title() );
			$jsonarray['id']   = get_the_ID();

			$orders[] = $jsonarray;
		}

		wp_reset_postdata();

		umconf_send_order_email( $orders[0] );

		$response = new WP_REST_Response( $orders[0] );
		$response->set_status( 201 );
		return $response;

	} else {
		$data     = array( 'Auftrag konnte nicht angelegt werden. ' );
		$response = new WP_REST_Response( $data );
		$response->set_status( 500 );
		return $response;
	}
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
			'post_content' => maybe_serialize( $jsonarray ),
		),
		true
	);

	if ( ! is_wp_error( $status ) ) {

		$args  = array(
			'p'         => $order_id,
			'post_type' => 'orders',
		);
		$query = new WP_Query( $args );

		$orders = array();

		if ( $query->have_posts() ) {

			while ( $query->have_posts() ) {
				$query->the_post();

				$body      = htmlspecialchars_decode( get_the_content() );
				$jsonarray = maybe_unserialize( $body );

				$jsonarray['name'] = htmlspecialchars_decode( get_the_title() );
				$jsonarray['id']   = get_the_ID();

				$orders[] = $jsonarray;
			}

			wp_reset_postdata();

			$response = new WP_REST_Response( $orders[0] );
			$response->set_status( 200 );
			return $response;

		} else {
			$data     = array( 'Auftrag konnte nicht aktualisiert werden. ' );
			$response = new WP_REST_Response( $data );
			$response->set_status( 500 );
			return $response;
		}
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
	$response = new WP_REST_Response();
	$response->set_status( 200 );
	return $response;
}

/**
 * Send 2 emails with order details.
 *
 * @param array $order Order data.
 *
 * @return void
 * @SuppressWarnings(PHPMD)
 */
function umconf_send_order_email( $order ) {

	// phpcs:disable
	$order = $order;

	// Load load template.
	$file_path3 = dirname( dirname( __FILE__ ) ) . '/app-dist/messages/templates/email.php';
	ob_start();
	include( $file_path3 );
	$email_text = ob_get_clean();

	// Load internal subject.
	$file_path4 = dirname( dirname( __FILE__ ) ) . '/app-dist/messages/templates/subject_company.php';
	ob_start();
	include( $file_path4 );
	$subject_internal = ob_get_clean();

	// Load internal subject.
	$file_path5 = dirname( dirname( __FILE__ ) ) . '/app-dist/messages/templates/subject_customer.php';
	ob_start();
	include( $file_path5 );
	$subject_customer = ob_get_clean();
	$subject_customer = $subject_customer;

	// phpcs:enable
	add_filter( 'wp_mail_from_name', 'umconf_filter_wp_mail_from_name', 10, 1 );
	add_filter( 'wp_mail_from', 'umconf_filter_wp_mail_from_address', 10, 1 );
	$headers[] = 'Reply-To: ' . trim( $order['customer']['email'] );
	if ( get_option( UM_CONFIG_OPTION_EMAIL ) ) {
		wp_mail( get_option( UM_CONFIG_OPTION_EMAIL ), $subject_internal, $email_text, $headers );
	}

	if ( isset( $order['customer']['email'] ) && '' !== trim( $order['customer']['email'] ) ) {
		wp_mail( trim( $order['customer']['email'] ), $subject_customer, $email_text );
	}
	remove_filter( 'wp_mail_from_name', 'umconf_filter_wp_mail_from_name', 10, 1 );
	remove_filter( 'wp_mail_from', 'umconf_filter_wp_mail_from_address', 10, 1 );
}

/**
 * Modify eMail From header
 *
 * @param string $from_name From name in eMail.
 *
 * @return string
 */
function umconf_filter_wp_mail_from_name( $from_name ) {
	$from_name = get_option( UM_CONFIG_OPTION_EMAIL_FROM );
	return $from_name;
}

/**
 * Modify eMail Address.
 *
 * @param string $address eMail.
 *
 * @return string
 */
function umconf_filter_wp_mail_from_address( $address ) {
	if ( get_option( UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS ) ) {
		$address = get_option( UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS );
	}
	return $address;
}
