<?php
/**
 * Umzugmeister Konfigurator
 *
 * Admin-Panel zur Verwaltung von Umzugsartikeln, Dienstleistungen und Aufträgen
 * inklusive Angebotsversand per E-Mail.
 *
 * @package UmConfigurator
 */

/*
 * Plugin Name:  Umzugmeister Konfigurator
 * Description:  Admin-Panel für Umzugsartikel, Dienstleistungen und Auftragsverwaltung mit Angebots-E-Mail
 * Version:      2.0.0
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );
defined( 'UMZUGKONFADMIN_SLUG' ) || define( 'UMZUGKONFADMIN_SLUG', 'konfigurator' );
defined( 'UM_CONFIG_DO_AUTH' ) || define( 'UM_CONFIG_DO_AUTH',  'local'  !== wp_get_environment_type() );
define( 'UM_CONFIG_OPTION_GROUP', '_ummei' );
define( 'UM_CONFIG_OPTION_PAGE', 'ummei' );
define( 'UM_CONFIG_OPTION_EMAIL', '_umconf_opt_companyEmail' );
define( 'UM_CONFIG_OPTION_EMAIL_FROM', '_umconf_opt_emailFromName' );
define( 'UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS', '_umconf_opt_emailFrom' );
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
add_action( 'template_redirect', 'umconf_admin_html' );
add_action( 'template_redirect', 'umconf_redirects' );
add_action( 'wp_head', 'umconf_admin_css' );
add_action( 'wp_footer', 'umconf_add_custom_js' );
add_filter( 'wp_mail_content_type', 'umconf_set_html_mail_content_type' );

remove_filter( 'the_title', 'wptexturize' );
remove_filter( 'the_content', 'wptexturize' );

// Endpoints declaration.
require 'inc/general-endpoints.php';
require 'inc/item-categories-endpoints.php';
require 'inc/service-categories-endpoints.php';
require 'inc/items-endpoints.php';
require 'inc/services-endpoints.php';
require 'inc/orders-endpoints.php';
require 'inc/simple_html_dom.php';

// Functions.

/**
 * Redirect to login if not logged in.
 */
function umconf_redirects() {

	if ( ! isset( $_SESSION ) ) {
		session_start();
	}

	if ( is_page( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) && ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( get_permalink( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) ), 302 );
		exit();
	}


}

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

	if ( $request->get_param( 'order' ) && $request->get_param( 'orderby' ) ) {
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

	// Check if admin page already exists and delete it if it does.
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

	// Save the admin page in settings.
	if ( $newadminpostid ) {
		update_option( '_umconf_page_for_configurator_admin', $newadminpostid );
	}
}


/**
 * Plugin deactivation hook
 */
function umconf_deactivation() {

	// Check if admin page already exists and delete it if it does.
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
 *
 * @SuppressWarnings(PHPMD)
 */
function umconf_register_settings() {

	register_setting(
		UM_CONFIG_OPTION_GROUP,
		UM_CONFIG_OPTION_EMAIL,
		array(
			'type' => 'string',
		)
	);

	register_setting(
		UM_CONFIG_OPTION_GROUP,
		UM_CONFIG_OPTION_EMAIL_FROM,
		array(
			'type' => 'string',
		)
	);

	register_setting(
		UM_CONFIG_OPTION_GROUP,
		UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS,
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
		UM_CONFIG_OPTION_EMAIL,
		__( 'Postfach für Angebot Kopie', 'um-configurator' ),
		function() {
			include 'inc/configurator-settings-fields-email.php';
		},
		UM_CONFIG_OPTION_PAGE,
		UM_CONFIG_OPTION_GROUP
	);

	add_settings_field(
		UM_CONFIG_OPTION_EMAIL_FROM,
		__( 'Name im FROM Feld', 'um-configurator' ),
		function() {
			include 'inc/configurator-settings-fields-email-from.php';
		},
		UM_CONFIG_OPTION_PAGE,
		UM_CONFIG_OPTION_GROUP
	);

	add_settings_field(
		UM_CONFIG_OPTION_EMAIL_FROM_ADDRESS,
		__( 'E-Mail-Adresse im FROM Feld', 'um-configurator' ),
		function() {
			include 'inc/configurator-settings-fields-email-from-address.php';
		},
		UM_CONFIG_OPTION_PAGE,
		UM_CONFIG_OPTION_GROUP
	);
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
			'public'              => false,
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
			'public'              => false,
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
			'public'              => false,
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
			'public'            => false,
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
			'public'            => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'service-categories' ),
		)
	);

}

/**
 * Start buffer on admin page.
 */
function umconf_admin_html() {
	if ( is_page( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) ) {
		ob_start( 'umconf_end_buffering' );
	}
}

/**
 *  Start process html.
 *
 * @param string $content HTML of the page.
 *
 * @return string         Modified HTML.
 */
function umconf_end_buffering( $content ) {

	$html = str_get_html( $content );

	if ( ! $html ) {
		return $content;
	}

	if ( $el = $html->find( 'header.site-header', 0 ) ) {
		$el->outertext = '';
	}

	if ( $el = $html->find( 'main#wp--skip-link--target', 0 ) ) {
		$el->outertext = '<div id="um-configurator-admin" class="um-configurator-admin"></div>';
	}

	if ( $el = $html->find( 'footer.site-footer', 0 ) ) {
		$el->outertext = '';
	}

	if ( $el = $html->find( '#wpadminbar', 0 ) ) {
		$el->outertext = '';
	}

	$html = str_get_html( $html->save() );

	$html->find( 'body', 0 )->class = preg_replace( '/\badmin-bar\b/', '', $html->find( 'body', 0 )->class );

	return $html;
}

/**
 * Output specific js in header
 */
/**
 * Hide admin bar and theme chrome on the configurator page.
 */
function umconf_admin_css() {
	if ( ! is_page( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) ) {
		return;
	}
	?>
<style>
#wpadminbar { display: none !important; }
html, body { margin-top: 0 !important; padding-top: 0 !important; }
</style>
	<?php
}

function umconf_add_custom_js() {
	if ( ! is_page( intval( get_option( '_umconf_page_for_configurator_admin' ) ) ) ) {
		return;
	}

	?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	console.group("[Konfigurator] Page Load");
	console.log("Admin container:", document.getElementById("um-configurator-admin"));
	console.log("Admin container class:", document.querySelector(".um-configurator-admin") ? "present" : "missing");
	console.log("Header stripped:", document.querySelector("header") === null);
	console.log("Main stripped:", document.querySelector("main") === null);
	console.log("Footer stripped:", document.querySelector("footer") === null);
	console.log("SPA scripts:", document.querySelectorAll('script[src*="konfigurator"]').length);
	console.log("Page URL:", window.location.href);
	console.groupEnd();
});
window.UMCONFUrls = window.UMCONFUrls || {};
window.UMCONFUrls.nonce = "<?php echo wp_create_nonce( 'wp_rest' ); ?>";
</script>
<?php
}

/**
 * Sets email format to plan text.
 */
function umconf_set_html_mail_content_type() {
	return 'text/plain';
}
