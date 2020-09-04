<?php

namespace Selin;

use stdClass;

class ErrorNotifier {

	//based on https://stackoverflow.com/questions/8440439/safely-catch-a-allowed-memory-size-exhausted-error-in-php

	private $memory;
	private $last_error;
	private $email_to;
	private $email_from;
	private $base_url;

	public function __construct( $email_to, $email_from, $base_url ) {
		$this->email_to   = $email_to;
		$this->email_from = $email_from;
		$this->base_url   = $base_url;

		$this->reserve_memory();

		register_shutdown_function( [ $this, 'shutdown' ] );
	}

	private function reserve_memory() {
		// reserve 3 mega bytes
		$this->memory          = new stdClass();
		$this->memory->reserve = str_repeat( '❤', 1024 * 1024 );
	}

	public function shutdown() {
		//Если словили ООМ, то надо освободить зарезервированую память чтобы скрипт отработал дальше, отправил оповещение об ошибке.
		unset( $this->memory->reserve );

		$this->last_error = error_get_last();

		$this->notify();
	}

	private function notify() {

		if ( ! $this->is_anything_for_notify() ) {
			return;
		}

//	var_dump(http_response_code(500));

		$subject = 'Error: ' . $this->last_error['message'];

		$url     = $this->base_url . $_SERVER['REQUEST_URI'];
		$message = "<a href='$url'>$url</a><br>";

		foreach ( $this->last_error as $key => $value ) {
			$message .= $key . ': ' . $value . '<br>';
		}

		$message .=  'ip: ' . $_SERVER['REMOTE_ADDR'] . '<br>';
		$message .=  'User Agent: ' . $_SERVER['HTTP_USER_AGENT'] . '<br>';

		$headers = [
			'From'         => $this->email_from,
			'Content-type' => 'text/html; charset=utf-8',//.PHP_EOL,
		];

		mail( $this->email_to, $subject, $message, $headers );
	}

	private function is_anything_for_notify() {
		return $this->last_error !== null
		       && $this->last_error['type'] === 1;
	}

}