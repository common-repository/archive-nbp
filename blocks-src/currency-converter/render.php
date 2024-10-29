<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currencies = implode(',', $attributes['selectedCurrencies']);
$anbp = \ANBP\Archive_NBP::anbp_get_instance();
$date_rate = $anbp->today_date;

$api_url = get_rest_url() . 'archive-nbp/v1/date-rates/' . $date_rate . '/' . $currencies;
$wp_http = new WP_Http();
$request = $wp_http->request($api_url, ['sslverify' => false]);
$body = json_decode($request['body'], true);
$rates = $anbp->anbp_get_conversion_rates( $body['rates'], $body['dates_to_show'][0] );
wp_localize_script( 'anbp-blocks-currency-converter-view-script', 'conversionData', $rates );

?>
<div <?php echo esc_attr( get_block_wrapper_attributes() ) ?>>
	<h2><?php echo esc_html( __( ' Exchange Converter, ', 'archive-nbp' ) . $date_rate . '.' ); ?></h2>
	<div id="converter-rates"></div>
</div>

