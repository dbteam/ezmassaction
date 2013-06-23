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
$my_ch = new Content_Changer($script);
if (!$my_ch){

	return false;
}
$my_ch->display_begin();
$my_ch->process();
$my_ch->write_result();
$my_ch->display_summation();



unset ($my_ch);

$script->shutdown (0);



class Content_Changer{
	protected $db;
	protected $script;
	protected $script_id;
	protected $cli;
	protected $options;

	protected $siteaccess;
	protected $user_admin_name;
	protected $user_admin;
	protected $show_SQL_flag;

	protected $log_file_name;
	protected $log_file_full_name;
	protected $ma_nodes_list;
	protected $ma_tree_nodes;
	protected $ma_xml_file;
	protected $xml_data_file_name;
	protected $xml_data_arr;

	protected $folder_container;
	protected $scheduled_script;

	protected $error;


	function __construct (eZScript $_script){
		$this->error = MA_Error::get_instance();
		$this->set_log_file_name();

		$this->cli = eZCLI::instance();

		$this->script = $_script;
		$this->script->startup();
		$this->set_options();
		$this->script->initialize();
		$this->set_scheduled_script();

		$this->db = eZDB::instance ();
		$this->set_show_SQL();

		$this->user_admin_name = ($this->options['user-admin-name']? $this->options['user-admin-name']: 'admin');
		$this->user_admin = eZUser::fetchByName( $this->user_admin_name );
		if ($this->user_admin){
			eZUser::setCurrentlyLoggedInUser ($this->user_admin, $this->user_admin->attribute ('id'));
		}

		if (!$this->set_ma_xml())
			return false;
		if (!$this->set_xml_data_arr())
			return false;

		if (!$this->set_ma_nodes_list())
			return false;



	}

	protected function set_sheduled_script (){
		$this->scheduled_script = false;
		if (
			isset ($this->options['scriptid'])
			and in_array ('ezmassaction', eZExtension::activeExtensions())
			and class_exists ('eZScheduledScript')
		){
			$this->script_id = $this->options['scriptid'];
			$this->scheduled_script = eZScheduledScript::fetch ($this->script_id);
		}
	}

	public function display_begin (){
		$this->cli->endlineString();
		$this->cli->output('Start processing');
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

	protected function set_xml_data_file_name (){
		$this->xml_data_file_name = str_replace ('\.xml', '', $this->options['filename-part']);
		$this->xml_data_file_name = $this->xml_data_file_name? $this->xml_data_file_name: null;
	}
	protected function set_folder_container (){
		$this->folder_container = $this->options['parent-folder']? $this->options['parent-folder']: $this->xml_data_file_name;
	}
	protected function set_ma_xml (){
		$this->error->add_parent_source_line(__METHOD__, __LINE__);

		$xml_path = eZSys::rootDir (). '/'. eZSys::storageDirectory (). '/'. $this->folder_container. '/' ;
		$this->ma_xml_file = new MA_XML_File(null, $xml_path, $this->xml_data_file_name);
		//$this->ma_xml_file->fetch_file()
		if (!$this->ma_xml_file){
			$this->cli->error ($this->error->get_error ());
			$this->write_log ($this->error->get_error (true, true));
			$this->error->pop_parent_source_line();
			$this->script->shutdown(1);
			return false;
		}
		$this->error->pop_parent_source_line();
		return true;
	}

	protected function set_xml_data_arr (){
		$this->error->add_parent_source_line(__METHOD__, __LINE__);

		if (!$this->ma_xml_file->fetch_file()){
			$this->cli->error($this->error->get_error());
			$this->write_log($this->error->get_error(true, true));
			$this->error->pop_parent_source_line();
			$this->script->shutdown(1);
			return false;
		}

		$this->xml_data_arr = $this->ma_xml_file->get_data_arr();
		if (!reset($this->xml_data_arr)){
			$this->cli->error($this->error->get_error());
			$this->write_log($this->error->get_error(true, true));
			$this->error->pop_parent_source_line();
			$this->script->shutdown(1);
			return false;
		}

		if (!isset ($this->xml_data_arr['cron'])){
			$this->xml_data_arr['cron']['step'] = 0;
			$this->xml_data_arr['cron']['offset'] = 0;
			$this->xml_data_arr['cron']['limit'] = 10;
			$this->xml_data_arr['cron']['subtree'] = array ();
		}

		$this->error->pop_parent_source_line();
		return true;
	}


	protected function set_ma_nodes_list (){
		$this->error->add_parent_source_line(__METHOD__, __LINE__);

		//$this->ma_nodes_list = new MA_Content_Object_Tree_Nodes_List($this->xml_data_arr['']
		//);


		$_subtree_counter = 0;

		foreach ($this->xml_data_arr['parents_nodes_ids'] as $_key => $_id){
			//$this->ma_tree_nodes = new MA_Content_Object_Tree_Nodes_List($_id, $this->xml_data_arr['section_identifier'], $this->xml_data_arr['class_identifier'], $this->xml_data_arr['locales_codes']);

			$this->ma_tree_nodes = new MA_Content_Object_Tree_Nodes_List (
				$_id, $this->xml_data_arr['section_identifier'], $this->xml_data_arr['class_identifier'], $this->xml_data_arr['locales_codes']
			);

			$this->ma_tree_nodes->set_to_change_nodes_tree_attribute_content (
				$this->xml_data_arr['attribute_identifier'], $this->xml_data_arr['attribute_content'], true, $this->xml_data_arr['cron']['offset'],
				$this->xml_data_arr['cron']['limit']
			);

			do{
				$this->ma_tree_nodes->change_nodes_tree_attribute_content ();
				$this->xml_data_arr['cron']['subtree'][$_subtree_counter] = $this->ma_tree_nodes->get_change_result ();
			}
			while ($this->xml_data_arr['cron']['subtree'][$_subtree_counter]['end_flag']);

			// now a small scam
			$progressPercentage = (
				($this->xml_data_arr['cron']['subtree'][$_subtree_counter]['counter']
					/ $this->xml_data_arr['cron']['subtree'][$_subtree_counter]['count'])
				/ (count ($this->xml_data_arr['parents_nodes_ids']) - $_subtree_counter) * 100);

			$this->display_progress($progressPercentage);

			$_subtree_counter++;
		}

		if (!$this->ma_nodes_list){
			$this->error->pop_parent_source_line();
			return false;
		}

		$this->error->pop_parent_source_line();
		return true;
	}
	protected function set_scheduled_script (){
		$this->scheduled_script = false;
		if (
			isset ($this->options['scriptid'])
			and in_array ('ezmassaction', eZExtension::activeExtensions())
			and class_exists ('eZScheduledScript')
		){
			$this->script_id = $this->options['scriptid'];
			$this->scheduled_script = eZScheduledScript::fetch ($this->script_id);
		}
	}
	public function display_progress ($progressPercentage = 0){
		$this->cli->output( sprintf( ' %01.1f %%', $progressPercentage ) );

		if ($this->scheduled_script){
			$this->scheduled_script->updateProgress ($progressPercentage);
		}
	}


	protected function set_log_file_name (){
		$this->log_file_name = __CLASS__;
		$this->set_log_file_full_name ();
	}
	protected function set_log_file_full_name (){
		$this->log_file_full_name = $this->log_file_name. '.log';
	}

	protected function write_log ($message = ''){
		eZLog::write ($message, $this->log_file_full_name);
	}

}
