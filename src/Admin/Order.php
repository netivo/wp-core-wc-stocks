<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 14.08.2025
 * Time: 15:59
 *
 */

namespace Netivo\Module\WooCommerce\Stocks\Admin;


use Automattic\WooCommerce\Utilities\OrderUtil;
use Netivo\Module\WooCommerce\Stocks\Module;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Order {

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'realisation_time_metabox' ] );
		add_action( 'save_post', [ $this, 'save_realisation_time' ] );
		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_realisation_time' ] );
	}

	public function realisation_time_metabox() {
		$screen = OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'nt-order-realisation-time', 'Termin realizacji', [
			$this,
			'render_realisation_time'
		], $screen, 'side', 'core' );
	}

	public function render_realisation_time( $post_or_order_object ) {
		if ( $post_or_order_object instanceof WP_Post ) {
			$order = wc_get_order( $post_or_order_object->ID );
		} else {
			$order = $post_or_order_object;
		}

		if ( ! $order ) {
			return;
		}
		wp_nonce_field( 'save_order_realisation_time', 'order_realisation_time_nonce' );

		$filename = Module::get_module_path() . '/views/admin/order/realisation-time.php';

		include $filename; //phpcs:ignore
	}

	public function save_realisation_time( $order_id ) {
		if ( ! isset( $_POST['order_realisation_time_nonce'] ) ) {
			return $order_id;
		}
		if ( ! wp_verify_nonce( $_POST['order_realisation_time_nonce'], 'save_order_realisation_time' ) ) {
			return $order_id;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $order_id;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $order_id;
		}

		$order->update_meta_data( '_realisation_time', $_POST['_realisation_time'] );
		$order->save();

		return $order_id;
	}
}