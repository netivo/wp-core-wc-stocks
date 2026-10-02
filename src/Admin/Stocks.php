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

/**
 * Manages stock-related functionalities for WooCommerce products.
 */
class Stocks {

	public function __construct() {
		$this->init_admin_fields();
	}

	/**
	 * Initializes the custom admin fields for WooCommerce products.
	 *
	 * @return void
	 */
	protected function init_admin_fields(): void {
		add_action( 'woocommerce_product_options_stock_fields', [ $this, 'display_stock_quantity_options' ] );

		add_action( 'save_post', [ $this, 'product_data_save' ] );

		add_action( 'woocommerce_variation_options_inventory', [ $this, 'display_variation_stock_quantity_options' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'variation_data_save' ], 10, 2 );
	}

	/**
	 * Displays stock quantity options in the admin product interface.
	 *
	 * Includes the required template file for rendering stock quantity options based on the provided configuration.
	 *
	 * @return void
	 */
	public function display_stock_quantity_options(): void {
		global $post, $thepostid, $product_object;

		$config = Module::get_config_stocks();

		$filename = Module::get_module_path() . '/views/admin/product/stock-quantity.phtml';

		include $filename; //phpcs:ignore
	}

	/**
	 * Displays stock quantity options in the inventory section of a product variation.
	 *
	 * @param int $loop The index of the variation in the variations list.
	 * @param array $variation_data The variation data.
	 * @param \WP_Post $variation The variation post object.
	 *
	 * @return void
	 */
	public function display_variation_stock_quantity_options( int $loop, array $variation_data, \WP_Post $variation ): void {
		$variation_object = wc_get_product( $variation->ID );
		if ( empty( $variation_object ) ) {
			return;
		}

		$config = Module::get_config_stocks();

		$filename = Module::get_module_path() . '/views/admin/product/variation-stock-quantity.phtml';

		include $filename; //phpcs:ignore
	}

	/**
	 * Saves external stock data and associated meta fields for a product variation.
	 *
	 * Nonce and capability checks are performed by WooCommerce before this action is fired.
	 *
	 * @param int $variation_id The ID of the variation being saved.
	 * @param int $i The index of the variation in the submitted data.
	 *
	 * @return void
	 */
	public function variation_data_save( int $variation_id, int $i ): void {
		foreach ( Module::get_config_stocks() as $id => $stk ) {
			if ( empty( $stk['manage'] ) ) {
				continue;
			}
			$meta_key      = '_ex_stock_' . $id;
			$meta_key_sync = '_ex_sync_' . $id;
			$meta_key_rt   = '_ex_time_' . $id;

			if ( isset( $_POST[ 'variable' . $meta_key ][ $i ] ) ) {
				update_post_meta( $variation_id, $meta_key, wc_stock_amount( sanitize_text_field( $_POST[ 'variable' . $meta_key ][ $i ] ) ) );
			}

			if ( ! empty( $stk['synchronize'] ) ) {
				$sync = isset( $_POST[ 'variable' . $meta_key_sync ][ $i ] ) ? sanitize_text_field( $_POST[ 'variable' . $meta_key_sync ][ $i ] ) : '';
				update_post_meta( $variation_id, $meta_key_sync, $sync );
			}
			if ( ! empty( $stk['realisation_time'] ) && isset( $_POST[ 'variable' . $meta_key_rt ][ $i ] ) ) {
				update_post_meta( $variation_id, $meta_key_rt, sanitize_text_field( $_POST[ 'variable' . $meta_key_rt ][ $i ] ) );
			}
		}

		if ( Module::is_realisation_time_enabled() && isset( $_POST['variable_realisation_time'][ $i ] ) ) {
			update_post_meta( $variation_id, '_realisation_time', sanitize_text_field( $_POST['variable_realisation_time'][ $i ] ) );
		}
	}

	/**
	 * Saves product stock data and associated meta fields for a given product.
	 *
	 * Validates the request, checks user permissions, and updates custom stock-related
	 * meta fields based on the submitted data and configuration.
	 *
	 * @param string $post_id The ID of the product post being saved.
	 *
	 * @return string The post ID after processing the save operation.
	 */
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

			if ( ! empty( $stk['manage'] ) && ! empty( $stk['synchronize'] ) ) {
				$sync = isset( $_POST[ $meta_key_sync ] ) ? sanitize_text_field( $_POST[ $meta_key_sync ] ) : '';
				update_post_meta( $post_id, $meta_key_sync, $sync );
			}
			if ( ! empty( $stk['realisation_time'] ) && isset( $_POST[ $meta_key_rt ] ) ) {
				update_post_meta( $post_id, $meta_key_rt, sanitize_text_field( $_POST[ $meta_key_rt ] ) );
			}
		}

		if ( Module::is_realisation_time_enabled() ) {
			update_post_meta( $post_id, '_realisation_time', sanitize_text_field( $_POST['_realisation_time'] ) );
		}

		return $post_id;
	}
}