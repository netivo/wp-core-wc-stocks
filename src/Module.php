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

/**
 * Singleton class responsible for handling the module's core functionality
 * and configuration management.
 */
class Module {

	protected static ?self $instance = null;

	protected array $config = array();

	/**
	 * Retrieves the singleton instance of the class. If the instance does not already exist, it initializes a new instance.
	 *
	 * @return self The singleton instance of the class.
	 */
	public static function get_instance(): self {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieves the configuration array by accessing the singleton instance.
	 *
	 * @return array The configuration array containing key-value pairs.
	 */
	public static function get_config_array(): array {
		return self::get_instance()->get_config();
	}

	/**
	 * Retrieves the stock configuration settings.
	 *
	 * @return array Returns an array containing stock configuration if available, otherwise an empty array.
	 */
	public static function get_config_stocks(): array {
		$conf = self::get_instance()->get_config();
		if ( ! empty( $conf['stocks'] ) ) {
			return $conf['stocks'];
		}

		return [];
	}

	/**
	 * Checks if the realisation time feature is enabled in the configuration.
	 *
	 * @return bool Returns true if the realisation time is enabled, otherwise false.
	 */
	public static function is_realisation_time_enabled(): bool {
		$conf = self::get_instance()->get_config();

		return ( ! empty( $conf['realisation_time'] ) );
	}

	/**
	 * Determines if the realisation timeline feature is enabled based on the configuration.
	 *
	 * @return bool Returns true if the realisation timeline is enabled, otherwise false.
	 */
	public static function is_realisation_time_line_enabled(): bool {
		$conf = self::get_instance()->get_config();

		return ( ! empty( $conf['realisation_time_line'] ) );
	}

	/**
	 * Retrieves the absolute path to the module directory.
	 *
	 * @return false|string|null Returns the absolute path to the module directory if it exists,
	 *                           null if the file does not exist, or false on failure.
	 */
	public static function get_module_path(): false|string|null {
		$file = realpath( __DIR__ . '/../' );
		if ( file_exists( $file ) ) {
			return $file;
		}

		return null;
	}

	protected function __construct() {
		$this->init_config();
	}

	/**
	 * Initializes the necessary components for stock management.
	 * This includes creating a new instance of the Stocks class,
	 * conditionally initializing an Order instance if realisation time is enabled,
	 * and setting up AdminStocks if the current context is an admin environment.
	 *
	 * @return void
	 */
	public function init(): void {
		new Stocks();

		if ( self::is_realisation_time_enabled() ) {
			new Order();
		}

		if ( is_admin() ) {
			new AdminStocks();
		}
	}

	/**
	 * Loads the configuration file for stock management if it exists.
	 * The configuration is fetched from the "config/stocks.config.php" file located in the current theme's directory
	 * and stored in the `$config` property.
	 *
	 * @return void
	 */
	public function init_config(): void {
		if ( file_exists( get_stylesheet_directory() . "/config/stocks.config.php" ) ) {
			$this->config = include get_stylesheet_directory() . "/config/stocks.config.php";
		}
	}

	/**
	 * Retrieves the configuration settings.
	 * This method returns an array containing the configuration values
	 * stored within the class instance.
	 *
	 * @return array The configuration settings.
	 */
	public function get_config(): array {
		return $this->config;
	}

	protected function __clone() {
	}
}