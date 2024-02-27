<?php
/**
 * My Account Tab
 *
 * @package WooCommerce_Point_Of_Sale/Vies
 */

defined( 'ABSPATH' ) || exit;
?>

<table class="shop_table shop_table_responsive">
	<thead>
	<tr>
		<th class=""><?php esc_html_e( 'Register', 'woocommerce-point-of-sale' ); ?></th>
		<th class=""><?php esc_html_e( 'Outlet', 'woocommerce-point-of-sale' ); ?></th>
		<th class=""><?php esc_html_e( 'Actions', 'woocommerce-point-of-sale' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$registers = get_posts(
		[
			'numberposts' => -1,
			'post_type'   => 'pos_register',
		]
	);
	$count     = 0;

	foreach ( $registers as $register ) {
		$register = wc_pos_get_register( $register->ID );
		if ( wc_pos_current_user_can_open_register( $register->get_id() ) ) {
			$count++;
			$outlet        = wc_pos_get_outlet( $register->get_outlet() );
			$register_link = get_home_url( null, '/point-of-sale/' . $outlet->get_slug() . '/' . $register->get_slug() );
			?>
			<tr class="">
				<td data-title="<?php esc_attr_e( 'Register', 'woocommerce-point-of-sale' ); ?>">
					<?php
					echo wp_kses_post(
						sprintf(
							'<a href="%1$s" target="_blank">%2$s</a>',
							$register_link,
							ucfirst( $register->get_name() )
						)
					);
					?>
				</td>
				<td>
					<?php echo esc_html( ucfirst( $outlet->get_name() ) ); ?>
				</td>
				<td>
					<?php
						$can_force_logout   = wc_pos_current_user_can_force_logout();
						$is_register_locked = wc_pos_is_register_locked( $register->get_id() );
						$is_register_open   = wc_pos_is_register_open( $register->get_id() );

					if ( $is_register_locked ) {
						$user           = get_userdata( $is_register_locked );
						$logged_in_user = trim( $user->first_name . ' ' . $user->last_name );
						$logged_in_user = empty( $logged_in_user ) ? $user->user_login : $logged_in_user;

						/* translators: %s: user full name */
						$tip = sprintf( __( '%s is currently logged on this register.', 'woocommerce-point-of-sale' ), $logged_in_user );

						if ( $can_force_logout ) {
							echo '<a class="woocommerce-button button" href="' . esc_attr( $register_link ) . '" title="' . esc_attr( $tip ) . '" target="_blank">' . esc_html__( 'Open', 'woocommerce-point-of-sale' ) . '</a>';
						} else {
							echo '<a class="woocommerce-button button disabled" title="' . esc_attr( $tip ) . '" target="_blank">' . esc_html__( 'Open', 'woocommerce-point-of-sale' ) . '</a>';
						}
					} else {
						$button = $is_register_open ? __( 'Enter', 'woocommerce-point-of-sale' ) : __( 'Open', 'woocommerce-point-of-sale' );
						$tip    = $is_register_open ? __( 'Enter Register', 'woocommerce-point-of-sale' ) : __( 'Open Register', 'woocommerce-point-of-sale' );

						echo '<a class="woocommerce-button button" href="' . esc_attr( $register_link ) . '" title="' . esc_attr( $tip ) . '" target="_blank">' . esc_html( $button ) . '</a>';
					}
					?>
				</td>
			</tr>
			<?php
		}
	}

	if ( $count < 1 ) :
		?>
		<tr>
			<td colspan="3"><span class="no-rows-found"><?php esc_html_e( 'No registers found.', 'woocommerce-point-of-sale' ); ?></span></td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>

