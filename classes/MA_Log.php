<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radek
 * Date: 24.06.13
 * Time: 11:47
 * To change this template use File | Settings | File Templates.
 */

class MA_Log {
	protected $file_name;
	protected $file_full_name;
	protected $content;

	protected static $instance = false;

	public static function get_instance (){
		if (self::$instance == false){
			self::$instance = new MA_Log();
		}
		return self::$instance;
	}

	protected function __construct(){
		if (!$this->file_name){
			$this->set_file_name();
		}
	}

	public function write ($content = '', $begin_nl_fg = true, $end_nl_fg = false){
		$this->content = $content;
		$begin_nl = ($begin_nl_fg? "\n": '');
		$end_nl= ($end_nl_fg? "\n": '');
		eZLog::write ($begin_nl. $this->content. $end_nl, $this->file_full_name);
	}

	public function set_file_name ($file_name = null){
		$this->file_name = ($file_name? $file_name: __CLASS__);
		$this->set_file_full_name ();
	}
	protected function set_file_full_name (){
		$this->file_full_name = $this->file_name. '.log';
	}

}