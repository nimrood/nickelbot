<?PHP

	class BitstampAdapter extends CryptoBase implements CryptoExchange {

		public function __construct( $Exch ) {
			$this->exch = $Exch;
		}

		private function get_market_symbol( $market ) {
			return strtoupper( substr_replace($market, '-', 3, 0) );
		}

		private function unget_market_symbol( $market ) {
			return str_replace( "-", "", strtolower( $market ) );
		}

		public function get_info() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function withdraw( $account = "exchange", $currency = "BTC", $address = "1fsdaa...dsadf", $amount = 1 ) {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function get_currency_summary( $currency = "BTC" ) {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function get_currency_summaries( $currency = "BTC" ) {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function get_order( $orderid = "1" ) {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function cancel( $orderid="1", $opts = array() ) {
			return $this->exch->cancel_order($orderid);
		}

		public function get_deposits_withdrawals() {
			$currencies = $this->get_currencies();
			$results = [];
			foreach( $currencies as $currency ) {
				$transactions = $this->get_completed_orders( $market="BTC-USD", $limit=100 );
				foreach( $transactions as $transaction ) {
					if( $transaction['type'] == 0 || $transaction['type'] == 1 ) {
						$transaction['type'] = $transaction['type'] == 0 ? "DEPOSIT" : "WITHDRAWAL"; 
						$transaction['exchange'] = "Bitstamp";
						$transaction['currency'] = $transaction['market'];
						$transaction['method'] = $transaction['market'];
						$transaction['description'] = $transaction['market'];
						$transaction['status'] = 'COMPLETED';
						$transaction['address'] = null;
						$transaction['confirmations'] = null;

						unset( $transaction['order_id'] );
						unset( $transaction['market'] );
						unset( $transaction['price'] );
						unset( $transaction['total'] );
						unset( $transaction['tid'] );
						unset( $transaction['fee_amount'] );
						unset( $transaction['fee_currency'] );
						array_push( $results, $transaction );
					}
				}
			}
			return $results;
		}

		public function get_deposits() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function get_deposit( $deposit_id="1", $opts = array() ) {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function get_withdrawals() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function cancel_all() {
			$result = $this->exch->cancel_all_orders();
			if( $result == 1 ) {
				return array( 'success' => true, 'error' => false, 'message' => $result );
			}
			return array( 'success' => false, 'error' => true, 'message' => $result );
		}

		public function buy( $pair="BTC-LTC", $amount=0, $price=0, $type="LIMIT", $opts=array() ) {
			$buy = $this->exch->buy( number_format( $amount, 8 ), $price );
			if( isset( $buy['error'] ) )
				$buy['message'] = 'ERROR';
			return $buy;
		}
		
		public function sell( $pair="BTC_LTC", $amount=0, $price=0, $type="LIMIT", $opts=array() ) {
			$sell = $this->exch->sell( number_format( $amount, 8 ), $price );
			if( isset( $sell['error'] ) )
				$sell['message'] = 'ERROR';
			return $sell;
		}

		public function get_open_orders() {
			if( isset( $this->open_orders ) )
				return $this->open_orders;
			$open_orders = $this->exch->open_orders();

			if( isset( $open_orders['error'] ) ) {
				return array( 'error' => $open_orders ); //need to standardize!
			}

			$this->open_orders = [];
			foreach( $open_orders as $open_order ) {
				$open_order['market'] = "BTC-USD";
				$open_order['timestamp_created'] = strtotime( $open_order['datetime'] . " UTC" );
				$open_order['exchange'] = "bitstamp";
				$open_order['avg_execution_price'] = null;
				$open_order['side'] = null;
				$open_order['is_live'] = null;
				$open_order['is_cancelled'] = null;
				$open_order['is_hidden'] = null;
				$open_order['was_forced'] = null;
				$open_order['original_amount'] = null;
				$open_order['remaining_amount'] = null;
				$open_order['executed_amount'] = null;
				unset( $open_order['datetime'] );
				array_push( $this->open_orders, $open_order );
			}
			return $this->open_orders;
		}

		public function get_completed_orders( $market="BTC-USD", $limit=100 ) {
			$completed_orders = $this->exch->user_transactions( array( 'offset' => 0, 'limit' => $limit, 'sort' => 'desc' ) );
			$completed_orders = [];
			foreach( $completed_orders as $completed_order ) {
				$completed_order['market'] = "BTC-USD";
				$completed_order['exchange'] = "bitstamp";
				$completed_order['timestamp'] = $completed_order['datetime'];
				$completed_order['price'] = $completed_order['btc_usd'];
				$completed_order['amount'] = $completed_order['btc'];
				$completed_order['total'] = $completed_order['usd'];

				unset( $completed_order['datetime'] );
				unset( $completed_order['btc_usd'] );
				unset( $completed_order['usd'] );
				unset( $completed_order['btc'] );

				$completed_order['tid'] = null;
				$completed_order['fee_amount'] = null;
				$completed_order['fee_currency'] = null;

				array_push( $completed_orders, $completed_order );
			}

			return $completed_orders;
		}

		//btcusd, btceur, eurusd, xrpusd, xrpeur, xrpbtc, ltcusd, ltceur, ltcbtc, ethusd, etheur, ethbtc
		public function get_markets() {
			return array( 'BTC-USD', 'BTC-EUR', 'EUR-USD', 'XRP-USD', 'XRP-EUR', 'XRP-BTC', 'LTC-USD', 'LTC-EUR', 'LTC-BTC', 'ETH-USD', 'ETH-EUR', 'ETH-BTC' );
		}

		public function get_currencies() {
			return array( 'BTC', 'USD', 'EUR', 'XRP', 'LTC', 'ETH' );
		}
		
		public function deposit_address( $currency = "BTC" ){
			return array( 'ERROR' => 'METHOD_NOT_IMPLEMENTED' );//broken
			$response = array();
			if( $currency === "BTC" ) {
				$address = $this->exch->bitcoin_deposit_address();
				$response = array( 'wallet_type' => 'exchange', 'currency' => $currency, 'address' => $address );
			}
			if( $currency === "XRP" ) {
				$address = $this->exch->ripple_address();
				$address = $address['address'];
				$response = array( 'wallet_type' => 'exchange', 'currency' => $currency, 'address' => $address );
			}
			if( $currency === "LTC" ) {
				$address = $this->exch->ltc_address();
				$address = $address['address'];
				$response = array( 'wallet_type' => 'exchange', 'currency' => $currency, 'address' => $address );
			}
			if( $currency === "ETH" ) {
				$address = $this->exch->eth_address();
				$address = $address['address'];
				$response = array( 'wallet_type' => 'exchange', 'currency' => $currency, 'address' => $address );
			}

			return $response;
		}
		
		public function deposit_addresses(){
			return array( 'ERROR' => 'METHOD_NOT_IMPLEMENTED' );//broken
			$addresses = [];
			array_push( $addresses, $this->deposit_address( "BTC" ) );
			array_push( $addresses, $this->deposit_address( "XRP" ) );
			array_push( $addresses, $this->deposit_address( "LTC" ) );
			array_push( $addresses, $this->deposit_address( "BTC" ) );
			return $addresses;
		}

		public function get_balances() {
			$response = [];

			$balances = $this->exch->balance();

			$balance['type'] = "exchange";
			$balance['currency'] = "BTC";
			$balance['available'] = $balances['btc_available'];
			$balance['total'] = $balances['btc_balance'];
			$balance['reserved'] = $balances['btc_reserved'];
			$balance['pending'] = 0;
			$balance['btc_value'] = 0;
			$response['BTC'] = $balance;

			$balance['type'] = "exchange";
			$balance['currency'] = "USD";
			$balance['available'] = $balances['usd_available'];
			$balance['total'] = $balances['usd_balance'];
			$balance['reserved'] = $balances['usd_reserved'];
			$balance['pending'] = 0;
			$balance['btc_value'] = 0;
			$response['USD'] = $balance;

			$balance['type'] = "exchange";
			$balance['currency'] = "XRP";
			$balance['available'] = $balances['xrp_available'];
			$balance['total'] = $balances['xrp_balance'];
			$balance['reserved'] = $balances['xrp_reserved'];
			$balance['pending'] = 0;
			$balance['btc_value'] = 0;
			$response['XRP'] = $balance;

			$balance['type'] = "exchange";
			$balance['currency'] = "EUR";
			$balance['available'] = $balances['eur_available'];
			$balance['total'] = $balances['eur_balance'];
			$balance['reserved'] = $balances['eur_reserved'];
			$balance['pending'] = 0;
			$balance['btc_value'] = 0;
			$response['XRP'] = $balance;

			return $response;
		}

		public function get_balance( $currency="BTC" ) {
			$balances = $this->get_balances();
			foreach( $balances as $balance )
				if( $balance['currency'] == $currency )
					return $balance;
		}

		public function get_market_summary( $market = "BTC-USD" ) {
			$market_summary = $this->exch->ticker( $this->unget_market_symbol( $market ) );

			//Set variables:
			$market_summary['market'] = $market;
			$market_summary['exchange'] = 'bitstamp';
			$market_summary['display_name'] = $market;
			$market_summary['last_price'] = $market_summary['last'];
			$market_summary['mid'] = ( $market_summary['ask'] + $market_summary['bid'] ) / 2;
			$market_summary['result'] = true;
			$market_summary['created'] = null;
			$market_summary['frozen'] = null;
			$market_summary['percent_change'] = null;
			$market_summary['verified_only'] = null;
			$market_summary['expiration'] = null;
			$market_summary['initial_margin'] = null;
			$market_summary['maximum_order_size'] = null;
			$market_summary['minimum_margin'] = null;
			$curs_bq = explode( "-", $market );
			$quote_cur = $curs_bq[1];
			$market_summary['minimum_order_size_quote'] = $this->get_min_order_size( $quote_cur ); //must be in USD...
			$market_summary['minimum_order_size_base'] = null;
			$market_summary['price_precision'] = 4;
			$market_summary['vwap'] = null;
			$market_summary['base_volume'] = $market_summary['volume'];
			$market_summary['quote_volume'] = bcmul( $market_summary['base_volume'], $market_summary['mid'], 32 );
			$market_summary['btc_volume'] = null;
			$market_summary['open_buy_orders'] = null;
			$market_summary['open_sell_orders'] = null;
			$market_summary['market_id'] = null;

			unset( $market_summary['last'] );
			unset( $market_summary['open'] );
			unset( $market_summary['volume'] );

			ksort( $market_summary );

			return $market_summary;
		}

		private function get_min_order_size( $currency ) {
			if( $currency == "USD" || $currency == "EUR" )
				return '5.55';
			else
				return '0.00222';

		}

		public function get_market_summaries() {
			$markets = $this->get_markets();
			$results = [];
			foreach( $markets as $market ) {
				array_push( $results, $this->get_market_summary( $market ) );
				sleep(1);
			}
			return $results;
		}

		public function get_trades( $market = "BTC-USD", $time = 60 ) {

			if( $time <= 60 )
				$time = 'minute';
			if( $time <= 3600 && $time > 60 )
				$time = 'hour';
			if( $time <= 3600*24 && $time > 3600 )
				$time = 'day';

			$trades = $this->exch->transactions( $time );
			$results = [];
			foreach( $trades as $trade ) {
				$trade['exchange'] = 'bitstamp';
				$trade['market'] = $market;
				$trade['timestamp'] = $trade['date'];
				unset( $trade['date'] );
				array_push( $results, $trade );
			}
			return $results;
		}

		public function get_orderbook( $market = 'BTC-USD', $depth = 0 ) {
			if( $market != 'BTC-USD' )
				return array( 'error' => true, 'message' => "Only BTC-USD is accepted" );
			$book = $this->exch->order_book();

			$results = [];
			foreach( $book['bids'] as $bid ) {
				$bid['timestamp' ] = $book['timestamp'];
				$bid['exchange'] = "bitstamp";
				$bid['market'] = $market;
				$bid['type'] = "buy";
				$bid['price'] = $bid[0];
				$bid['amount'] = $bid[1];
				unset( $bid[0] );
				unset( $bid[1] );
				array_push( $results, $bid );
			}
			foreach( $book['asks'] as $ask ) {
				$ask['timestamp' ] = $book['timestamp'];
				$ask['exchange'] = "bitstamp";
				$ask['market'] = $market;
				$ask['type'] = "sell";
				$ask['price'] = $ask[0];
				$ask['amount'] = $ask[1];
				unset( $ask[0] );
				unset( $ask[1] );
				array_push( $results, $ask );
			}
			return $results;
		}

		//Return trollbox data from the exchange, otherwise get forum posts or twitter feed if must...
		public function get_trollbox() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		//Margin trading
		public function margin_history() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		public function margin_info() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		//lending:
		public function loan_offer() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function cancel_loan_offer() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function loan_offer_status() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function active_loan_offers() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		//borrowing:

		public function get_positions() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function claim_position() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function close_position() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function active_loan() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function inactive_loan() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

	}

?>