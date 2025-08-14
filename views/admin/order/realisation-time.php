<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 14.08.2025
 * Time: 16:28
 *
 * @var $order WC_Order
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

woocommerce_wp_text_input(
	array(
		'id'    => '_realisation_tim',
		'value' => $order->get_meta( '_realisation_time' ),
		'label' => __( 'Termin realizacji', 'netivo' ),
		'type'  => 'text',
	)
);