<?php
namespace ANBP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Archive_NBP' ) ) {
    class Archive_NBP {

        private static $instance = null;
        private $rates_table_name;
        private $currencies_table_name;
        private $exchange_rates_uri = 'https://api.nbp.pl/api/exchangerates/tables/a';
        private static $archive_begin_date = '2002-01-01';
        private $archive_end_date = '2002-01-01';
        private $currencies_list = [];
		public $today_date;
		public $base_currency = 'PLN';

        private function __construct() {
            global $wpdb;
            $this->rates_table_name = $wpdb->prefix . 'nbp_rates';
            $this->currencies_table_name = $wpdb->prefix . 'nbp_currencies';
            $this->today_date = gmdate( 'Y-m-d', time() );
            $this->currencies_list = $this->anbp_get_currencies_list();

            register_activation_hook( ANBP_FILE_PATH, [ $this, 'anbp_install' ] );
            register_deactivation_hook( ANBP_FILE_PATH, [ $this, 'anbp_uninstall' ] );

            add_action( 'rest_api_init', [ $this, 'anbp_register_rest_routes' ] );
            add_action( 'init', [ $this, 'anbp_rates_update' ] );
            add_action( 'plugins_loaded', [ $this, 'anbp_load_textdomain' ] );
            add_action( 'init', [ $this, 'anbp_blocks_init' ] );
            add_filter( 'block_categories_all', [ $this, 'anbp_blocks_category' ], 10, 2 );
        }

        public static function anbp_get_instance() {
            if ( null == self::$instance ) {
                self::$instance = new self;
            }
            return self::$instance;
        }
		
		public static function anbp_get_begin_date() {
			return self::$archive_begin_date;
		}

        public function anbp_install() {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();

            $sql_1 = "CREATE TABLE $this->rates_table_name (
                id int NOT NULL AUTO_INCREMENT,
                currency_id int NOT NULL,
                exchange_rate float NOT NULL,
                exchange_date date NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY currency_date_idx (currency_id, exchange_date)
            ) $charset_collate;";

            $sql_2 = "CREATE TABLE $this->currencies_table_name (
                id int NOT NULL AUTO_INCREMENT,
                currency_name varchar(64) NOT NULL,
                currency_code varchar(4) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY currency_code_idx (currency_code)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_1 . $sql_2 );

            $today = $this->anbp_get_nbp_today_currencies();
            foreach($today['rates'] as $currency){
                $wpdb->insert(
                    $this->currencies_table_name,
                    [
                        'currency_name' => $currency->currency,
                        'currency_code' => $currency->code,
                    ]
                );
            }
        }

        public function anbp_uninstall() {
            global $wpdb;
            $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $this->rates_table_name ) ); //db call ok; no-cache ok
            $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $this->currencies_table_name ) ); //db call ok; no-cache ok
        }

        public function anbp_blocks_init() {
            register_block_type( ANBP_DIR_PATH . '/blocks/currency-chart' );
            register_block_type( ANBP_DIR_PATH . '/blocks/last-rates' );
            register_block_type( ANBP_DIR_PATH . '/blocks/currency-converter' );
        }

        public function anbp_blocks_category( $categories, $post ) {
            return [
                [
                    'slug'  => 'archive-nbp',
                    'title' => __('Archive NBP', 'archive-nbp'),
                ],
                ...$categories,
            ];
        }

        public function anbp_load_textdomain(){
            $locale = determine_locale();
            if ( $locale != 'en_US' && !is_textdomain_loaded( ANBP_NAME ) ) {
                load_plugin_textdomain( ANBP_NAME, false, ANBP_NAME . '/languages/' );
            }
        }

        public function anbp_register_rest_routes() {
            register_rest_route( 'archive-nbp/v1', '/currencies', [
                'methods'  => 'GET',
                'callback' => [ $this, 'anbp_get_currencies' ],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route( 'archive-nbp/v1', '/currencies-period/(?P<start_date>[0-9-]+)/(?P<end_date>[0-9-]+)/(?P<currency>[A-Z]+)', [
                'methods'  => 'GET',
                'callback' => [ $this, 'anbp_get_rates' ],
                'permission_callback' => '__return_true',
                'args' => [
                    'start_date' => [
                        'required' => true,
                        'validate_callback' => [ $this, 'anbp_is_valid_date_param' ],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'end_date' => [
                        'required' => true,
                        'validate_callback' => [ $this, 'anbp_is_valid_date_param' ],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'currency' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]);
			
			register_rest_route( 'archive-nbp/v1', '/date-rates/(?P<date_rate>[0-9-]+)/(?P<currencies>[A-Z,]+)', [
                'methods'  => 'GET',
                'callback' => [ $this, 'anbp_get_date_rate' ],
                'permission_callback' => '__return_true',
                'args' => [
                    'date_rate' => [
                        'required' => true,
                        'validate_callback' => [ $this, 'anbp_is_valid_date_param' ],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'currencies' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]);
        }

        public function anbp_get_nbp_today_currencies() {
            $today = wp_remote_get($this->exchange_rates_uri);
            if( isset($today['response']['code']) && $today['response']['code'] === 200 ){
                $body = json_decode($today['body']);
                return [ 'date' => $body[0]->effectiveDate, 'rates' => $body[0]->rates ];
            }
            return false;
        }

        public function anbp_get_nbp_rates($start_date, $end_date) {
            $rates = wp_remote_get($this->exchange_rates_uri. '/' . $start_date . '/' . $end_date);
            if( isset($rates['response']['code']) && $rates['response']['code'] === 200 ){
                $body = json_decode( $rates['body'] );
                return $body;
            }
            return false;
        }

        public function anbp_insert_rates($nbp_rates) {
            global $wpdb;
            if( $nbp_rates ){
                $prepare_values = [];
                $values_placeholders_arr = [];
                foreach($nbp_rates as $date_rates){
                    $effective_rates = $date_rates->rates;
                    foreach($effective_rates as $rate){
                        if(isset($this->currencies_list[$rate->code]['id'])){
                            $values_placeholders_arr[] = "(%d, %s, %s)";
                            $prepare_values = array_merge( $prepare_values, [$this->currencies_list[$rate->code]['id'], $rate->mid, $date_rates->effectiveDate] );
                        }
                    }
                }
                if( empty( $prepare_values ) ){
                    return new \WP_Error( 'anbp_insert_rates', __( 'There aren\'t NBP rates for insert to local database.', 'archive-nbp' ), [ 'status' => 400 ] );
                } else{
                    $values_placeholders = implode( ', ', $values_placeholders_arr );
                    $prepare_values = array_merge([$this->rates_table_name], $prepare_values);

                    $wpdb->query(
                        $wpdb->prepare( 
                            "INSERT INTO %i (currency_id, exchange_rate, exchange_date)
                            VALUES $values_placeholders
                            ON DUPLICATE KEY UPDATE
                            exchange_rate = VALUES(exchange_rate),
                            exchange_date = VALUES(exchange_date);",
                            $prepare_values
                        )
                    ); //db call ok; no-cache ok
                }
            }
        }

        public function anbp_rates_update(){
            $start_date = $this->anbp_get_last_rates();
            $end_date = $this->today_date;
            $start_date_obj = new \DateTime($start_date);
            $end_date_obj = new \DateTime();
            $end_date_obj->setTimezone(new \DateTimeZone('GMT'));
            $start_date_of_week = $start_date_obj->format('w');
            $end_date_of_week = $end_date_obj->format('w');
            $end_date_time = $end_date_obj->format('H');
            $dates_diff = $start_date_obj->diff($end_date_obj);
            if($start_date < $end_date){
                if( $start_date_of_week == 5 && ( $end_date_of_week == 0 || $end_date_of_week == 6 ) && $dates_diff->days < 3 ){
                    return false;
                }
                if($end_date_time < 10 && ( $dates_diff->days == 1 || ( $dates_diff->days == 3 && $end_date_of_week == 1) ) ){
                    // TODO:
                    // NBP update the rates about 9.45AM GMT. Maybe need to adjust the time to check rates... 
                    return false;
                }
                $intervals = $this->anbp_get_dates_intervals( $start_date, $end_date );

                for($i=0; $i<count($intervals); $i++){
                    $nbp_rates = $this->anbp_get_nbp_rates( $intervals[$i]['start'], $intervals[$i]['end'] );
                    $this->anbp_insert_rates($nbp_rates);
                }
            }            
        }

        public function anbp_get_currencies() {
            if( !empty( $this->currencies_list ) ) {
                return new \WP_REST_Response( $this->currencies_list, 200 );
            } else {
                return new \WP_REST_Response( 'Currencies list is empty', 400 );
            }            
        }

        public function anbp_get_rates( \WP_REST_Request $request ) {
            global $wpdb;
            
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            $currency = $request->get_param('currency');

            $results = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT exchange_rate as rate, exchange_date as date FROM %i WHERE currency_id = %d AND ( exchange_date BETWEEN %s AND %s )",
                    $this->rates_table_name,
                    intval ($this->currencies_list[$currency]['id'] ),
                    $start_date, $end_date 
                ),
                ARRAY_A
            ); //db call ok; no-cache ok

            $response = [
				'archive_begin_date' => self::$archive_begin_date,
				'archive_end_date' => $this->today_date,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'currency' => $currency,
                'currencies_list' => $this->currencies_list,
                'rates' => $results
            ];

            return new \WP_REST_Response( $response, 200 );
		}
        
		
		public function anbp_get_date_rate( \WP_REST_Request $request ) {
			global $wpdb;
			
			$date_rate = $request->get_param('date_rate');
            $currencies = $request->get_param('currencies');

			$dates = $wpdb->get_col( 
                $wpdb->prepare(
                    "SELECT exchange_date as date FROM %i WHERE exchange_date <= %s GROUP BY exchange_date DESC LIMIT %d",
                    $this->rates_table_name,
                    $date_rate,
                    2
                )
            ); //db call ok; no-cache ok
            $currencies_arr = explode(",", $currencies);

            $currencies_placeholders = implode( ', ', array_fill( 0, count( $currencies_arr ), '%s' ) );
            $selected_dates_placeholders = implode( ', ', array_fill( 0, count( $dates ), '%s' ) );
            $prepare_values = array_merge( [$this->rates_table_name, $this->currencies_table_name], $dates, $currencies_arr );

			$rates = $wpdb->get_results( 
                $wpdb->prepare("
                    SELECT r.exchange_rate as rate, r.exchange_date as date, c.currency_code
                    FROM %i as r
                    LEFT JOIN %i as c ON r.currency_id = c.id
                    WHERE r.exchange_date IN ( $selected_dates_placeholders )
                    AND c.currency_code IN ( $currencies_placeholders )",
                    $prepare_values
                ),
                ARRAY_A
            ); //db call ok; no-cache ok

			$response = [
				'date_rate' => $date_rate,
				'currencies' => $currencies,
				'dates_to_show' => $dates,
				'rates' => $rates
			];
			
			return new \WP_REST_Response( $response, 200 );
		}

        public function anbp_get_currencies_list(){
            global $wpdb;
            if( $this->anbp_table_exists($this->currencies_table_name) ) {
                $result = $wpdb->get_results(
                    $wpdb->prepare( "SELECT * FROM %i", $this->currencies_table_name ), 
                    ARRAY_A
                ); //db call ok; no-cache ok
                $currencies_list = array_map( function($item) {
                    return [ $item['currency_code'] => [ 'id' => $item['id'], 'name' => $item['currency_name'] ] ];
                }, $result);
                $currencies_list = array_reduce($currencies_list, 'array_merge', []);
                return $currencies_list;
            }
            return new \WP_Error( 'anbp_table_error', $this->currencies_table_name . __( ' table doesn\'t exist.', 'archive-nbp' ), [ 'status' => 400 ] );
        }

        public function anbp_get_last_rates(){
            global $wpdb;
            $result = $wpdb->get_var(
                $wpdb->prepare("
                    SELECT exchange_date FROM %i 
                    WHERE currency_id = %d 
                    ORDER BY exchange_date DESC LIMIT 1",
                    $this->rates_table_name,
                    $this->currencies_list['USD']['id']
                )
            );
            if($result){
                return $result;
            }
            return self::$archive_begin_date;
        }

        public function anbp_get_dates_intervals($start_date, $end_date) {
            $start = new \DateTime($start_date);
            $end = new \DateTime($end_date);
            $intervals = [];
        
            while ($start < $end) {
                $interval_end = clone $start;
                $interval_end->modify('+93 days');

                if ($interval_end > $end) {
                    $interval_end = $end;
                }

                $intervals[] = [
                    'start' => $start->format('Y-m-d'),
                    'end' => $interval_end->format('Y-m-d')
                ];

                $start = $interval_end->modify('+1 day');
            }
        
            return $intervals;
        }

        function anbp_is_valid_date_param( $param, $request, $key ) {

            $wp_error_code = 'invalid_' . $key;
            $error_label = str_replace( '_', ' ', $key);
            $start_date_param = $request->get_param('start_date');
            $end_date_param = $request->get_param('end_date');

            if( !(bool)strtotime($param) ) {
                return new \WP_Error( $wp_error_code, __( 'The ', 'archive-nbp' ) . $error_label . __(' parameter is invalid', 'archive-nbp' ), [ 'status' => 400 ] );
            } elseif( self::$archive_begin_date > $param ){
                return new \WP_Error( $wp_error_code, __( 'The ', 'archive-nbp' ) . $error_label . __(' can\'t be less than ', 'archive-nbp' ) . self::$archive_begin_date , [ 'status' => 400 ] );
            } elseif( $this->today_date < $param ){
                return new \WP_Error( $wp_error_code, __( 'The ', 'archive-nbp' ) . $error_label . __(' can\'t be more than ', 'archive-nbp' ) . $this->today_date, [ 'status' => 400 ] );
            } elseif( $this->archive_end_date > $param ){
                return new \WP_Error( $wp_error_code, __( 'The ', 'archive-nbp' ) . $error_label . __(' can\'t be less than ', 'archive-nbp' ) . $this->archive_end_date, [ 'status' => 400 ] );
            } elseif( $start_date_param > $end_date_param ){
                return new \WP_Error( $wp_error_code, __( 'The start date can\'t be more than end date', 'archive-nbp' ), [ 'status' => 400 ] );
            }

            return true;            
        }

        public function anbp_table_exists( $table ) {
            global $wpdb;
            $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s " , $table) );  //db call ok; no-cache ok
            if ( \is_wp_error( $table_exists ) || \is_null( $table_exists ) ) {
                return false;
            }
            return true;
        }

        public function anbp_get_currency_rates( $rates, $date1, $date2, $currency ){
            $rate1 = 0;
            $rate2 = 0;
            foreach( $rates as $row ){
                if( $row['currency_code'] == $currency ) {
                    if( $row['date'] == $date1 ) {
                        $rate1 = $row['rate'];
                    }
                    if( $row['date'] == $date2 ) {
                        $rate2 = $row['rate'];
                    }
                    if($rate1 > 0 && $rate2 > 0) {
                        break;
                    }
                }
            }
            if($rate1 > 0 && $rate2 > 0) {
                return [ 'rate1' => $rate1, 'rate2' => $rate2, 'diff' => number_format( round( ($rate1 - $rate2) / $rate2 * 100, 3), 3 ) ];
            }
            return false;
        }
        
        public function anbp_get_conversion_rates( $rates, $date ){
            $conversions['PLN'] = 1;
            foreach( $rates as $rate ){
                if( $rate['date'] == $date ){
                    $conversions[$rate['currency_code']] = round( 1 / $rate['rate'], 4 );
                }
            }
            return $conversions;
        }

    }

    Archive_NBP::anbp_get_instance();
}
