<?php

class twizo extends WP_SMS
{
	private $wsdl_link = "https://api.unisender.com/en/api/";
	public $tariff = "http://www.unisender.com/en/prices/";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct()
	{
		parent::__construct();
		$this->has_key = true;
		$this->help = "Get the application key from https://portal.twizo.com/applications/";
		$this->validateNumber = "This is a mandatory array with strings parameter. It should be an array of numbers (in string format), in international format, for the SMS. At least 1 number must be set and maximum 1000.";
	}

	public function SendSMS()
	{
		// Check gateway credit
		if (is_wp_error($this->GetCredit())) {
			return new WP_Error('account-credit', __('Your account does not credit for sending sms.', 'wp-sms'));
		}

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters('wp_sms_from', $this->from);

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters('wp_sms_to', $this->to);

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters('wp_sms_msg', $this->msg);

		try {
			$twizo = Twizo\Api\Twizo::getInstance($this->has_key, 'api-asia-01.twizo.com');
			$sms = $twizo->createSms($this->msg, $this->to[0], $this->from);

			$sms->setResultType($sms::RESULT_TYPE_POLL);
			$result = $sms->send();

			$this->InsertToDB($this->from, $this->msg, $this->to);

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 * @param string $result result output.
			 */
			do_action('wp_sms_send', $result);

			return $result;
		} catch (Twizo\Api\Exceptio $e) {
			return new WP_Error('send-sms', $e->getMessage());
		}
	}

	public function GetCredit()
	{
		// Check username and password
		if (!$this->username && !$this->password) {
			return new WP_Error('account-credit', __('Username/Password does not set for this gateway', 'wp-sms'));
		}
		
		if (!function_exists('curl_version')) {
			return new WP_Error('required-function', __('CURL extension not found in your server. please enable curl extension.', 'wp-sms'));
		}

		return true;
	}
}