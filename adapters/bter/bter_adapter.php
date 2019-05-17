<?PHP

	class BterAdapter extends CryptoBase implements CryptoExchange {

		public function __construct($Exch) {
			$this->exch = $Exch;
		}

		//Get the symbol returned from Adapter:
		private function get_market_symbol( $market ) {
			$market = strtoupper( str_replace("_", "-", $market ) );
			return $market;
		}
		
		//Get the symbol returned from native lib:
		private function unget_market_symbol( $market ) {
			$market = explode( "-", $market );
			return strtolower( $market[0] . "_" . $market[1] );
		}

		public function get_info() {
			return [];
		}

		public function withdraw( $account = "exchange", $currency = "BTC", $address = "1fsdaa...dsadf", $amount = 1 ) {
			return [];
		}

		public function get_currency_summary( $currency = "BTC" ) {
			return [];
		}
		
		public function get_currency_summaries( $currency = "BTC" ) {
			return [];
		}
		
		public function get_order( $orderid = "1" ) {
			return [];
		}

		public function get_trades( $market = "BTC-USD", $opts = array( 'time' => 60 ) ) {
			$results = [];
			$curs = explode( "-", $market );
			$trades = $this->exch->trade_history( $curs[0], $curs[1], $opts['time'] );

			if( $trades['result'] ) {
				foreach( $trades['data'] as $trade ) {
					$trade['market'] = $market;
					$trade['timestamp'] = $trade['date'];
					$trade['exchange'] = null;

					unset( $trade['date'] );
					array_push( $results, $trade );
				}
			}
			return $results;
		}

		public function get_orderbook( $market = "BTC-USD", $depth = 0 ) {
			$curs = explode( "-", $market );
			$orderbook = $this->exch->depth( $curs[0], $curs[1] );
			$results = [];
			$n_orderbook = [];

			if( $orderbook['result'] ) {
				foreach( $orderbook['bids'] as $order ) {
					$order['type'] = 'bid';
					array_push( $results, $order );
				}
				foreach( $orderbook['bids'] as $order ) {
					$order['type'] = 'ask';
					array_push( $results, $order );
				}
				foreach( $results as $order ) {
					$order['market'] = $market;
					$order['price'] = $order[0];
					$order['amount'] = $order[1];
					$order['timestamp'] = null;
					$order['exchange'] = null;

					unset( $order[0] );
					unset( $order[1] );

					array_push( $n_orderbook, $order );
				}
			}

			return $n_orderbook;
		}

		public function cancel( $orderid="1", $opts = array() ) {
			return $this->exch->cancelorder( array( 'order_id' => $orderid ) );
		}

		public function get_deposits_withdrawals() {
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
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
			$orders = $this->get_open_orders();
			$results = array();
			foreach( $orders as $order ) {
				$order['cancel_results'] = $this->exch->cancelorder( array( 'order_id' => $order['id'] ) );
				print_r( $order );
				array_push($results,$order);
			}
			return array( 'success' => true, 'error' => false, 'message' => $results );
		}

		public function buy( $pair='BTC-LTC', $amount=0, $price=0, $type="LIMIT", $opts=array() ) {
			$pair = str_replace( "-", "_", strtolower( $pair ) );
			$buy = $this->exch->placeorder( array('pair' => $pair, 'type' => 'BUY', 'rate' => $price, 'amount' => $amount ) );
			if( $buy['message'] != "Success" )
				return array( 'message' => array( $buy ) );
		}
		
		public function sell( $pair='BTC-LTC', $amount=0, $price=0, $type="LIMIT", $opts=array() ) {
			$pair = str_replace( "-", "_", strtolower( $pair ) );
			$sell = $this->exch->placeorder( array('pair' => $pair, 'type' => 'SELL', 'rate' => $price, 'amount' => $amount ) );
			if( $sell['message'] != "Success" )
				return array( 'message' => array( $sell ) );
		}

		public function get_open_orders( $market = "BTC-USD" ) {
			$orderlist = $this->exch->orderlist();

			$results = [];
			foreach( $orderlist['orders'] as $order ) {

				$order['market'] = $order['pair'];
				$order['price'] = $order['rate'];
				$order['timestamp_created'] = $order['time_unix'];
				$order['exchange'] = "bter";
				$order['avg_execution_price'] = null;
				$order['side'] = null;
				$order['is_live'] = null;
				$order['is_cancelled'] = null;
				$order['is_hidden'] = null;
				$order['was_forced'] = null;
				$order['original_amount'] = null;
				$order['remaining_amount'] = null;
				$order['executed_amount'] = null;

				unset( $order['oid'] );
				unset( $order['pair'] );
				unset( $order['time_unix'] );
				unset( $order['date'] );
				unset( $order['margin'] );
				unset( $order['sell_type'] );
				unset( $order['buy_type'] );
				unset( $order['sell_amount'] );
				unset( $order['buy_amount'] );
				unset( $order['rate'] );
				unset( $order['initial_rate'] );
				unset( $order['initial_amount'] );
				unset( $order['status'] );

				array_push( $results, $order );

			}

			return $results;
		}

		//TODO: see if there is a limit
		public function get_completed_orders( $market = "BTC-USD", $limit = 100 ) 
		{
			$market = $this->get_market_symbol($market);
			$orders = $this->exch->mytrades( array( 'pair' => $this->unget_market_symbol( $market ) ) );
			$results = [];

			if( ! isset( $orders['trades'] ) ) {
				return array( 'ERROR' => array( $orders ) );
			} else {
				foreach( $orders['trades'] as $order ) {
					$order['market'] = $market;
					$order['order_id'] = $order['orderid'];
					$order['price'] = $order['rate'];
					$order['exchange'] = "Bter";
					$order['timestamp'] = $order['time_unix'];
					$order['exchange'] = "Bter";
					$order['fee_currency'] = null;
					$order['fee_amount'] = null;
					$order['tid'] = null;
					$order['id'] = null;
					$order['fee'] = null;
					$order['total'] = null;

					unset( $order['orderid'] );
					unset( $order['oid'] );
					unset( $order['pair'] );
					unset( $order['rate'] );
					unset( $order['time'] );
					unset( $order['time_unix'] );

					array_push( $results, $order );
				}
			}
			return $results;
		}

		public function get_markets() {
			$markets = $this->exch->pairs();
			$results = [];
			foreach( $markets as $market ) {
				array_push( $results, str_replace('_', '-', strtoupper( $market ) ) );
			}
			return $results;
		}

		public function get_currencies() {
			$currencies = $this->exch->marketlist();
			$response = [];
			foreach( $currencies['data'] as $currency ) {
				array_push( $response, strtoupper( $currency['symbol'] ) );
			}
			return $response;
		}

		public function deposit_address($currency="BTC"){
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function deposit_addresses(){
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function get_balances() {
			$balances = $this->exch->getfunds();
			$response = [];
			$currencies = $this->get_currencies();
			foreach( $currencies as $currency ) {
				$balance = [];
				$balance['type'] = "exchange";
				$balance['currency'] = strtoupper($currency);
				$balance['available'] = isset( $balances['available_funds'][$currency] ) ? $balances['available_funds'][$currency] : 0;
				$balance['reserved'] = isset( $balances['locked_funds'][$currency] ) ? $balances['locked_funds'][$currency] : 0;
				$balance['total'] = $balance['available'] + $balance['reserved'];
				$balance['pending'] = 0;
				$balance['btc_value'] = 0;
				$response[$balance['currency']] = $balance;
			}

			return $response;
		}

		public function get_balance( $currency="BTC" ) {
			$balances = $this->get_balances();
			foreach( $balances as $balance )
				if( $balance['currency'] == $currency )
					return $balance;
		}

		public function get_market_summary( $market = "BTC-LTC" ) {
			$market_summaries = $this->get_market_summaries();
			foreach( $market_summaries as $market_summary )
				if( $market_summary['market'] = $market )
					return $market_summary;
			return [];
		}

		public function get_market_summaries() {
			$tickers = $this->exch->tickers();

			$market_info = $this->exch->marketinfo();
			$market_info = $market_info['pairs'];
			$markets = [];
			foreach( $market_info as $market ) {
				$key = array_keys( $market );
				$key = $key[0];
				$markets[$key] = $market[$key];
			}

			$market_summaries = [];
			foreach( $tickers as $key => $market_summary ) {
				$market_summary['market'] = $this->get_market_symbol( $key );
				$market_summary['exchange'] = "bter";
				$market_summary = array_merge( $market_summary, $markets[$key] );
				$curs = explode( "_", $key );
				$cur1 = $curs[0];
				$cur2 = $curs[1];
				$market_summary['mid'] = $market_summary['avg'];
				$market_summary['bid'] = is_null( $market_summary['buy'] ) ? 0 : $market_summary['buy'];
				$market_summary['ask'] = is_null( $market_summary['sell'] ) ? 0 : $market_summary['sell'];
				$market_summary['last_price'] = $market_summary['last'];
				$market_summary['display_name'] = $market_summary['market'];
				$market_summary['percent_change'] = $market_summary['rate_change_percentage'];
				$market_summary['base_volume'] = $market_summary['vol_'.$cur1];
				$market_summary['quote_volume'] = $market_summary['vol_'.$cur2];
				$market_summary['btc_volume'] = null;
				$market_summary['created'] = null;
				$market_summary['open_buy_orders'] = null;
				$market_summary['open_sell_orders'] = null;
				$market_summary['vwap'] = null;
				$market_summary['frozen'] = null;
				$market_summary['expiration'] = null;
				$market_summary['verified_only'] = null;
				$market_summary['initial_margin'] = null;
				$market_summary['maximum_order_size'] = null;
				$market_summary['minimum_margin'] = null;
				$market_summary['minimum_order_size_quote'] = $market_summary['min_amount'];
				$market_summary['minimum_order_size_base'] = null;
				$market_summary['price_precision'] = $market_summary['decimal_places'];
				$market_summary['timestamp'] = null;
				$market_summary['vwap'] = null;
				$market_summary['market_id'] = null;

				unset( $market_summary['fee'] );
				unset( $market_summary['min_amount'] );
				unset( $market_summary['avg'] );
				unset( $market_summary['decimal_places'] );
				unset( $market_summary['buy'] );
				unset( $market_summary['sell'] );
				unset( $market_summary['last'] );
				unset( $market_summary['rate_change_percentage'] );
				unset( $market_summary['vol_'.$cur1] );
				unset( $market_summary['vol_'.$cur2] );

				ksort( $market_summary );

				if( $market_summary['bid'] == 0 || $market_summary['ask'] == 0 || $market_summary['base_volume'] == 0 || $market_summary['quote_volume'] == 0 )
					continue;

				array_push( $market_summaries, $market_summary );
			}
			return $market_summaries;
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