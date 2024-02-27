<?php
/**
 * Client App.
 *
 * Renders the Point of Sale UI.
 */
use Mexitek\PHPColors\Color;

$register_data = WC_POS_App::instance()->get_register_data( $wp->query_vars['register'] );
$outlet_data   = WC_POS_App::instance()->get_outlet_data( $wp->query_vars['outlet'] );
$is_dev        = 'development' === wc_pos_get_env();

$primary_color   = empty( get_option( 'wc_pos_theme_primary_color' ) ) ? '#7f54b3' : get_option( 'wc_pos_theme_primary_color', '#7f54b3' );
$secondary_color = '#3c3c3c';
$accent_color    = new Color( $primary_color );
$accent_color    = '#' . $accent_color->darken( 10 );
$dark_color      = '#1d1d1d';
$positive_color  = '#71b02f';
$negative_color  = '#C10015';
$info_color      = '#52aad0';
$warning_color   = '#fea000';

$body_style = 'overflow:hidden;'
	. "background:{$primary_color};"
	. "--q-primary:{$primary_color};"
	. "--q-secondary:{$secondary_color};"
	. "--q-accent:{$accent_color};"
	. "--q-dark:{$dark_color};"
	. "--q-positive:{$positive_color};"
	. "--q-negative:{$negative_color};"
	. "--q-info:{$info_color};"
	. "--q-warning:{$warning_color};";

// $validate_manifest         = true;
// $manifest_url              = WC_POS()->plugin_url() . '/assets/dist/images/manifest.json';
// $mainfest_url_headers      = wc_pos_get_headers( $manifest_url, 1 );
// $mainfest_url_content_type = isset( $mainfest_url_headers, $mainfest_url_headers['Content-Type'] ) ? $mainfest_url_headers['Content-Type'] : '';
// $manifest_path             = WC_POS()->plugin_path() . '/assets/dist/images/manifest.json';
// $manifest_content          = wc_pos_file_get_contents( $manifest_path );
// $manifest_content_decoded  = json_decode( $manifest_content, true );

// if (
// is_null( $manifest_content_decoded )
// || ( 'application/json' !== strtolower( $mainfest_url_content_type ) )
// ) {
// $validate_manifest = false;
// }

// @temp
$validate_manifest = false;
$manifest_url      = '';

defined( 'ABSPATH' ) || exit;
?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $is_dev ? '[DEV] ' : ''; ?><?php echo esc_html( $register_data['name'] ) . ' &lsaquo; ' . esc_html( $outlet_data['name'] ) . ' &lsaquo; ' . esc_html__( 'Point of Sale', 'woocommerce-point-of-sale' ); ?></title>

		<?php if ( $validate_manifest ) : ?>
		<link rel="manifest" href="<?php echo esc_url( $manifest_url ); ?>">
		<?php endif; ?>

		<link rel="apple-touch-icon" sizes="57x57" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-57x57.png'; ?>">
		<link rel="apple-touch-icon" sizes="60x60" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-60x60.png'; ?>">
		<link rel="apple-touch-icon" sizes="72x72" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-72x72.png'; ?>">
		<link rel="apple-touch-icon" sizes="76x76" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-76x76.png'; ?>">
		<link rel="apple-touch-icon" sizes="114x114" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-114x114.png'; ?>">
		<link rel="apple-touch-icon" sizes="120x120" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-120x120.png'; ?>">
		<link rel="apple-touch-icon" sizes="144x144" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-144x144.png'; ?>">
		<link rel="apple-touch-icon" sizes="152x152" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-152x152.png'; ?>">
		<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/apple-icon-180x180.png'; ?>">
		<link rel="icon" type="image/png" sizes="192x192"  href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/android-icon-192x192.png'; ?>">
		<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/favicon-32x32.png'; ?>">
		<link rel="icon" type="image/png" sizes="96x96" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/favicon-96x96.png'; ?>">
		<link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/favicon-16x16.png'; ?>">
		<link rel="mask-icon" href="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/safari-pinned-tab.svg'; ?>" color="#7f54b3">
		<meta name="msapplication-TileColor" content="<?php echo esc_attr( $primary_color ); ?>">
		<meta name="msapplication-TileImage" content="<?php echo esc_url( WC_POS()->plugin_url() ) . '/assets/dist/images/ms-icon-144x144.png'; ?>">
		<meta name="theme-color" content="<?php echo esc_attr( $primary_color ); ?>">
		<meta http-equiv="Content-Type" name="viewport" charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="<?php echo esc_attr( $primary_color ); ?>" />
	</head>
	<body style="<?php echo esc_attr( $body_style ); ?>">
		<div id="q-app"></div>
		<?php
			/*
			 * Invoking the following functions one by one prevents unwanted scripts and styles to
			 * be loaded by wp_footer()
			 */
			wp_enqueue_scripts();
			print_late_styles();
			print_footer_scripts();
		?>
	</body>
</html>
