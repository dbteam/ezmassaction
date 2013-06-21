<?php
/**
 * Created by JetBrains PhpStorm.
 * Singleton
 *
 * User: radek
 * Date: 21.06.13
 * Time: 10:43
 * To change this template use File | Settings | File Templates.
 */

class MA_Error {
	protected $errors_list;
	protected $error;
	protected $message;
	protected $source;//Class::method() or/and file, line,
	protected $type;

	const DEBUG = 7;
	const NOTICE= 6;

	const WARNING = 3;
	const ERROR = 1;

	protected static $o_instance = false;

	public static function get_instance ($clear_errors_fg = true){
		if (self::$o_instance == false){
			self::$o_instance = new MA_Error();
		}
		if ($clear_errors_fg){
			self::$o_instance->clear_errors();
		}

		return self::$o_instance;
	}

	protected function __construct(){
		$this->errors_list = array();
		$this->error = array(
			'message' => '',
			'source' => '',
			'type' => MA_Error::DEBUG
		);
	}

	public function set_error ($messagee = '', $source = '', $line = null, MA_ERROR $type = ''){
		$this->error = array ();

		$this->error['message'] = $messagee;
		$this->error['source'] = $source;
		$this->error['line'] = $line;
		$this->error['type'] = ($type? $type: MA_Error::MA_DEBUG);

		$this->add_error();
	}
	protected function add_error (){
		//if ($this->error['type'] < self::NOTICE){
		//	array_unshift($this->errors_list, $this->error);
		//}
		$this->errors_list[] = $this->error;
	}

	public function get_error ($as_string_fg = true, $unset_error_fg = false) {
		if (!reset ($this->errors_list)){
			return false;
		}
		$this->error = reset ($this->errors_list);

		if ($unset_error_fg){
			$this->error = array_shift ($this->errors_list);
			$error = $this->error;
		}
		else{
			$error = $this->error;
		}

		if ($as_string_fg){
			$error = $error['message']. ' '. $error['source']. ' Line: '. $error['line']. ' Type('. $error['type']. ')';
		}

		return $error;
	}
	public function get_errors ($unset_errors_flag = true){
		$errors = $this->errors_list;

		if ($unset_errors_flag){

			$this->clean_errors();
		}
		return $errors;
	}
	public function has_error (){
		if (!reset ($this->errors_list)){
			return false;
		}
		return true;
	}
	public function clear_errors (){
		$this->errors_list = array ();
	}
	public function add_parent_source_line ($source = '', $line = null){
		$this->errors_list[0]['source'] = $source. ' Line: '. $line. ':: '. $this->errors_list[0]['source'];
	}
	public function get_error_message (){
		$this->error = $this->get_error(false);
		return $this->error['message'];
	}

}
