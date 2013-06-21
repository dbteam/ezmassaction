<?php

require 'autoload.php';

function microtime_float()
{
	return microtime( true );
}

set_time_limit (0);

$script = eZScript::instance(
	array(
		'description' => (
			"CLI script. \n\n".
			"Will change attributes content set with wizard. \n".
			"\n".
			'content_changer.php -s site_admin'
		),
		'use-session' => false,
		'use-modules' => true,
		'use-extensions' => true
	)
);
$my_ch = new Content_Changer ();
$my_ch->

unset ($my_ch);

$script->shutdown (0);



class Content_Changer{
	protected $db;
	protected $script;
	protected $cli;
	protected $options;

	protected $siteaccess;
	protected $user_admin_name;
	protected $user_admin;
	protected $show_SQL_flag;

	protected $log_file_name;
	protected $log_file_full_name;
	protected $ma_nodes_list;
	protected $ma_xml_file;
	protected $error;


	function __construct (eZScript $_script){
		$this->error = MA_Error::get_instance();
		$this->set_log_file_name();

		$this->cli = eZCLI::instance();

		$this->script = $_script;
		$this->script->startup();
		$this->set_options();
		$this->script->initialize();
		$this->set_ez_sheduled_script();

		$this->db = eZDB::instance ();

		$this->user_admin_name = ($this->options['user-admin-name']? $this->options['user-admin-name']: 'admin');
		$this->user_admin = eZUser::fetchByName( $this->user_admin_name );
		if ($this->user_admin){
			eZUser::setCurrentlyLoggedInUser ($this->user_admin, $this->user_admin->attribute ('id'));
		}
		$this->set_ma_nodes_list();

		$this->set_show_SQL();
	}

	protected function set_show_SQL (){
		$this->show_SQL_flag = $this->options['sql'] ? true : false;
		$this->db->setIsSQLOutputEnabled ($this->show_SQL_flag);
	}
	protected function set_options (){
		$this->options = $this->script->getOptions(
			"[parent-catalog:][filename-part:][user-admin-name:][scriptid:][sql]",
			"",
			array(
				'parent-catalog' => 'Catalog contains the file',
				'filename-part' => 'XML file name with serialized data (without extension, without module name and file extension)',
				'user-admin-name' => 'Alternative login for the user to perform operation as, if no write script use default name: admin',
				'scriptid' => 'Used by the Script Monitor extension, do not use manually',
				'sql' => 'If write script display sql queries'
			)
		);
	}

	protected function set_ma_xml_file (){
		$this->ma_xml_file = new MA_XML_File(
			 null, $this->options[''],);
	}

	protected function set_ma_nodes_list (){
		$this->ma_nodes_list = new MA_Content_Object_Tree_Nodes_List(
			$this->options['']
		);

		if (!$this->ma_nodes_list){
			$this->error->add_parent_source_line(__METHOD__, __LINE__);
			return false;
		}
		return true;
	}
	protected function set_ez_sheduled_script (){


	}

	protected function set_log_file_name (){
		$this->log_file_name = __CLASS__;
		$this->set_log_file_full_name ();
	}
	protected function set_log_file_full_name (){
		$this->log_file_full_name = $this->log_file_name. '.log';
	}



}
