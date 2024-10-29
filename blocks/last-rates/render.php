<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date_rate = explode('T', $attributes['rateDate'])[0];
$currencies = implode(',', $attributes['selectedCurrencies']);
$anbp = \ANBP\Archive_NBP::anbp_get_instance();
if( strtotime( $date_rate ) > strtotime( $anbp->today_date ) ){
	$date_rate = $anbp->today_date;
}
$api_url = get_rest_url() . 'archive-nbp/v1/date-rates/' . $date_rate . '/' . $currencies;
$wp_http = new WP_Http();
$request = $wp_http->request($api_url, ['sslverify' => false]);
$body = json_decode($request['body'], true);

?>
<div <?php echo esc_attr( get_block_wrapper_attributes() ) ?>>
	<h2><?php echo esc_html($anbp->base_currency . __( ' Exchange Rates, ', 'archive-nbp' ) . $date_rate . '.') ?></h2>
	<table class="current-rates">
		<tr>
			<th>&nbsp;</th>	
			<th><?php echo esc_html( $body['dates_to_show'][0] ) ?></th>
			<th><?php echo esc_html( $body['dates_to_show'][1] ) ?></th>
			<th>&nbsp;</th>
		</tr>
		<?php 
		foreach($attributes['selectedCurrencies'] as $currency) { 
			$currency_rates = $anbp->anbp_get_currency_rates( $body['rates'], $body['dates_to_show'][0], $body['dates_to_show'][1], $currency );
			$row_class = 'diff-arrow-up';
			$precision = in_array($currency, ['HUF', 'JPY', 'ISK', 'CLP', 'IDR', 'INR', 'KRW']) ? 6 : 4;
			if($currency_rates['diff'] < 0){
				$row_class = 'diff-arrow-down';
			}
		?>
			<tr>
				<td><?php echo esc_html( $currency ) ?></td>
				<td><?php echo esc_html( number_format( $currency_rates['rate1'], $precision ) ) ?></td>
				<td><?php echo esc_html( number_format( $currency_rates['rate2'], $precision ) ) ?></td>
				<td><span class="<?php echo esc_attr( $row_class ) ?>"><?php echo esc_html( $currency_rates['diff'] ) ?>%</span></td>
			</tr>
		<?php } ?>
	</table>
</div>

