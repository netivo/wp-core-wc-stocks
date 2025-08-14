<?php
/**
 * Created by Netivo for alchi
 * User: manveru
 * Date: 5.08.2025
 * Time: 12:05
 *
 */

namespace Netivo\Module\WooCommerce\Stocks\Admin;

use Automattic\WooCommerce\Admin\API\AI\Product;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;
use Netivo\Module\WooCommerce\Stocks\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Stocks {

	public function __construct() {
		$this->init_admin_fields();
	}

	protected function init_admin_fields(): void {
		add_action( 'woocommerce_product_options_stock_fields', [ $this, 'display_stock_quantity_options' ] );

		add_action( 'save_post', [ $this, 'product_data_save' ] );
	}

	public function display_stock_quantity_options(): void {
		global $post, $thepostid, $product_object;

		$config = Module::get_config_stocks();

		$filename = Module::get_module_path() . '/views/admin/product/stock-quantity.phtml';

		include $filename; //phpcs:ignore
	}

	public function product_data_save( string $post_id ): string {
		if ( ! isset( $_POST['ex_stock_quantity_nonce'] ) ) {
			return $post_id;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['ex_stock_quantity_nonce'] ), 'save_ex_stock_quantity' ) ) {
			return $post_id;
		}

		if ( empty( $_POST['post_type'] ) ) {
			return $post_id;
		}

		if ( $_POST['post_type'] !== 'product' ) {
			return $post_id;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		foreach ( Module::get_config_stocks() as $id => $stk ) {
			$meta_key      = '_ex_stock_' . $id;
			$meta_key_sync = '_ex_sync_' . $id;
			$meta_key_rt   = '_ex_time_' . $id;

			if ( isset( $_POST[ $meta_key ] ) ) {
				update_post_meta( $post_id, $meta_key, wc_stock_amount( sanitize_text_field( $_POST[ $meta_key ] ) ) );
			}

			if ( ! empty( $stk['synchronize'] ) && isset( $_POST[ $meta_key_sync ] ) ) {
				update_post_meta( $post_id, $meta_key_sync, sanitize_text_field( $_POST[ $meta_key_sync ] ) );
			}
			if ( ! empty( $stk['realisation_time'] ) && isset( $_POST[ $meta_key_rt ] ) ) {
				update_post_meta( $post_id, $meta_key_rt, sanitize_text_field( $_POST[ $meta_key_rt ] ) );
			}
		}

		return $post_id;
	}
}