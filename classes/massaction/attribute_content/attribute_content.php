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
		$this->storage_path = eZSys::rootDir ().'/'. eZSys::storageDirectory (). '/'. $this->Module->currentModule(). '/';
		$this->ma_nodes_list = array();

		//echo __METHOD__;
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
	public function postCheck (){
		if (!$this->Module->isCurrentAction ('change_attribute_content')){
			/*
			$this->ma_xml = new MA_XML_File(null, $this->storage_path, 'massaction_17');
			//var_dump($this->ma_xml->get_data_arr());
			var_dump($this->ma_xml->get_data_arr());
			// */
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
		//$this->error->pop_parent_source_line();

		$this->ma_xml = new MA_XML_File ($this->parameters, $this->storage_path, $this->Module->currentModule ());
		if ($this->error->has_error()){
			$this->ErrorList[] = $this->error->get_error();
			$this->log->write($this->error->get_error(true, true));
			return false;
		}

		if (!$this->ma_xml->store_file ()){
			$this->ErrorList[] = $this->error->get_error();
			$this->log->write($this->error->get_error(true, true));
			return false;
		}
		$this->parameters['cli']['file-name'] = $this->ma_xml->get_file_name ();
		$this->parameters['cli']['parent-folder'] = $this->ma_xml->get_container ();

		if ($this->parameters['cli_flag']){
			if (!$this->delegate_work_to_cli ()){
				$this->ErrorList[] = $this->error->get_error();
				$this->log->write($this->error->get_error(true, true));
				return false;
			}
		}
		if (!$this->parameters['cli_flag']){
			if (!$this->change_attribute_content()){
				$this->ErrorList[] = $this->error->get_error();
				$this->log->write($this->error->get_error(true, true));
				return false;
			}
		}
		$this->error->pop_parent_source_line();

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
			$this->error->set_error($this->ErrorList[0], __METHOD__, __LINE__, MA_Error::ERROR);
			//eZDebug::writeError ('Module: '. $this->Module->currentModule (). '/'. $this->Module->currentView ().
			//	' '. __METHOD__. ' '.__LINE__. ': '. $this->ErrorList[0]);
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
		$this->error->add_parent_source_line(__METHOD__);
		if (!$this->set_script_monitor()){
			$this->error->pop_parent_source_line();
			return false;
		}
		$this->error->pop_parent_source_line();
		return true;
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
				'php extension/ezmassaction/bin/cli/'. eZScheduledScript::SCRIPT_NAME_STRING. ' -s '. eZScheduledScript::SITE_ACCESS_STRING.
				' --filename-part='. $this->parameters['cli']['file-name']. ' --parent-catalog='. $this->parameters['cli']['parent-folder']
			);
			$script->store();
			$this->parameters['scheduled_script_id'] = $script->attribute( 'id' );
		}
		else{
			$this->error->set_error('The ext ezscriptmonitor is turned off or missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		return true;
	}

	protected function change_attribute_content (){
		$this->error->add_parent_source_line(__METHOD__);
		$this->start_TS = $this->get_microtime_float();
		set_time_limit (60*30);

		$this->parameters['web']['subtrees'] = array();
		$this->parameters['web']['objects']['languages']['count'] = 0;
		$this->parameters['web']['nodes']['count'] = 0;

		$this->log->write (
			'Start '. __METHOD__. "\n".
			'Section identifier: '. $this->parameters['section_identifier']. "\n".
			'Class identifier: '. $this->parameters['class_identifier']. "\n".
			'Attribute identifier: '. $this->parameters['attribute_identifier']. "\n".
			'New attribute content: '. $this->parameters['attribute_content']. "\n"
		);

		foreach ($this->parameters['parents_nodes_ids'] as $_key => $_node_id){
			$this->log->write(
				'Tree Parent - node id: '. $_node_id. "\n"
			);
			$this->ma_nodes_list[$_key] = new MA_Content_Object_Tree_Nodes_List(
				$_node_id, $this->parameters['section_identifier'], $this->parameters['class_identifier'], $this->parameters['locales_codes'], 15
			);

			if (!$this->ma_nodes_list[$_key]){
				$this->error->pop_parent_source_line();
				return false;
			}
			if ($this->error->has_error()){
				$this->error->pop_parent_source_line();
				return false;
			}

			if (
				!$this->ma_nodes_list[$_key]->set_to_change_nodes_tree_attribute_content(
					$this->parameters['attribute_identifier'], $this->parameters['attribute_content'], false, null, null
				)
			){
				$this->error->pop_parent_source_line();
				return false;
			}
			do{
				if (!$this->ma_nodes_list[$_key]->change_nodes_tree_attribute_content ()){
					$this->error->pop_parent_source_line();
					//$this->parameters['web']['subtrees'][$_node_id]['result'] = $this->ma_nodes_list[$_key]->get_change_result ();
					return false;
				}
				$this->parameters['web']['subtrees'][$_node_id]['result'] = $this->ma_nodes_list[$_key]->get_change_result ();
				$this->parameters['web']['objects']['languages']['count']
					+= $this->parameters['web']['subtrees'][$_node_id]['result']['objects']['languages']['changed']['counter'];
				$this->parameters['web']['nodes']['count'] += $this->parameters['web']['subtrees'][$_node_id]['result']['nodes']['changed']['counter'];
				break;
			}
			while (!$this->parameters['web']['subtrees'][$_node_id]['result']['end_flag']);

			//$obb = new MA_Content_Object_Tree_Nodes_List();
			//if ($this->error->has_error()){
				//unset ($obb);
				//return false;
			//}
			//$obb->preset_create_tree();
			//$this->log->write('class: '. get_class($obb));

			/**
			 * @ToDo move below code to maobjects module
			 *
			if (!$this->ma_nodes_list[$_key]->preset_create_tree(MA_Content_Object_Tree_Nodes_List::CR_METHOD_LINE_X, 20, 2, null, 'folder')){
				$this->ErrorList[] = $this->error->get_error_message();
				$this->log->write($this->error->get_error(true, true));
				return false;
			}
			if (!$this->ma_nodes_list[$_key]->create_tree()){
				$this->ErrorList[] = $this->error->get_error_message();
				$this->log->write($this->error->get_error(true, true));
				return false;
			}

			*/
		}
		$this->error->pop_parent_source_line ();

		$this->end_TS = $this->get_microtime_float();
		$this->log->write (
			'  Done, end '. __METHOD__. "\n".
			'  Run from Web.'. "\n".
			'  Works time: '. ($this->end_TS - $this->start_TS). ' secs'. "\n".
			'  Count of changed nodes: '. $this->parameters['web']['nodes']['count']. "\n".
			'  Count of changed language versions objects: '. $this->parameters['web']['objects']['languages']['count']. "\n"
		);
		return true;
	}

	protected function get_microtime_float (){
		return microtime( true );
	}

}
