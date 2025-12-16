<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 13.08.2025
 * Time: 12:54
 *
 */

namespace Netivo\Module\WooCommerce\Stocks;

use Netivo\Module\WooCommerce\Stocks\Admin\Order as AdminOrder;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Order {

	public function __construct() {
		add_action( 'woocommerce_checkout_order_created', [ $this, 'add_order_realisation_time' ], 20, 1 );
		add_action( 'woocommerce_review_order_before_shipping', [ $this, 'show_realisation_time_on_review' ], 10, 1 );

		add_filter( 'woocommerce_get_order_item_totals', [ $this, 'add_realisation_time_to_order_totals' ], 10, 2 );
		add_filter( 'woocommerce_order_item_meta_start', [ $this, 'add_realisation_time_to_order_item' ], 10, 3 );

		if ( is_admin() ) {
			new AdminOrder();
		}
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @throws \DateMalformedIntervalStringException
	 */
	public function add_order_realisation_time( \WC_Order $order ): void {
		$items = $order->get_items();
		$time  = null;

		$no_date = false;
		foreach ( $items as $item ) {
			if ( $item->get_type() == 'line_item' ) {
				$product = $item->get_product();
				$lqty    = $item->get_quantity();

				$realisation_time = Product::get_realisation_time( $product, $lqty, 'date' );
				if ( ! empty( $realisation_time ) ) {
					$item->update_meta_data( '_realisation_time', $realisation_time->format( 'Y-m-d' ) );

					if ( $time !== null && $time < $realisation_time ) {
						$time = $realisation_time;
					} elseif ( $time === null ) {
						$time = $realisation_time;
					}
				} else {
					$no_date = true;
				}
			}
		}
		if ( ! empty( $time ) && ! $no_date ) {
			$order->update_meta_data( '_realisation_time', $time->format( 'Y-m-d' ) );
			$order->save();
		}
	}

	/**
	 * @throws \DateMalformedIntervalStringException
	 */
	public function show_realisation_time_on_review(): void {
		$times   = array();
		$no_date = false;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			$lqty = $cart_item['quantity'];

			$r_time = Product::get_realisation_time( $_product, $lqty, 'days' );

			if ( ! empty( $r_time ) ) {
				$times[] = (int) $r_time;
			} else {
				$no_date = true;
			}
		}

		if ( ! $no_date ) {
			$t      = max( $times );
			$s_time = Product::get_readable_realisation_time( $t );
		} else {
			$s_time = 'Na zamówienie';
		}


		$filename = Module::get_module_path() . '/views/checkout/realisation-time.php';

		include $filename; //phpcs:ignore

	}

	public function add_realisation_time_to_order_totals( $totals, $order ): array {
		$time       = $order->get_meta( '_realisation_time' );
		$new_totals = array();
		foreach ( $totals as $key => $total ) {
			$new_totals[ $key ] = $total;
			if ( $key == 'shipping' ) {
				$new_totals['realisation_time'] = array(
					'type'  => 'realisation_time',
					'label' => __( 'Przewidywany termin realizacji:', 'netivo' ),
					'value' => ( ! empty( $time ) ) ? $time : __( 'Na zamówienie', 'netivo' ),
				);
			}
		}

		return $new_totals;
	}

	public function add_realisation_time_to_order_item( $item_id, $item, $order ): void {
		if ( Module::is_realisation_time_line_enabled() ) {
			$time = $item->get_meta( '_realisation_time' );
			if ( ! empty( $time ) ) {
				echo wp_kses_post( '<p style="display: block;"><strong>' . __( 'Przewidywany termin realizacji:', 'netivo' ) . '</strong> ' . $time . '</p>' );
			}
		}
	}
}