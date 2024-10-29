<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date_begin = explode('T', $attributes['date_begin'])[0];
$date_end = explode('T', $attributes['date_end'])[0];
$anbp = \ANBP\Archive_NBP::anbp_get_instance();

if( strtotime( $anbp->anbp_get_begin_date() ) > strtotime( $date_begin ) ){
	$date_begin = $anbp->anbp_get_begin_date();
}
if( strtotime( $date_end ) > strtotime( $anbp->today_date ) ){
	$date_end = $anbp->today_date;
}
if( strtotime( $date_end ) < strtotime( $date_begin ) ){
	$date_begin = $anbp->anbp_get_begin_date();
	$date_end = $anbp->today_date;
}

$api_url = get_rest_url() . 'archive-nbp/v1/currencies-period/' . $date_begin . '/' . $date_end . '/' . $attributes['currency'];
$wp_http = new WP_Http();
$request = $wp_http->request($api_url, ['sslverify' => false]);
$body = json_decode($request['body'], true);
$rates['rates'] = $body['rates'];
$rates['label'] =  $attributes['currency'] . __(  ' to ', 'archive-nbp') . $anbp->base_currency;
wp_localize_script( 'anbp-blocks-currency-chart-view-script', 'ratesData', $rates );
?>

<div <?php echo esc_attr( get_block_wrapper_attributes() ); ?>>
	<h2><?php echo esc_html( '1 ' . $attributes['currency'] . __( ' to ', 'archive-nbp' ) . $anbp->base_currency . __( ' Currency Rates', 'archive-nbp' ) ); ?></h2>
	<canvas id="anbp-rates-chart"></canvas>
</div>