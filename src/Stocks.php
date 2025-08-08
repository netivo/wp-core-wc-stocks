<?php
/**
 * Created by Netivo for alchi
 * User: manveru
 * Date: 4.08.2025
 * Time: 16:44
 *
 */

namespace Netivo\Module\WooCommerce\Stocks;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Stocks {

	public function __construct() {
		$this->init_actions();
	}

	protected function init_actions(): void {
		if ( ! empty( Module::get_config_array() ) ) {
			add_filter( 'woocommerce_product_get_stock_quantity', [ $this, 'product_get_stock' ], 10, 2 );
			add_filter( 'woocommerce_product_get_stock_status', [ $this, 'product_get_stock_status' ], 10, 2 );
		}
	}

	public function product_get_stock( $value, $product ) {
		if ( ( ! is_admin() || wp_doing_ajax() ) && ! wp_doing_cron() ) {
			$stock = wc_stock_amount( $value );

			$final_stock = $stock;

			foreach ( Module::get_config_array() as $id => $stk ) {
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

	public function product_get_stock_status( $value, $product ): string {
		$stock = wc_stock_amount( $value );

		$final_stock = $stock;

		foreach ( Module::get_config_array() as $id => $stk ) {
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