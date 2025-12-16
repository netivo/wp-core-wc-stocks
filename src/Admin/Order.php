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


/**
 * Handles the addition and management of the "realisation time" functionality in WooCommerce orders.
 *
 * This class integrates custom meta boxes to display and save "realisation time" for WooCommerce orders,
 * providing support for custom order tables if enabled.
 */
class Order {


	/**
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'realisation_time_metabox' ] );
		add_action( 'save_post', [ $this, 'save_realisation_time' ] );
		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_realisation_time' ] );
	}


	/**
	 * Adds a custom meta box for order realisation time to the specified screen.
	 *
	 * @return void
	 */
	public function realisation_time_metabox(): void {
		$screen = OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'nt-order-realisation-time', 'Termin realizacji', [
			$this,
			'render_realisation_time'
		], $screen, 'side', 'core' );
	}

	/**
	 * Renders the realisation time information within an admin order view.
	 *
	 * @param WP_Post|WC_Order $post_or_order_object The current post object or WooCommerce order object.
	 *
	 * @return void
	 */
	public function render_realisation_time( $post_or_order_object ): void {
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


	/**
	 * Saves the realisation time for a WooCommerce order.
	 *
	 * @param int $order_id The ID of the order being updated.
	 *
	 * @return int The ID of the order after processing.
	 */
	public function save_realisation_time( $order_id ): int {
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