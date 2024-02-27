<?php
/**
 * Register options meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="register_options" class="panel-wrap register_options">
	<div class="wc-tabs-back"></div>
	<ul class="register_options_tabs wc-tabs">
		<?php foreach ( self::get_register_options_tabs() as $key => $settings_tab ) : ?>
			<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( implode( ' ', (array) $settings_tab['class'] ) ); ?>">
				<a href="#<?php echo esc_attr( $settings_tab['target'] ); ?>">
					<span><?php echo esc_html( $settings_tab['label'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
		self::output_tabs();

		/**
		 * Register panels.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_register_options_panels', $thepostid, $register_object );
	?>
	<div class="clear"></div>
</div>
