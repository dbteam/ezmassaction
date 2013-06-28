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
	protected $path;

	const ERROR = 1;
	const WARNING = 3;
	const NOTICE = 6;
	const DEBUG = 7;


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
			'line' => '',
			'type' => MA_Error::DEBUG
		);
		$this->path = array();
	}

	/**
	 * @param string $messagee
	 * @param string $source
	 * @param null $line
	 * @param null $type - MA_Error::ERROR/WARNING/..
	 * @param bool $ezdebug_fg
	 */
	public function set_error ($messagee = '', $source = '', $line = 0, $type = 0, $ezdebug_fg = true){
		$this->error = array ();

		$this->error['message'] = $messagee;
		$this->error['source'] = implode (':: ', $this->path). ':: '. $source;
		$this->error['line'] = $line;
		$this->error['type'] = ($type? $type: self::DEBUG);

		$this->add_error();

		if ($ezdebug_fg){
			$this->write_ezdebug();
		}
	}
	protected function add_error (){
		//if ($this->error['type'] < self::NOTICE){
		//	array_unshift($this->errors_list, $this->error);
		//}
		$this->errors_list[] = $this->error;
	}

	public function get_error ($as_string_fg = true, $unset_error_fg = false){
		if (!reset ($this->errors_list)){
			return false;
		}
		$this->error = reset ($this->errors_list);

		if ($unset_error_fg){
			$this->error = array_shift ($this->errors_list);
			$error = $this->error;

			$this->pop_parent_source_line();
		}
		else{
			$error = $this->error;
			//$this->error = array();
		}

		if ($as_string_fg){
			$error = $error['message']. ' '. $error['source']. ' Line: '. $error['line']. ' Type('. $error['type']. ').';
		}

		return $error;
	}
	public function get_errors ($unset_errors_flag = true){
		$errors = $this->errors_list;

		if ($unset_errors_flag){
			$this->clear_errors ();
		}
		return $errors;
	}

	protected function write_ezdebug (){
		switch ($this->error['type']){
			case self::ERROR:
				eZDebug::writeError ($this->get_error());
				break;
			case self::WARNING:
				eZDebug::writeWarning ($this->get_error());
				break;
			case self::NOTICE:
				eZDebug::writeNotice ($this->get_error());
				break;
			case self::DEBUG:
				eZDebug::writeDebug ($this->get_error());
				break;
			default:
				eZDebug::writeDebug ($this->get_error());
				break;
		}
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
		if ($line){
			$this->path[] = $source. ' Line: '. $line;
		}
		else{
			$this->path[] = $source;
		}

	}
	/*
	 * require refatorize code using MA_Error
	 * before the programmer have to call the method yourself at now it is called in ::get_error(true|false, true)
	 *
	 */
	public function pop_parent_source_line (){
		array_pop($this->path);
		$this->path = array_values($this->path);
	}
	public function clear_parents_source_line (){
		$this->path = array();
	}
	public function get_error_message ($unset_error_fg = false){
		$this->error = $this->get_error(false, $unset_error_fg);
		return $this->error['message'];
	}

}
