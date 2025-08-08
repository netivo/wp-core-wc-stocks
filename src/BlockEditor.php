<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 8.08.2025
 * Time: 15:23
 *
 */

namespace Netivo\Module\WooCommerce\Stocks;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class BlockEditor {

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_blocks' ) );
		add_action( 'woocommerce_block_template_register', array( $this, 'add_blocks_to_editor' ) );
	}

	public function initialize_blocks(): void {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-admin' ) {
			BlockRegistry::get_instance()->register_block_type_from_metadata( get_stylesheet_directory() . '/dist/gutenberg/stocks/' );
		}
	}

	public function add_blocks_to_editor( BlockTemplateInterface $template ): void {
		if ( $template instanceof ProductFormTemplateInterface && 'simple-product' === $template->get_id() ) {
			$section = $template->get_section_by_id( 'product-inventory-section' );
			$section->add_block(
				array(
					'id'         => 'netivo/stocks',
					'order'      => 15,
					'blockName'  => 'netivo/stocks',
					'attributes' => [
						'stock_name' => 'Example Block Name',
					]
				)
			);
		}
	}
}