=== Archive NBP ===
 
Contributors: Igvar
Tags: NBP, kursy walut, exchange rates, Narodowy Bank Polski, Archive
Requires at least: 6.0
Tested up to: 6.6.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin to collect currency rates from Bank of Poland. Allow to view rates as Gutenberg blocks and REST API endpoints from local database.
 
== Description ==
 
This plugin collect and allow to view historical currency rates from Bank of Poland by using custom Gutenberg Blocks and REST API. 
The plugin use Narodowy Bank Polski public API for collecting the rates.
Endpoint: https://api.nbp.pl/api/exchangerates/tables/a
Bank of Poland API description and terms of use: https://api.nbp.pl/en.html
After plugin installation your site will provide Bank of Poland historical data using your local database and directly from your domain as REST API service (see FAQ about endpoints).
 
== Installation ==
 
1. Upload the plugin folder to your /wp-content/plugins/ folder.
2. Go to the **Plugins** page and activate the plugin.
 
== Frequently Asked Questions ==
 
= Which API endpoints can I use after installation =
1. List of currencies rates for available needed date and date before it. 
Example: https://your-domain-name.com/wp-json/archive-nbp/v1/date-rates/YYYY-MM-DD/AAA,BBB
Where: YYYY - year; MM-month; DD-day; AAA,BBB -  ISO 4217 currency codes, for example - USD,EUR 
2. List of rates for needed currency and dates range.
Example: https://your-domain-name.com/wp-json/archive-nbp/v1/currencies-period/YYYY-MM-DD/YYYY-MM-DD/AAA
Where: YYYY - year; MM-month; DD-day; AAA,BBB -  ISO 4217 currency code, for example - USD

= Which Gutenberg Blocks can I use after installation =
1. Currency Chart - show rates for needed currency and dates range as a chart. If you want to have the last available date on the chart always then set Date end parameter far in the future.
2. Last Rates - show rates for needed date. Set the date far in the future if you want to show last rates always.
3. Currency Converter - allow to calculate any amount in different currencies using last available rates in the table.

= Are any translation available for the plugin? =
Plugin support 2 languages: English and Polish.

= How to uninstall the plugin? =
Simply deactivate and delete the plugin. 
 
== Screenshots ==

1. List of available Gutenberg blocks.
2. Archive NBP Currency Chart Block (admin area).
3. Archive NBP Currency Chart Block (public area).
4. Archive NBP Currency Rates Block (admin area).
5. Archive NBP Currency Rates Block (public area).
6. Archive NBP Currency Converter Block (admin area).
7. Archive NBP Currency Converter Block (public area).
 
 
== Changelog ==
= 1.0 =
* Plugin released.