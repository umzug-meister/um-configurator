<?php
/**
 * Umzugmeister Konfigurator
 *
 * Hauptdatei des Plugins.
 *
 * @package UmConfigurator
 */

/*
 * Plugin Name:  Umzugmeister Konfigurator
 * Description:  Konfigurator, der den Preis eines Umzuges berechnet und ein Angebot verschickt
 * Version:      1.0.0
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );
defined( 'UMZUGKONF_SLUG' ) || define( 'UMZUGKONF_SLUG', 'umzugskosten-berechnen' );
defined( 'UMZUGKONFADMIN_SLUG' ) || define( 'UMZUGKONFADMIN_SLUG', 'konfigurator-admin' );
define( 'UM_CONFIG_OPTION_GROUP', '_ummei' );
define( 'UM_CONFIG_OPTION_PAGE', 'ummei' );
define( 'UM_CONFIG_OPTION_GOOGLE_API', '_umconf_google_api' );
define( 'UM_CONFIG_OPTION_PREFIX', '_umconf_opt_' );


register_activation_hook( __FILE__, 'umconf_activation' );
register_deactivation_hook( __FILE__, 'umconf_deactivation' );
add_filter( 'body_class', 'umconf_body_class' );
add_filter( 'display_post_states', 'umconf_add_configurator_status', 10, 2 );
add_action( 'wp_enqueue_scripts', 'umconf_include_assets' );
add_filter( 'the_content', 'umconf_append_configurator' );
add_action( 'admin_menu', 'umconf_register_configurator_settings_page' );
add_action( 'admin_init', 'umconf_register_settings' );
add_action( 'init', 'umconf_register_cpt', 0 );
add_action( 'init', 'umconf_register_ct', 0 );

// Endpoints declaration.
require 'inc/general-endpoints.php';
require 'inc/item-categories-endpoints.php';
require 'inc/service-categories-endpoints.php';
require 'inc/items-endpoints.php';
require 'inc/services-endpoints.php';
require 'inc/orders-endpoints.php';

// Functions.

/**
 * Adds filter parameters to query args
 *
 * @param array  $args    Query args.
 * @param Object $request Request object.
 *
 * @return array          Modified query args.
 */
function umconf_attach_filter_to_query_arg( $args, $request ) {

	if ( $request->get_param( 's' ) ) {
		$args['s'] = sanitize_text_field( $request->get_param( 's' ) );
	}

	if ( $request->get_param( 'status' ) ) {
		$args['post_status'] = sanitize_text_field( $request->get_param( 'status' ) );
	}

	if (
		$request->get_param( 'posts_per_page' )
		&& $request->get_param( 'paged' )
	) {
		$args['posts_per_page'] = sanitize_text_field( $request->get_param( 'posts_per_page' ) );
		$args['paged']          = sanitize_text_field( $request->get_param( 'paged' ) );

	}

	if (
		$request->get_param( 'order' )
		&& $request->get_param( 'orderby' )
	) {
		$args['order']   = sanitize_text_field( $request->get_param( 'order' ) );
		$args['orderby'] = sanitize_text_field( $request->get_param( 'orderby' ) );

	}

	if ( $request->get_param( 'qq' ) ) {
		$args['qq'] = json_decode( $request->get_param( 'qq' ), true );
	}

	return $args;
}

/**
 * Plugin activation hook
 */
function umconf_activation() {

	// Check if config page already exists and delete it if it does.
	$configpage = umconf_post_exists_by_slug( UMZUGKONF_SLUG );
	if ( $configpage ) {
		wp_delete_post( $configpage, true );
	}

	// Createa configurator page.
	$newpostid = wp_insert_post(
		array(
			'post_title'  => __( 'Umzugskosten Rechner', 'um-configurator' ),
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_name'   => UMZUGKONF_SLUG,
		)
	);

	// Save the config page in settings.
	if ( $newpostid ) {
		update_option( '_umconf_page_for_configurator', $newpostid );
	}

	// Check if config page already exists and delete it if it does.
	$configadminpage = umconf_post_exists_by_slug( UMZUGKONFADMIN_SLUG );
	if ( $configadminpage ) {
		wp_delete_post( $configadminpage, true );
	}

	// Createa configurator page.
	$newadminpostid = wp_insert_post(
		array(
			'post_title'  => __( 'Konfigurator Admin', 'um-configurator' ),
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_name'   => UMZUGKONFADMIN_SLUG,
		)
	);

	// Save the config page in settings.
	if ( $newpostid ) {
		update_option( '_umconf_page_for_configurator_admin', $newadminpostid );
	}
}


/**
 * Plugin deactivation hook
 */
function umconf_deactivation() {

	// Check if config page already exists and delete it if it does.
	$configpage = umconf_post_exists_by_slug( UMZUGKONF_SLUG );
	if ( $configpage ) {
		wp_delete_post( $configpage, true );
	}

	// Delete configurator page option.
	delete_option( '_umconf_page_for_configurator' );

	// Check if config page already exists and delete it if it does.
	$configadminpage = umconf_post_exists_by_slug( UMZUGKONFADMIN_SLUG );
	if ( $configadminpage ) {
		wp_delete_post( $configadminpage, true );
	}

	// Delete configurator page option.
	delete_option( '_umconf_page_for_configurator_admin' );
}

/**
 * Add body class.
 *
 * @param array $classes Existing body classes.
 *
 * @return array         Array with body classes.
 */
function umconf_body_class( $classes ) {

	if ( is_page( intval( get_option( '_umconf_page_for_configurator' ) ) ) ) {
		$classes[] = 'page-umzugmeister-configurator';
	}

	if ( is_page( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) ) {
		$classes[] = 'page-umzugmeister-configurator-admin';
	}

	return $classes;
}

/**
 * Chack if post with specific slug exists.
 *
 * @param string $post_slug Slug of the post..
 *
 * @return mixed boolean false if no post exists; post ID otherwise.
 */
function umconf_post_exists_by_slug( $post_slug ) {
	$args_posts = array(
		'pagename'       => $post_slug,
		'posts_per_page' => 1,
	);

	$loop_posts = new WP_Query( $args_posts );
	if ( ! $loop_posts->have_posts() ) {
		return false;
	} else {
		$loop_posts->the_post();
		return get_the_ID();
	}
	wp_reset_postdata();
}

/**
 * Filter and add status to page for configurator.
 *
 * @param array   $states Post states.
 *
 * @param WP_Post $post   Post object.
 */
function umconf_add_configurator_status( $states, $post ) {

	if (
		intval( get_option( '_umconf_page_for_configurator' ) )
		=== $post->ID
	) {
		$states['_umconf_page_for_configurator'] = __(
			'Seite für Konfigurator',
			'um-configurator'
		);
	}

	if (
		intval( get_option( '_umconf_page_for_configurator_admin' ) )
		=== $post->ID
	) {
		$states['_umconf_page_for_configurator_admin'] = __(
			'Seite für Konfigurator-Admin',
			'um-configurator'
		);
	}

	return $states;
}



/**
 * Register css and js files for this plugin.
 */
function umconf_include_assets() {
	wp_register_style(
		'um-configurator',
		plugins_url( 'assets/css/um-configurator.css', __FILE__ ),
		null,
		'1.0'
	);
	wp_enqueue_style( 'um-configurator' );

	wp_register_style(
		'jquery-ui',
		plugins_url( 'assets/css/jquery-ui.css', __FILE__ ),
		null,
		'1.0'
	);
	wp_enqueue_style( 'jquery-ui' );

	wp_register_style(
		'jquery-ui.structure',
		plugins_url( 'assets/css/jquery-ui.structure.css', __FILE__ ),
		null,
		'1.0'
	);
	wp_enqueue_style( 'jquery-ui.structure' );

	wp_register_style(
		'jquery-ui.theme',
		plugins_url( 'assets/css/jquery-ui.theme.css', __FILE__ ),
		null,
		'1.0'
	);
	wp_enqueue_style( 'jquery-ui.theme' );

	wp_register_script(
		'jquery-ui',
		plugins_url( 'assets/js/jquery-ui.js', __FILE__ ),
		array( 'jquery' ),
		'1.0',
		true
	);
	wp_enqueue_script( 'jquery-ui' );

	wp_register_script(
		'vue',
		plugins_url( 'assets/js/vue.min.js', __FILE__ ),
		array( 'jquery' ),
		'1.0',
		true
	);
	wp_enqueue_script( 'vue' );

	wp_register_script(
		'um-configurator',
		plugins_url( 'assets/js/um-configurator.js', __FILE__ ),
		array( 'jquery' ),
		'1.0',
		true
	);

	// Localize the script with new data.
	$translation_array = array(
		'baseUrl'              => get_rest_url(),
		'configuratorUrl'      => get_permalink( intval( get_option( '_umconf_page_for_configurator' ) ) ),
		'configuratorAdminUrl' => get_permalink( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ),
		'pluginDir'            => plugin_dir_url( __FILE__ ),
	);
	wp_localize_script( 'um-configurator', 'UMCONFUrls', $translation_array );

	wp_enqueue_script( 'um-configurator' );

	if ( is_page( intval( get_option( '_umconf_page_for_configurator' ) ) ) ) {
		require 'inc/enqueue-configurator.php';
	}

	if ( is_page( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) ) {
		require 'inc/enqueue-configurator-admin.php';
	}
}

/**
 * Append configurator html code to the end of content output
 *
 * @param string $content Content of the current post.
 *
 * @return string         Modified content.
 */
function umconf_append_configurator( $content ) {
	global $post;

	if (
		in_the_loop()
		&& (
			intval( get_option( '_umconf_page_for_configurator' ) )
			=== $post->ID
		)
	) {
		$content .= '<div id="um-configurator" class="um-configurator"></div>';
	}

	if (
		in_the_loop()
		&& (
			intval( get_option( '_umconf_page_for_configurator_admin' ) )
			=== $post->ID
		)
	) {
		$content .= '<div id="um-configurator-admin" class="um-configurator-admin"></div>';
	}
	return $content;
}
/**
 * Adds a link to configurator settings to common settings.
 */
function umconf_register_configurator_settings_page() {
	add_options_page(
		__( 'Konfigurator', 'um-configurator' ),
		__( 'Konfigurator', 'um-configurator' ),
		'manage_options',
		UM_CONFIG_OPTION_PAGE,
		'umconf_render_configurator_settings_page'
	);
}

/**
 * Output configurator settings page html.
 */
function umconf_render_configurator_settings_page() {
	include 'inc/configurator-settings.php';
}

/**
 * Register settings.
 */
function umconf_register_settings() {

	register_setting(
		UM_CONFIG_OPTION_GROUP,
		UM_CONFIG_OPTION_GOOGLE_API,
		array(
			'type' => 'string',
		)
	);

	add_settings_section(
		UM_CONFIG_OPTION_GROUP,
		null,
		function() {
			include 'inc/configurator-settings-section.php';
		},
		UM_CONFIG_OPTION_PAGE
	);

	add_settings_field(
		UM_CONFIG_OPTION_GOOGLE_API,
		__( 'Google API-Key', 'um-configurator' ),
		function() {
			include 'inc/configurator-settings-fields.php';
		},
		UM_CONFIG_OPTION_PAGE,
		UM_CONFIG_OPTION_GROUP
	);

	if ( function_exists( 'acf_add_local_field_group' ) ) {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_5c12bb92256c4',
				'title'                 => 'Configurator',
				'fields'                => array(
					array(
						'key'               => 'field_5c12bbd68387a',
						'label'             => 'Title',
						'name'              => 'configurator_title',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'default_value'     => '',
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
						'maxlength'         => '',
					),
					array(
						'key'               => 'field_5c12bbe18387b',
						'label'             => 'Button',
						'name'              => 'configurator_button',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'default_value'     => '',
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
						'maxlength'         => '',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'page_type',
							'operator' => '==',
							'value'    => 'front_page',
						),
					),
				),
				'menu_order'            => 2,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => array(
					0 => 'the_content',
				),
				'active'                => 1,
				'description'           => '',
			)
		);
	}

}

/**
 * Displays configurator HTML.
 */
function umconf_display_configurator() {
	include 'inc/configurator-form.php';
}

/**
 * Register custom post types
 */
function umconf_register_cpt() {

	register_post_type(
		'items',
		array(
			'label'               => __( 'Items' ),
			'description'         => __( 'Items' ),
			'supports'            => array( 'title', 'editor' ),
			'public'              => true,
			'hierarchical'        => false,
			'has_archive'         => false,
			'can_export'          => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
		)
	);

	register_post_type(
		'services',
		array(
			'label'               => __( 'Services' ),
			'description'         => __( 'Services' ),
			'supports'            => array( 'title', 'editor' ),
			'public'              => true,
			'hierarchical'        => false,
			'has_archive'         => false,
			'can_export'          => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
		)
	);

	register_post_type(
		'orders',
		array(
			'label'               => __( 'Orders' ),
			'description'         => __( 'Orders' ),
			'supports'            => array( 'title', 'editor' ),
			'public'              => true,
			'hierarchical'        => false,
			'has_archive'         => false,
			'can_export'          => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
		)
	);

}

/**
 * Register custom taxonomies
 */
function umconf_register_ct() {

	register_taxonomy(
		'item-categories',
		array( 'items' ),
		array(
			'hierarchical'      => false,
			'labels'            => array( 'name' => 'Item-Categories' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'item-categories' ),
		)
	);

	register_taxonomy(
		'service-categories',
		array( 'services' ),
		array(
			'hierarchical'      => false,
			'labels'            => array( 'name' => 'Service-Categories' ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'service-categories' ),
		)
	);

}
