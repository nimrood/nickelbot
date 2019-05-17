<?PHP

	class CoinbaseAdapter extends CryptoBase implements CryptoExchange {

		public function __construct($Exch) {
			$this->exch = $Exch;
		}

		private function get_market_symbol( $market )
		{
			return strtoupper( $market );
		}

		private function unget_market_symbol( $market )
		{
			return strtolower( $market );
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

		public function get_trades( $market = "BTC-USD", $opts = array( 'limit' => 10 ) ) {
			$results = [];
			$trades = $this->exch->products_trades( $market, $opts['limit'] );

			foreach( $trades as $trade ) {
				$trade['market'] = "$market";

				$trade['amount'] = $trade['size'];
				$trade['timestamp'] = $trade['time'];
				$trade['exchange'] = null;
				$trade['tid'] = null;
				$trade['type'] = $trade['side'];

				unset( $trade['time'] );
				unset( $trade['trade_id'] );
				unset( $trade['size'] );
				unset( $trade['side'] );

				array_push( $results, $trade );
			}
			return $results;
		}

		public function get_orderbook( $market = "BTC-USD", $depth = 0 ) {
			$orderbook = $this->exch->products_book( $market );
			$results = [];
			$n_orderbook = [];

			foreach( $orderbook['bids'] as $order ) {
				$order['type'] = 'bid';
				array_push( $results, $order );
			}
			foreach( $orderbook['asks'] as $order ) {
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
				unset( $order[2] );

				array_push( $n_orderbook, $order );
			}

			return $n_orderbook;
		}

		public function get_deposits_withdrawals() {
			$accounts = $this->exch->accounts();
			$results = [];
			foreach( $accounts as $account ) {
				$transactions = $this->exch->account_ledger( $account['id'] );
				foreach( $transactions as $transaction ) {
					$transaction['exchange'] = 'Coinbase';
					$transaction['method'] = 'BTC';
					$transaction['currency'] = 'BTC';
					$transaction['confirmations'] = 6;
					$transaction['description'] = $transaction['details'];
					$transaction['status'] = 'COMPLETE';
					$transaction['address'] = null;
					$transaction['fee'] = 0;
					$transaction['timestamp'] = $transaction['created_at'];

					unset( $transaction['created_at'] );
					unset( $transaction['balance'] );
					unset( $transaction['details'] );
					array_push( $results, $transaction );
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

		public function cancel( $orderid="1", $opts = array() ) {
			return $this->exch->cancel_order( $orderid );
		}
		
		public function cancel_all() {
			$orders = $this->get_open_orders();
			$results = [];
			if( is_array( $orders ) && count( $orders ) > 0 )
				foreach( $orders as $order )
					if( isset( $order['id'] ) )
						array_push( $results, $this->cancel( $order['id'] ) );
			return array( 'success' => true, 'error' => false, 'message' => $results );
		}

		public function buy( $pair = "BTC-LTC", $amount = 0, $price = 0, $type = "LIMIT", $opts = array() ) {
			$price = number_format( $price, 2, ".", "" );
			$buy = $this->exch->create_order( array( 'side' => 'buy', 'product_id' => $pair, 'price' => $price, 'size' => $amount ) );
			if( isset( $buy['message'] ) )
				print_r( $buy );
			return $buy;
		}
		
		public function sell( $pair = "BTC-LTC", $amount = 0, $price = 0, $type = "LIMIT" , $opts = array() ) {
			$price = number_format( $price, 2, ".", "" );
			$sell = $this->exch->create_order( array( 'side' => 'sell', 'product_id' => $pair, 'price' => $price, 'size' => $amount ) );
			if( isset( $sell['message'] ) )
				print_r( $sell );
			return $sell;
		}

		public function get_open_orders( $market = "BTC-USD" ) {
			if( isset( $this->open_orders ) )
				return $this->open_orders;
			$orders = $this->exch->get_orders();
			
			$results = [];
			foreach( $orders as $order ) {
				$order['market'] = $order['product_id'];
				$order['timestamp_created'] = strtotime( $order['created_at'] );
				$order['exchange'] = "coinbase";
				$order['avg_execution_price'] = null;
				$order['side'] = null;
				$order['is_live'] = null;
				$order['is_cancelled'] = null;
				$order['is_hidden'] = null;
				$order['was_forced'] = null;
				$order['amount'] = $order['size'];
				$order['remaining_amount'] = null;
				$order['executed_amount'] = null;

				unset( $order['product_id'] );
				unset( $order['created_at'] );

				array_push( $results, $order );
			};

			$this->open_orders = $results;
			return $this->open_orders;
		}

		public function get_completed_orders( $market = "BTC-USD", $limit = 100 ) {
			$completed_orders = $this->exch->get_fills();
			$results = [];

			foreach( $completed_orders as $completed_order ) {
				$completed_order['market'] = $market;

				$completed_order['amount'] = $completed_order['size'];
				$completed_order['timestamp'] = $completed_order['created_at'];
				$completed_order['exchange'] = null;
				$completed_order['type'] = $completed_order['side'];
				$completed_order['fee_currency'] = null;
				$completed_order['fee_amount'] = null;
				$completed_order['tid'] = null;
				$completed_order['id'] = null;
				$completed_order['total'] = null;

				unset( $completed_order['created_at'] );
				unset( $completed_order['trade_id'] );
				unset( $completed_order['product_id'] );
				unset( $completed_order['user_id'] );
				unset( $completed_order['profile_id'] );
				unset( $completed_order['liquidity'] );
				unset( $completed_order['size'] );
				unset( $completed_order['side'] );
				unset( $completed_order['settled'] );

				array_push( $results, $completed_order );

			}

			return $results;
		}

		public function get_markets() {
			$products = $this->exch->products();
			$results = [];
			foreach( $products as $product ) {
				array_push( $results, $this->get_market_symbol( $product['id'] ) );
			}
			return $results;
		}

		public function get_currencies() {
			$currencies = $this->exch->currencies();
			$response = [];
			foreach( $currencies as $currency ) {
				array_push( $response, $currency['id'] );
			}
			return array_map( 'strtoupper', $response );
		}
		
		public function deposit_address( $currency = "BTC" ){
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}
		
		public function deposit_addresses(){
			return array( 'ERROR' => 'METHOD_NOT_AVAILABLE' );
		}

		public function get_balances() {
			$balances = $this->exch->accounts();
			$response = [];
			foreach( $balances as $balance ) {
				$balance['type'] = "exchange";
				$balance['total'] = $balance['balance'];
				$balance['reserved'] = $balance['hold'];
				$balance['pending'] = 0;
				$balance['btc_value'] = 0;
				$balance['currency'] = strtoupper( $balance['currency'] );

				unset( $balance['balance'] );
				unset( $balance['hold'] );
				unset( $balance['id'] );
				unset( $balance['profile_id'] );
				
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
				if( $market_summary['market'] == $market )
					return $market_summary;
			return $market_summary;
		}

		public function get_market_summaries() {
			if( isset( $this->market_summaries ) ) //cache
				return $this->market_summaries;

			$products = $this->exch->products();
			$this->market_summaries = [];
			foreach( $products as $market_summary ) {
				$market_summary['exchange'] = "coinbase";
				$market_summary = array_merge( $market_summary, $this->exch->products_ticker( $market_summary['id'] ) );
				$market_summary = array_merge( $market_summary, $this->exch->products_stats( $market_summary['id'] ) );
				$market_summary['high'] = isset( $market_summary['high'] ) ? $market_summary['high'] : 0;
				$market_summary['low'] = isset( $market_summary['low'] ) ? $market_summary['low'] : 0;
				$market_summary['volume'] = isset( $market_summary['volume'] ) ? $market_summary['volume'] : 0;
				$market_summary['market'] = $this->get_market_symbol( $market_summary['id'] );
				$market_summary['minimum_order_size_base'] = $market_summary['base_min_size'];
				$market_summary['minimum_order_size_quote'] = null;
				$market_summary['maximum_order_size'] = $market_summary['base_max_size'];
				$market_summary['timestamp'] = $market_summary['time'];
				$market_summary['mid'] = is_null( $market_summary['price'] ) ? 0 : $market_summary['price'];
				$market_summary['last_price'] = $market_summary['mid'];
				$market_summary['ask'] = $market_summary['last_price'];
				$market_summary['bid'] = $market_summary['last_price'];
				$market_summary['price_precision'] = 2; //base_precision, quote_precision?
				$market_summary['result'] = true;
				$market_summary['created'] = null;
				$market_summary['percent_change'] = null;
				$market_summary['frozen'] = null;
				$market_summary['verified_only'] = null;
				$market_summary['vwap'] = null;
				$market_summary['base_volume'] = $market_summary['volume'];
				$market_summary['quote_volume'] = bcmul( $market_summary['base_volume'] * $market_summary['mid'], 32);
				$market_summary['btc_volume'] = null;
				$market_summary['expiration'] = null;
				$market_summary['initial_margin'] = null;
				$market_summary['minimum_margin'] = null;
				$market_summary['open_buy_orders'] = null;
				$market_summary['open_sell_orders'] = null;
				$market_summary['market_id'] = null;
				if( isset( $market_summary['message'] ) )
					$market_summary['frozen'] = true;

				unset( $market_summary['volume_30day'] );
				unset( $market_summary['volume'] );
				unset( $market_summary['message'] );
				unset( $market_summary['id'] );
				unset( $market_summary['base_min_size'] );
				unset( $market_summary['base_max_size'] );
				unset( $market_summary['time'] );
				unset( $market_summary['price'] );
				unset( $market_summary['quote_increment'] );
				unset( $market_summary['base_currency'] );
				unset( $market_summary['quote_currency'] );
				unset( $market_summary['base_currency'] );
				unset( $market_summary['open'] );
				unset( $market_summary['size'] );
				unset( $market_summary['open'] );
				unset( $market_summary['trade_id'] );

				ksort( $market_summary );

				if( $market_summary['market'] == "BTC-USD" )
					array_push( $this->market_summaries, $market_summary );
			}
			return $this->market_summaries;
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