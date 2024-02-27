<?php
/**
 * Database Update Script for 5.2.9
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

// Update roles and capabilities.
WC_POS_Install::remove_roles();
WC_POS_Install::create_roles();
