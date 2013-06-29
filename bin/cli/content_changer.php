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
	$script->shutdown (1);
	return false;
}

$my_ch->process();


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

	protected $ma_nodes_list;
	protected $ma_tree_nodes;
	protected $ma_xml_file;
	protected $xml_data_file_name;
	protected $xml_data_arr;
	protected $node_id;
	protected $folder_container;
	protected $scheduled_script;
	//protected $view;
	protected $result;
	protected $start_TS;
	protected $end_TS;

	protected $key;

	protected $error;
	protected $log;
	protected $depth;


	function __construct (eZScript $_script){
		$this->error = MA_Error::get_instance();
		//$this->set_log_file_name();
		$this->log = MA_Log::get_instance();
		$this->log->set_file_name(__CLASS__);
		$this->key = 0;
		$this->node_id = -1;

		$this->cli = eZCLI::instance();

		$this->script = $_script;
		$this->script->startup();
		//CLI Options
		$this->set_options();
		$this->script->initialize();
		$this->set_scheduled_script();

		$this->db = eZDB::instance ();
		$this->set_show_SQL();

		$this->set_user_admin();
		$this->set_xml_data_file_name();
		$this->set_folder_container();

		//$this->cli->output('file name: '. $this->options['filename-part']);
		//$this->script->shutdown(0);
		$this->set_ma_xml();
		$this->preset_xml_data_arr();
		$this->depth = 15;
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
				'parent-catalog' => 'Catalog contains the xml file.',
				'filename-part' => 'XML file name with serialized data (without extension, without module name and file extension)',
				'user-admin-name' => 'Alternative login for the user to perform operation as, if no write script use default name: admin',
				'scriptid' => 'Used by the Script Monitor extension, do not use manually',
				'sql' => 'If write script display sql queries'
			)
		);
	}
	protected function set_user_admin (){
		$this->user_admin_name = ($this->options['user-admin-name']? $this->options['user-admin-name']: 'admin');
		$this->user_admin = eZUser::fetchByName( $this->user_admin_name );
		if ($this->user_admin){
			eZUser::setCurrentlyLoggedInUser ($this->user_admin, $this->user_admin->attribute ('id'));
		}
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

	protected function set_xml_data_file_name (){
		$this->xml_data_file_name = str_replace ('\.xml', '', $this->options['filename-part']);
		$this->xml_data_file_name = $this->xml_data_file_name? $this->xml_data_file_name: null;
		if (!$this->xml_data_file_name){
			$this->error->set_error('XML file name missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			$this->cli->error ($this->error->get_error ());
			$this->write_log ($this->error->get_error (true, true));
			$this->script->shutdown(1);
			return false;
		}
		$this->error->pop_parent_source_line();
		return true;
	}
	protected function set_folder_container (){
		$length = strrpos($this->xml_data_file_name, "_") - 0;
		$dir_name = substr($this->xml_data_file_name, 0, $length);
		$this->folder_container = ($this->options['parent-catalog']? $this->options['parent-catalog']: $dir_name);
	}
	protected function set_ma_xml (){
		$this->error->add_parent_source_line(__METHOD__);
		$xml_path = eZSys::rootDir (). '/'. eZSys::storageDirectory (). '/'. $this->folder_container. '/' ;
		$this->cli->output('xml path: '. $xml_path. $this->xml_data_file_name);

		$this->ma_xml_file = new MA_XML_File (null, $xml_path, $this->xml_data_file_name);
		//$this->ma_xml_file->fetch_file()
		if ($this->error->has_error()){
			$this->cli->error ($this->error->get_error ());
			$this->write_log ($this->error->get_error (true, true));
			$this->script->shutdown(1);
			return false;
		}
		$this->error->pop_parent_source_line();
		return true;
	}

	protected function preset_xml_data_arr (){

		$this->xml_data_arr = $this->ma_xml_file->get_data_arr();
		if (!count ($this->xml_data_arr)){
			$this->error->set_error('No data in XML file.', __METHOD__, __LINE__, MA_Error::ERROR);
			$this->cli->error($this->error->get_error());
			$this->write_log($this->error->get_error(true, true));
			$this->script->shutdown(1);
			return false;
		}

		//$this->xml_data_arr['cron']['subtrees'] = array ();
		//$this->xml_data_arr['cli'] = array();
		if (!isset ($this->xml_data_arr['cron'])){
			$this->xml_data_arr['cron']['subtrees'] = array ();
		}
		if (!isset ($this->xml_data_arr['cli'])){
			$this->xml_data_arr['cli'] = array();
			$this->xml_data_arr['cli']['result']['ended_flag'] = false;
		}
		$this->xml_data_arr['cli']['result']['objects']['languages']['count'] = 0;
		$this->xml_data_arr['cli']['result']['nodes']['count'] = 0;

		return true;
	}

	public function process (){
		if (isset ($this->xml_data_arr['cli']['result']['ended_flag'])){
			if ($this->xml_data_arr['cli']['result']['ended_flag']){
				$this->error->set_error("\n". 'Script stopped. All nodes/objects are changed in all subtrees.'. "\n".
					'Look into log file: '. $this->log->get_file_name(), __METHOD__, __LINE__, MA_Error::WARNING);
				$this->log->write($this->error->get_error());
				$this->cli->error($this->error->get_error(true, true));
				return false;
			}
		}
		$this->error->add_parent_source_line(__METHOD__);
		$this->display_begin();
		if (!$this->store_ma_nodes_list()){
			$this->log->write($this->error->get_error());
			$this->cli->error($this->error->get_error(true, true));
			$this->error->pop_parent_source_line();
			return false;
		}
		$this->set_result();
		$this->write_log($this->result['summation']);
		$this->write_result();
		$this->display_summation();
		$this->error->pop_parent_source_line();
		return true;
	}
	protected function display_begin (){
		//$this->cli->endlineString();
		$text =
			"\n".
			'Start step.'. "\n".
			'In every time You can look into the log file: '. $this->log->get_file_name(). "\n".
			__METHOD__. "\n".
			'Section identifier: '. $this->xml_data_arr['section_identifier']. "\n".
			'Class identifier: '. $this->xml_data_arr['class_identifier']. "\n".
			'Attribute identifier: '. $this->xml_data_arr['attribute_identifier']. "\n".
			'New attribute content: '. $this->xml_data_arr['attribute_content']. "\n"
		;
		$this->write_log($text);
		$this->cli->output($text);
	}
	protected function store_ma_nodes_list (){
		$this->start_TS = microtime_float();
		/*$this->ma_nodes_list = new MA_Content_Object_Tree_Nodes_List($this->xml_data_arr['']
		//);
		$_subtree_counter = 0;
		*/
		foreach ($this->xml_data_arr['parents_nodes_ids'] as $this->key => $this->node_id){
			if (!isset ($this->xml_data_arr['cron']['subtrees'][$this->node_id])){
				$this->preset_store();
			}
			if ($this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['end_flag']){
				continue;
			}
			$this->xml_data_arr['cron']['subtrees'][$this->node_id]['step']++;

			$this->xml_data_arr['cron']['subtrees'][$this->node_id]['offset']	= (
				(((int)$this->xml_data_arr['cron']['subtrees'][$this->node_id]['step']) - 1)
				* $this->xml_data_arr['cron']['subtrees'][$this->node_id]['limit']
			);

			$this->cli->output("Tree Parent - Node ID: ". $this->node_id, true);
			$this->error->add_parent_source_line(__METHOD__);

			$this->ma_nodes_list[$this->key] = new MA_Content_Object_Tree_Nodes_List(
				$this->node_id, $this->xml_data_arr['section_identifier'], $this->xml_data_arr['class_identifier'],
				$this->xml_data_arr['locales_codes'], $this->depth
			);
			if ($this->error->has_error() or !$this->ma_nodes_list[$this->key]){
				unset ($this->ma_nodes_list[$this->key]);
				$this->error->set_error('Cannot create object.', __METHOD__, __LINE__, MA_Error::ERROR);
				$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['error'] = $this->error->get_error();
				$this->error->pop_parent_source_line();
				return false;
			}
			if (
				!$this->ma_nodes_list[$this->key]->set_to_change_nodes_tree_attribute_content (
					$this->xml_data_arr['attribute_identifier'], $this->xml_data_arr['attribute_content'], true,
					$this->xml_data_arr['cron']['subtrees'][$this->node_id]['offset'],
					$this->xml_data_arr['cron']['subtrees'][$this->node_id]['limit']
				)
			){
				$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['error'] = $this->error->get_error();
				unset ($this->ma_nodes_list[$this->key]);
				$this->error->pop_parent_source_line();
				return false;
			}
			if (!$this->ma_nodes_list[$this->key]->change_nodes_tree_attribute_content ()){
				$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['error'] = $this->error->get_error();
				unset ($this->ma_nodes_list[$this->key]);
				$this->error->pop_parent_source_line();
				return false;
			}

			$this->update_result ();
			$this->display_progress();
			break;
		}

		$this->end_TS = microtime_float();
		$this->error->pop_parent_source_line();
		return true;
	}
	protected function preset_store (){
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['step'] = 0;
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['limit'] = 100;
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['offset'] = 0;
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result'] = array();
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['end_flag'] = false;
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['error'] = false;

		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['objects']['languages']['changed']['counter'] = 0;
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes'] = array();
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['changed'] = array();
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['changed']['counter'] = 0;
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched'] = array();
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['counter'] = 0;
		//$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['nodes_ids'] = array ();
	}
	protected function update_result (){
		$step = $this->ma_nodes_list[$this->key]->get_change_result ();

		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['objects']['languages']['changed']['counter']	=
			(int)$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['objects']['languages']['changed']['counter'] +
			$step['objects']['languages']['changed']['counter'];
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['changed']['counter'] =
			(int)$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['changed']['counter'] +
			$step['nodes']['changed']['counter'];
		//$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['nodes_ids']
		//	= array_merge($this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['nodes_ids'],
		//	$step['nodes']['fetched']['nodes_ids']);
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['counter'] =
			(int) $this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['counter'] +
			$step['nodes']['fetched']['counter'];
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['count'] = $step['nodes']['count'];

		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['last']['id'] = $step['nodes']['last']['id'];
		$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['end_flag'] = $step['end_flag'];
	}
	protected function set_result (){
		$this->xml_data_arr['cli']['result']['works_TS'] = $this->end_TS - $this->start_TS;

		foreach ($this->xml_data_arr['cron']['subtrees'] as $key => $subtree){
			$this->xml_data_arr['cli']['result']['objects']['languages']['count'] =
				(int)$this->xml_data_arr['cli']['result']['objects']['languages']['count'] +
				$subtree['result']['objects']['languages']['changed']['counter'];
			$this->xml_data_arr['cli']['result']['nodes']['count'] =
				(int)$this->xml_data_arr['cli']['result']['nodes']['count'] +
				$subtree['result']['nodes']['changed']['counter'];
		}
		$message = 'Not all works done yet.';
		$last = end ($this->xml_data_arr['cron']['subtrees']);
		$this->xml_data_arr['cli']['result']['ended_flag'] = false;
		if ((count ($this->xml_data_arr['cron']['subtrees']) == count ($this->xml_data_arr['parents_nodes_ids']))
			and ($last['result']['end_flag'] == true)
		){
			$this->xml_data_arr['cli']['result']['ended_flag'] = true;
			$message = '  All works Done!';
		}
		$this->result['summation'] =
			'  Works result: '. "\n".
			'  works time: '. $this->xml_data_arr['cli']['result']['works_TS']. ' secs'. "\n".
			'  Count of changed nodes: '. $this->xml_data_arr['cli']['result']['nodes']['count']. "\n".
			'  Count of changed language versions objects: '. $this->xml_data_arr['cli']['result']['objects']['languages']['count']. "\n".
			"\n".
			$message;
	}
	protected function write_result (){
		$this->error->add_parent_source_line(__METHOD__);

		$this->ma_xml_file->set_data_arr($this->xml_data_arr);
		if (!$this->ma_xml_file->rewrite_file()){
			$this->cli->error ($this->error->get_error ());
			$this->write_log ($this->error->get_error (true, true));
			$this->script->shutdown(1);
			return false;
		}
		$this->error->pop_parent_source_line();
	}
	protected function display_summation (){
		$this->cli->output(
			$this->result['summation']
		);
	}
	protected function display_progress ($progressPercentage = 0){
		/*
		 * now a small scam, egsample:
		 *
		 * $trees_count_elemets = array (2, 5, 9, 50);
		 *  when done $trees_count_elemets[0]
		 * echo $progressPercentage; => 25%
		 *  when done $trees_count_elemets[1]
		 * echo $progressPercentage; => 50% ..
		*/

		$progressPercentage = (
			(
				$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['fetched']['counter'] /
				$this->xml_data_arr['cron']['subtrees'][$this->node_id]['result']['nodes']['count']
			) /
			(count ($this->xml_data_arr['parents_nodes_ids']) - $this->key) * 100
		);

		$this->cli->output( sprintf( ' %01.1f %%', $progressPercentage ) );

		if ($this->scheduled_script){
			$this->scheduled_script->updateProgress ($progressPercentage);
		}
	}


	protected function set_log_file_name (){
	//	$this->log_file_name = __CLASS__;
		$this->set_log_file_full_name ();
	}

	/**
	 *
	 * @deprecated use MA_Log object->
	 */
	protected function set_log_file_full_name (){
	//	$this->log_file_full_name = $this->log_file_name. '.log';
	}

	protected function write_log ($message = ''){
		$this->log->write($message);
	}

}
