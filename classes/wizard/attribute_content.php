<?php

class Attribute_content extends MAWizardBase{
	//protected $xml;
	//protected $session;
	protected $content_object_attribute_post_key;
	protected $storage_path;


	function __construct ($_tpl, $_params, $_storageName){
		parent::__construct ($_tpl, $_params, $_storageName);

		$this->storage_path = str_replace ('\\', '/', eZSys::rootDir () ).'/'. eZSys::storageDirectory (). '/'.
			$this->Module->currentModule(). '/';

		echo __METHOD__;
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
			eZDebug::writeWarning ('Module: '. $this->Module->currentModule(). '/'. $this->Module->currentView().
				' '. __METHOD__. ' '.__LINE__. ': '. $this->ErrorList[0]);

			return false;
		}
		$founded_keys = array_values ($founded_keys);
		// array reindex to set first founded element to the index 0
		$this->content_object_attribute_post_key = $founded_keys[0];

		return true;
	}
	function postCheck()
	{
		if (!$this->Module->isCurrentAction ('change_attribute_content')){
			$this->prepare_to_repeat_step();

			return false;
		}

		if (!$this->search_attribute_in_post ()){
			$this->prepare_to_repeat_step();

			return false;
		}

		if (!$this->Module->hasActionParameter ('attribute_id') ){
			$this->ErrorList[] = 'Required data is either missing or is invalid';
			$this->prepare_to_repeat_step ();

			return false;
		}

		if ($this->Module->actionParameter ('attribute_id') < 1){
			$this->ErrorList[] = 'Required data is either missing or is invalid';
			$this->prepare_to_repeat_step ();

			return false;
		}
		$this->parameters['attribute_id'] = (int) $this->Module->actionParameter ('attribute_id');

		$ma_xml = new MA_XML_File ($this->parameters);
		$ma_xml->store_file ($this->Module->currentModule (), $this->storage_path);

		$this->set_var_parameters_attr_identifier();
		$this->set_var_parameters_class_identifier();

		return true;
	}

	protected function set_var_parameters_attr_identifier (){
		$this->parameters['attribute_identifier'] = eZContentClassAttribute::classAttributeIdentifierByID ($this->parameters['attribute_id']);
	}
	protected function set_var_parameters_class_identifier (){
		$this->parameters['class_identifier'] = eZContentClass::classIdentifierByID ($this->parameters['class_id']);
	}

}
