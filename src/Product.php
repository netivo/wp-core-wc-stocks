<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 13.08.2025
 * Time: 12:55
 *
 */

namespace Netivo\Module\WooCommerce\Stocks;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Class Product
 *
 * Provides functionality for calculating and retrieving product realization times
 * based on stock levels and other configurable parameters.
 */
class Product {

	protected static array $cache = array();

	/**
	 * Retrieves the realization time for a product based on quantity and output type.
	 *
	 * @param WC_Product $product The WooCommerce product for which the realization time is calculated.
	 * @param int $qty The quantity of the product considered for the realization time (default is 1).
	 * @param string $type The type of data to return. Allowed values are 'text', 'array', 'array_reversed', 'date', or the default type.
	 *
	 * @return mixed Returns the realization time based on the specified type. Can return a string (for 'text' or 'date'),
	 *               an array (for 'array' or 'array_reversed'), or a numeric value in other cases.
	 * @throws \DateMalformedIntervalStringException
	 */
	public static function get_realisation_time( WC_Product $product, int $qty = 1, string $type = 'text' ): mixed {
		$time_array = self::get_realisation_time_array( $product );
		if ( $type == 'array' ) {
			return $time_array;
		}
		$reversed = array();
		foreach ( $time_array as $key => $value ) {
			if ( ! array_key_exists( $value['stock'], $reversed ) ) {
				$reversed[ $value['stock'] ] = $value;
			}
		}
		ksort( $reversed );
		if ( $type == 'array_reversed' ) {
			return $reversed;
		}

		$time = null;
		foreach ( $reversed as $key => $value ) {
			if ( $qty <= $key ) {
				$time = $value['time'];
				break;
			}
		}
		if ( $time == null ) {
			$time = $time_array['backorder']['time'];
		}

		if ( $type == 'text' ) {
			return self::get_readable_realisation_time( $time );
		}

		if ( $type == 'date' ) {
			return self::get_realisation_time_date( $time );
		}

		return $time;

	}

	/**
	 * Converts a realisation time value into a human-readable format.
	 *
	 * @param int|null $r_time The realisation time in days. If null, a default value of 200 is used.
	 *                          The value 999 is treated as 0. The time ranges are converted to a string description.
	 *
	 * @return string Returns a formatted string description of the realisation time, such as "24h!" or "1-3 dni".
	 */
	public static function get_readable_realisation_time( $r_time = null ): string {
		if ( $r_time == 999 ) {
			$r_time = 0;
		}
		if ( empty( $r_time ) ) {
			$r_time = 200;
		}
		if ( $r_time == 1 ) {
			$d_time = '24h!';
		} elseif ( $r_time <= 3 ) {
			$d_time = '1-3 dni';
		} elseif ( $r_time <= 5 ) {
			$d_time = '3-5 dni';
		} elseif ( $r_time <= 7 ) {
			$d_time = 'ok. tygodnia';
		} elseif ( $r_time <= 14 ) {
			$d_time = '1-2 tygodnie';
		} elseif ( $r_time <= 21 ) {
			$d_time = '2-3 tygodnie';
		} elseif ( $r_time <= 28 ) {
			$d_time = '3-4 tygodnie';
		} elseif ( $r_time <= 35 ) {
			$d_time = '4-5 tygodni';
		} elseif ( $r_time <= 42 ) {
			$d_time = '5-6 tygodni';
		} elseif ( $r_time <= 49 ) {
			$d_time = '6-7 tygodni';
		} elseif ( $r_time <= 56 ) {
			$d_time = '7-8 tygodni';
		} elseif ( $r_time <= 70 ) {
			$d_time = 'dwa miesiące';
		} elseif ( $r_time <= 100 ) {
			$d_time = 'trzy miesiące';
		} else {
			$d_time = 'Na zamówienie';
		}

		return $d_time;
	}

	/**
	 * Calculates the realisation date based on the given time in days.
	 *
	 * @param int|null $time The number of days to calculate the realisation date. If $time is 999, returns null.
	 *
	 * @return \DateTime|null Returns a \DateTime object representing the calculated realisation date, or null if $time is 999 or not provided.
	 * @throws \DateMalformedIntervalStringException
	 */
	public static function get_realisation_time_date( ?int $time ): ?\DateTime {
		if ( $time == 999 ) {
			return null;
		}
		if ( ! empty( $time ) ) {
			$now = new \DateTime();
			$nn  = clone( $now );
			$now->add( new \DateInterval( 'P' . $time . 'D' ) );
			if ( $time > 1 && $time <= 5 ) {
				$wd = (int) $nn->format( 'N' );
				if ( $wd < 6 ) {
					$dif = $wd + $time;
					if ( $dif >= 6 ) {
						$now->add( new \DateInterval( 'P2D' ) );
					}
				} elseif ( $wd == 6 ) {
					$now->add( new \DateInterval( 'P1D' ) );
				}
			} else {
				$wd = $now->format( 'w' );
				if ( $wd == 0 || $wd == 6 ) {
					$now->add( new \DateInterval( 'P' . ( $wd == 0 ? 1 : 2 ) . 'D' ) );
				}
			}

			return $now;
		}

		return null;
	}

	/**
	 * Calculates and returns an array representing realisation times and stock quantities for a given product.
	 *
	 * This method computes the available stock levels and corresponding realisation times
	 * based on the product's own stock, external stock configuration, and other metadata.
	 * It caches the computed data to improve performance for subsequent calls.
	 *
	 * @param WC_Product $product The WooCommerce product object for which to calculate realisation times.
	 *
	 * @return array An associative array where the keys are the realisation times and the values
	 *               are arrays containing 'stock' and 'time'. Includes a 'backorder' entry for the default
	 *               realisation time and stock.
	 */
	protected static function get_realisation_time_array( WC_Product $product ): array {
		if ( ! empty( self::$cache[ $product->get_id() ] ) ) {
			return self::$cache[ $product->get_id() ];
		}
		$own_stock        = (float) $product->get_stock_quantity( 'own' );
		$r_t              = $product->get_meta( '_realisation_time' );
		$realisation_time = ( ! empty( $r_t ) ) ? (int) $r_t : 999;

		$stock_divider = (float) apply_filters( 'netivo/stocks/stock_divider', 1 );
		$stocks        = [];

		if ( $own_stock > 0 ) {
			$stocks['own'] = [
				'stock' => floor( $own_stock / $stock_divider ),
				'time'  => 1
			];
		}

		if ( ! empty( Module::get_config_stocks() ) ) {
			foreach ( Module::get_config_stocks() as $id => $stk ) {
				$e_stock = (float) $product->get_meta( '_ex_stock_' . $id );
				$e_time  = $product->get_meta( '_ex_time_' . $id );
				$d_time  = ( ! empty( $stk['default_time'] ) ) ? $stk['default_time'] : 999;
				if ( $e_stock > 0 ) {
					$stocks[ $id ] = [
						'stock' => floor( $e_stock / $stock_divider ),
						'time'  => ( ! empty( $e_time ) ) ? (int) $e_time : $d_time
					];
				}
			}
		}

		$result = array();

		if ( ! empty( $stocks ) ) {
			foreach ( $stocks as $stck ) {
				if ( $stck['stock'] > 0 ) {
					if ( $stck['time'] != 999 ) {
						if ( ! array_key_exists( $stck['time'], $result ) ) {
							$result[ $stck['time'] ] = [
								'stock' => 0,
								'time'  => $stck['time'],
							];
						}
						$result[ $stck['time'] ]['stock'] += $stck['stock'];
					}
				}
			}
			ksort( $result );
		}
		$result['backorder'] = [
			'stock' => - 999,
			'time'  => $realisation_time,
		];

		$prev = 0;
		foreach ( $result as $key => &$res ) {
			if ( $key !== 'backorder' ) {
				$prev         += $res['stock'];
				$res['stock'] = $prev;
			}
		}

		self::$cache[ $product->get_id() ] = $result;

		return $result;
	}
}