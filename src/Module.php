<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 8.08.2025
 * Time: 14:11
 *
 */

namespace Netivo\Module\WooCommerce\Stocks;

use Netivo\Module\WooCommerce\Stocks\Admin\Stocks as AdminStocks;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Module {

	protected static ?self $instance = null;

	protected array $config = array();

	public static function get_instance(): self {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function get_config_array(): array {
		return self::get_instance()->get_config();
	}

	public static function get_file_path(): false|string|null {
		$file = realpath( __DIR__ . '/../views/' );
		if ( file_exists( $file ) ) {
			return $file;
		}

		return null;
	}

	protected function __construct() {
		$this->init_config();
	}

	public function init(): void {
		new Stocks();

		if ( is_admin() ) {
			new AdminStocks();
		}
	}

	public function init_config(): void {
		if ( file_exists( get_stylesheet_directory() . "/config/stocks.config.php" ) ) {
			$this->config = include get_stylesheet_directory() . "/config/stocks.config.php";
		}
	}

	public function get_config(): array {
		return $this->config;
	}

	protected function __clone() {
	}
}