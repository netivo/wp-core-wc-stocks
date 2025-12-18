<?php
/**
 * Created by Netivo for alchi
 * User: manveru
 * Date: 4.08.2025
 * Time: 16:44
 *
 */

namespace Netivo\Module\WooCommerce\Stocks;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Class Stocks
 *
 * Manages stock behavior modifications in WooCommerce. Provides methods to calculate total stock quantity
 * and determine stock status by incorporating additional stock sources and configurations.
 */
class Stocks {

	public function __construct() {
		$this->init_actions();
	}

	/**
	 * Initializes the necessary actions and filters for modifying WooCommerce stock behavior.
	 *
	 * @return void
	 */
	protected function init_actions(): void {
		if ( ! empty( Module::get_config_array() ) ) {
			add_filter( 'woocommerce_product_get_stock_quantity', [ $this, 'product_get_stock' ], 10, 2 );
			add_filter( 'woocommerce_product_get_stock_status', [ $this, 'product_get_stock_status' ], 10, 2 );
			add_filter( 'woocommerce_hold_stock_for_checkout', '__return_false', 10, 2 );
		}
	}

	/**
	 * Calculates the total stock quantity of a product, including additional configured stock sources.
	 *
	 * @param mixed $value The initial stock quantity or value to be evaluated.
	 * @param WC_Product $product The product object for which the total stock is being calculated.
	 *
	 * @return int The calculated total stock quantity of the product.
	 */
	public function product_get_stock( mixed $value, WC_Product $product ): ?int {
		if ( ( ! is_admin() || wp_doing_ajax() ) && ! wp_doing_cron() ) {
			$stock = wc_stock_amount( $value );

			$final_stock = $stock;

			foreach ( Module::get_config_stocks() as $id => $stk ) {
				if ( ! empty( $stk['manage'] ) ) {
					$ex_stock = $product->get_meta( '_ex_stock_' . $id, true );
					$ex_stock = wc_stock_amount( $ex_stock );

					$final_stock += $ex_stock;
				}
			}

			return $final_stock;
		}

		return $value;
	}

	/**
	 * Determines the stock status of a product based on its current stock levels and additional configuration.
	 *
	 * @param mixed $value The initial stock value or status to be processed.
	 * @param WC_Product $product The product object for which the stock status is being determined.
	 *
	 * @return string The stock status of the product. Possible values are 'instock', 'outofstock', or 'onbackorder'.
	 */
	public function product_get_stock_status( mixed $value, WC_Product $product ): string {
		$stock = wc_stock_amount( $product->get_stock_quantity( 'normal' ) );

		$final_stock = $stock;

		foreach ( Module::get_config_stocks() as $id => $stk ) {
			if ( ! empty( $stk['manage'] ) ) {
				$ex_stock = $product->get_meta( '_ex_stock_' . $id, true );
				$ex_stock = wc_stock_amount( $ex_stock );

				$final_stock += $ex_stock;
			}
		}

		$out = ( $product->get_backorders() === 'no' ) ? 'outofstock' : 'onbackorder';

		return ( $final_stock > 0 ) ? 'instock' : $out;
	}
}