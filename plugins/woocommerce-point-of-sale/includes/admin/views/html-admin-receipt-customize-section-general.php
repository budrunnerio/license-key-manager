<?php
/**
 * Receipt Customizer - General Details
 *
 * @var object $receipt_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */
?>
<!-- Receipt Name -->
<li class="customize-control customize-control-text">
	<label class="customize-control-title" for="name"><?php esc_html_e( 'Receipt Name', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Distinguish this template from other templates by giving it a name.', 'woocommerce-point-of-sale' ); ?></span>
	<input type="text" id="name" name="name" value="<?php echo esc_attr( $receipt_object->get_name( 'edit' ) ); ?>" />
</li>

<!-- Show Title -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_title" name="show_title" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_title(), true, true ); ?>>
		<label for="show_title"><?php esc_html_e( 'Title', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the title.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Receipt Title Position -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="title_position"><?php esc_html_e( 'Receipt Title Position', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Choose the position of the receipt title that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select type="text" id="title_position" name="title_position">
		<option value="left" <?php selected( $receipt_object->get_title_position( 'edit' ), 'left', true ); ?> ><?php esc_html_e( 'Left', 'woocommerce-point-of-sale' ); ?></option>
		<option value="center" <?php selected( $receipt_object->get_title_position( 'edit' ), 'center', true ); ?>><?php esc_html_e( 'Center', 'woocommerce-point-of-sale' ); ?></option>
		<option value="right" <?php selected( $receipt_object->get_title_position( 'edit' ), 'right', true ); ?>><?php esc_html_e( 'Right', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Print Copies  -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="print_copies"><?php esc_html_e( 'Print Copies', 'woocommerce-point-of-sale' ); ?></label> <span class="description customize-control-description"><?php esc_html_e( 'Select how you want the number of receipts to be determined.', 'woocommerce-point-of-sale' ); ?></span> <select id="print_copies" name="print_copies">
		<option value="num_copies" <?php selected( $receipt_object->get_print_copies( 'edit' ), 'num_copies', true ); ?>><?php echo esc_html( 'Number of copies', 'woocommerce-point-of-sale' ); ?></option>
		<option value="per_category" <?php selected( $receipt_object->get_print_copies( 'edit' ), 'per_category', true ); ?>><?php echo esc_html( 'Per category', 'woocommerce-point-of-sale' ); ?></option>
		<option value="per_product" <?php selected( $receipt_object->get_print_copies( 'edit' ), 'per_product', true ); ?>><?php echo esc_html( 'Per product item', 'woocommerce-point-of-sale' ); ?></option>
		<option value="per_quantity" <?php selected( $receipt_object->get_print_copies( 'edit' ), 'per_quantity', true ); ?>><?php echo esc_html( 'Per quantity', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Number of Copies -->
<li class="customize-control customize-control-select" id="control_num_copies">
	<label class="customize-control-title" for="num_copies"><?php esc_html_e( 'Number of Copies', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Set the number of copies you want to print when this receipt is generated.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="num_copies" name="num_copies">
		<option value="1" <?php selected( $receipt_object->get_num_copies( 'edit' ), 1, true ); ?>><?php echo esc_html_x( '1', 'Receipt number of copies', 'woocommerce-point-of-sale' ); ?></option>
		<option value="2" <?php selected( $receipt_object->get_num_copies( 'edit' ), 2, true ); ?>><?php echo esc_html_x( '2', 'Receipt number of copies', 'woocommerce-point-of-sale' ); ?></option>
		<option value="3" <?php selected( $receipt_object->get_num_copies( 'edit' ), 3, true ); ?>><?php echo esc_html_x( '3', 'Receipt number of copies', 'woocommerce-point-of-sale' ); ?></option>
		<option value="4" <?php selected( $receipt_object->get_num_copies( 'edit' ), 4, true ); ?>><?php echo esc_html_x( '4', 'Receipt number of copies', 'woocommerce-point-of-sale' ); ?></option>
		<option value="5" <?php selected( $receipt_object->get_num_copies( 'edit' ), 5, true ); ?>><?php echo esc_html_x( '5', 'Receipt number of copies', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Receipt Width -->
<li class="customize-control customize-control-text">
	<label class="customize-control-title" for="width"><?php esc_html_e( 'Print Width', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Set the width (in mm) of the receipt when it is generated for printing. For dynamic width enter 0.', 'woocommerce-point-of-sale' ); ?></span>
	<input type="number" name="width" id="width" min="0" max="120" step="5" default="0" value="<?php echo esc_attr( $receipt_object->get_width( 'edit' ) ); ?>" style="width:100px;" /><span style="margin-left:10px;line-height:2;"><?php echo esc_html_x( 'mm', 'Millimeter', 'woocommerce-point-of-sale' ); ?></span><br>
</li>

<!-- Receipt Type -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="type"><?php esc_html_e( 'Receipt Type', 'woocommerce-point-of-sale' ); ?></label>
	<select name="type" id="type">
		<option value="html" <?php selected( $receipt_object->get_type(), 'html', true ); ?>><?php esc_html_e( 'HTML', 'woocommerce-point-of-sale' ); ?></option>
		<option value="normal" <?php selected( $receipt_object->get_type(), 'normal', true ); ?>><?php esc_html_e( 'Normal', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>
