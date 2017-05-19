<?php

class twizo extends WP_SMS {
	private $wsdl_link = "https://api-asia-01.twizo.com"; // Or https://api-eu-01.twizo.com
	public $tariff = "http://twizo.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->has_key        = true;
		$this->help           = "Get the application key from https://portal.twizo.com/applications/";
		$this->validateNumber = "This is a mandatory array with strings parameter. It should be an array of numbers (in string format), in international format, for the SMS. At least 1 number must be set and maximum 1000.";
	}

	public function SendSMS() {
		// Check gateway credit
		if ( is_wp_error( $this->GetCredit() ) ) {
			return new WP_Error( 'account-credit', __( 'Your account does not credit for sending sms.', 'wp-sms' ) );
		}

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 *
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters( 'wp_sms_from', $this->from );

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 *
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters( 'wp_sms_to', $this->to );

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 *
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters( 'wp_sms_msg', $this->msg );

		$data = array(
			'body'       => $this->msg,
			'sender'     => $this->from,
			'recipients' => $this->to,
		);

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->wsdl_link . "/v1/sms/submitsimple" );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $ch, CURLOPT_USERPWD, 'twizo' . ':' . $this->has_key );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen( json_encode( $data ) )
			)
		);

		$response = curl_exec( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		$json = json_decode( $response );

		if ( isset( $json->total_items ) ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */
			do_action( 'wp_sms_send', $json );

			return $json;
		} else {
			return new WP_Error( 'send-sms', sprintf( '%s, %s', $json->detail, print_r( $json->validation_messages, 1 ) ) );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms' ) );
		}

		if ( ! function_exists( 'curl_version' ) ) {
			return new WP_Error( 'required-function', __( 'CURL extension not found in your server. please enable curl extension.', 'wp-sms' ) );
		}

		return true;
	}
}