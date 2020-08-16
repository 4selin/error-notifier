<?php

class ErrorNotifier {

	//based on https://stackoverflow.com/questions/8440439/safely-catch-a-allowed-memory-size-exhausted-error-in-php

	private $memory;
	private $last_error;
	private $email;

	public function __construct( $email ) {
		$this->email = $email;

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

		$subject = 'Error: ' . $this->last_error['message'];

		$url     = WP_HOME . $_SERVER['REQUEST_URI'];
		$message = "<a href='$url'>$url</a><br>";

		foreach ( $this->last_error as $key => $value ) {
			$message .= $key . ': ' . $value . '<br>';
		}

		$headers = [
			'From'         => 'info@otabletkah.ru',
			'Content-type' => 'text/html; charset=utf-8',//.PHP_EOL,
		];

		mail( $this->email, $subject, $message, $headers );
	}

	private function is_anything_for_notify() {
		return $this->last_error !== null
		       && $this->last_error['type'] === 1;
	}

}

//( new ErrorNotifier( '4selin@gmail.com' ) );
