<?php
/**
 * Created by Netivo for modules
 * User: manveru
 * Date: 14.08.2025
 * Time: 11:08
 *
 * @var $s_time string
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

?>
<tr class="cart-shipping-time">
    <th><?php _e( 'Przewidywany czas wysyłki', 'netivo' ); ?></th>
    <td data-title="<?php echo __( 'Przewidywany czas wysyłki', 'netivo' ); ?>">
		<?php echo esc_html( $s_time ); ?>
    </td>
</tr>
