<?php

class Attribute_content extends MAWizardBase{
	//protected $xml;
	//protected $session;
	protected $content_object_attribute_post_key;
	protected $storage_path;
	protected $ma_xml;

	protected $start_TS;
	protected $end_TS;
	protected $ma_nodes_list;


	function __construct ($_tpl, $_params, $_storageName){
		parent::__construct ($_tpl, $_params, $_storageName);

		$this->storage_path = str_replace ('\\', '/', eZSys::rootDir () ).'/'. eZSys::storageDirectory (). '/'.
			$this->Module->currentModule(). '/';
		$this->ma_nodes_list = array();

		echo __METHOD__;
	}

	protected function set_parameters_attribute_content (){
		$this->parameters['attribute_content'] = $_POST[$this->content_object_attribute_post_key];
		$this->parameters['attribute_post_key'] = $this->content_object_attribute_post_key;
	}
	protected function set_parameters_cli_flag (){
		unset($this->parameters['step_by_step']);
		$this->parameters['cli_flag'] = false;
		if ($this->Module->hasActionParameter ('step_by_step') and $this->Module->actionParameter ('step_by_step')){
			$this->parameters['cli_flag'] = true;
		}
	}
	function postCheck (){
		if (!$this->Module->isCurrentAction ('change_attribute_content')){
			$this->prepare_to_repeat_step ();

			return false;
		}

		$this->set_var_parameters_attr_identifier ();
		$this->set_var_parameters_class_identifier ();
		$this->set_var_parameters_section_identifier();

		if (!$this->search_attribute_in_post ()){
			$this->prepare_to_repeat_step();

			return false;
		}
		$this->set_parameters_attribute_content ();

		$this->set_parameters_cli_flag ();


		$this->error->add_parent_source_line(__METHOD__);

		$this->ma_xml = new MA_XML_File ($this->parameters, $this->storage_path, $this->Module->currentModule ());
		if (!$this->ma_xml->store_file ()){
			$this->ErrorList[] = $this->error->get_error_message();
			$this->log->write($this->error->get_error(true, true));
			$this->error->pop_parent_source_line();
			return false;
		}
		$this->error->pop_parent_source_line();

		$this->parameters['file_name'] = $this->ma_xml->get_file_name ();

		if ($this->parameters['cli_flag']){
			$this->delegate_work_to_cli ();
		}
		else{
			if (!$this->change_attribute_content())
			{
				return false;
			}
		}


		//$this->setMetaData ('current_step', $this->metaData ('current_step') - 1);

		return true;
	}
	protected function search_attribute_in_post (){
		$post_keys = array_keys ($_POST);

		/*
		$post_keys = array (//test data
			'bla_bla',
			'ContentObjectAttribute_data_text_585',
			'ContentObjectAttribute_data_enhancedbinaryfilename_1209600'
		);
		*/
		$pattern = '/^ContentObjectAttribute_[[:alnum:]_]{4,}$/u';
		$founded_keys = preg_grep ($pattern, $post_keys, 0);
		/* $founded_keys
		 * 1 => 'ContentObjectAttribute_data_text_585',
		 * 2 => 'ContentObjectAttribute_data_enhancedbinaryfilename_1209600'
		 */

		if (!count ($founded_keys) ){
			$this->ErrorList[] = 'Do not founded attribute.';
			eZDebug::writeWarning ('Module: '. $this->Module->currentModule (). '/'. $this->Module->currentView ().
				' '. __METHOD__. ' '.__LINE__. ': '. $this->ErrorList[0]);

			return false;
		}
		$founded_keys = array_values ($founded_keys);
		// array reindex to set first founded element to the index 0
		$this->content_object_attribute_post_key = $founded_keys[0];

		return true;
	}
	protected function set_var_parameters_attr_identifier (){
		$this->parameters['attribute_identifier'] = eZContentClassAttribute::classAttributeIdentifierByID ($this->parameters['attribute_id']);
	}
	protected function set_var_parameters_class_identifier (){
		$this->parameters['class_identifier'] = eZContentClass::classIdentifierByID ($this->parameters['class_id']);
	}
	protected function set_var_parameters_section_identifier (){
		$this->parameters['section_identifier'] = eZSection::fetch($this->parameters['section_id'])->attribute('identifier');
	}

	protected function delegate_work_to_cli (){
		$this->set_script_monitor();
	}
	protected function set_script_monitor (){
		// Take care of script monitoring - only if extension exists
		$this->parameters['scheduled_script_id'] = -1;

		//$scheduledScript = false;
		if (in_array( 'ezscriptmonitor', eZExtension::activeExtensions() )
			and class_exists( 'eZScheduledScript' )
		){
			$script = eZScheduledScript::create(
				'content_changer.php',
				'extension/ezmassaction/bin/cli/'. eZScheduledScript::SCRIPT_NAME_STRING. ' -s '. eZScheduledScript::SITE_ACCESS_STRING.
				' --filename-part='. $this->parameters['file_name']
			);
			$script->store();
			$this->parameters['scheduled_script_id'] = $script->attribute( 'id' );
		}
	}

	protected function change_attribute_content (){
		$this->error->add_parent_source_line(__METHOD__);
		$this->start_TS = $this->get_microtime_float();
		//set_time_limit (120);

		$this->parameters['cron']['subtrees'] = array();
		$this->parameters['cron']['objects']['languages']['count'] = 0;
		$this->parameters['cron']['nodes']['count'] = 0;

		$this->log->write (
			"\n".
			'Start '. __METHOD__. "\n".
			'Section identifier: '. $this->parameters['section_identifier']. "\n".
			'Class identifier: '. $this->parameters['class_identifier']. "\n".
			'Attribute identifier: '. $this->parameters['attribute_identifier']. "\n".
			'New attribute content: '. $this->parameters['attribute_content']. "\n"
		);

		$this->HTTP->setPostVariable($this->parameters['attribute_post_key'], $this->parameters['attribute_content']);

		foreach ($this->parameters['parents_nodes_ids'] as $_key => $_node_id){
			$this->log->write(
				"\n".
				'Parent node id: '. $_node_id. "\n"
			);
			$this->ma_nodes_list[$_key] = new MA_Content_Object_Tree_Nodes_List(
				$_node_id, $this->parameters['section_identifier'], $this->parameters['class_identifier'], $this->parameters['locales_codes'], 5
			);

			if (!$this->ma_nodes_list[$_key]){
				$this->ErrorList[] = $this->error->get_error_message();
				$this->log->write($this->error->get_error(true, true));
				$this->error->pop_parent_source_line();
				return false;
			}

			$this->ma_nodes_list[$_key]->set_to_change_nodes_tree_attribute_content (
				$this->parameters['attribute_identifier'], $this->parameters['attribute_content'], $this->parameters['attribute_post_key'], false
			);

			do{
				if (!$this->ma_nodes_list[$_key]->change_nodes_tree_attribute_content ()){
					$this->ErrorList[] = $this->error->get_error_message();
					$this->log->write($this->error->get_error(true, true));
					$this->error->pop_parent_source_line();
					$this->parameters['cron']['subtrees'][$_node_id] = $this->ma_nodes_list[$_key]->get_change_result ();
					return false;
				}
				$this->parameters['cron']['subtrees'][$_node_id] = $this->ma_nodes_list[$_key]->get_change_result ();
				$this->parameters['cron']['objects']['languages']['count']
					+= $this->parameters['cron']['subtrees'][$_node_id]['objects']['langs']['counter'];
				$this->parameters['cron']['nodes']['count'] += $this->parameters['cron']['subtrees'][$_node_id]['nodes']['counter'];
				break;
			}
			while (!$this->parameters['cron']['subtrees'][$_node_id]['end_flag']);

			// now a small scam

			//$_subtree_counter++;
		}
		$this->error->pop_parent_source_line ();

		$this->end_TS = $this->get_microtime_float();
		$this->log->write(
			"\n".
			'  Works end '. "\n".
			'  Works time: '. ($this->end_TS - $this->start_TS). ' secs'. "\n".
			'  Changed nodes count: '. $this->parameters['cron']['nodes']['count']. "\n".
			'  Changed language objects count: '. $this->parameters['cron']['objects']['languages']['count']. "\n".
			'  Was Run from web browser'. "\n"
		);
		return true;
	}

	protected function get_microtime_float (){
		return microtime( true );
	}

}
