# Netivo WP Core Woocommerce Stocks Module

This module adds option to define external stock management for products.

## Usage

In the main theme directory add config directory and inside create a file: `stocks.config.php`

Which must return array with structure:

```php  
return array(
    'realisation_time' => true | false, // enable realisation times for products and orders
    'stocks' => array(
        '[stock_id]' => array (
            'name' => [stock name],
            'manage'           => true | false, // enable stock management
            'synchronize'      => true | false, // is there a synchronization for stock
            'realisation_time' => true | false // can user specify custom realisation time for stock
        )
    )
);
```