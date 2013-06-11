<?php

class Attribute_content extends eZWizardBase{
	//protected $xml;
	//protected $session;
	protected $parameters;
	protected $user_parameters;
	protected $params;
	protected $content_object_attribute_post_key;


	function __construct ($_tpl, $_params, $_storageName = false, $_userParameters = null){
		//$this->xml = new Massaction_XML_file ();
		//$this->user_parameters = $_userParameters;
		$this->eZWizardBase( $_tpl, $_params['Module'], $_storageName );

		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->currentView ();

		$this->params = $_params;
		$this->user_parameters = $this->params['UserParameters'];
		$this->parameters = $this->variable ('parameters');

	}

	function processPostData()
	{

		return true;
	}

	function preCheck()
	{
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

		$this->search_attribute_in_post();

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

		$this->prepare_to_next_step ();

		return true;
	}


	protected function prepare_to_repeat_step (){
		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->currentView ();
	}

	protected function prepare_to_next_step (){
		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->Functions[$this->params['FunctionName']]['custom_view_parameters']
			[$this->Module->currentAction ()]['next_step']['url_alias'];

		$ma_xml = new MA_XML_File ($this->parameters);
	}

	function process()
	{
		$this->set_var_parameters_attr_identifier();
		$this->set_var_parameters_class_identifier();

		$this->setVariable ('parameters', $this->parameters);

		return $this->set_view ();
	}

	protected function set_var_parameters_attr_identifier (){
		$this->parameters['attribute_identifier'] = eZContentClassAttribute::classAttributeIdentifierByID ($this->parameters['attribute_id']);
	}
	protected function set_var_parameters_class_identifier (){
		$this->parameters['class_identifier'] = eZContentClass::classIdentifierByID ($this->parameters['class_id']);
	}

	protected function set_view (){
		$this->Tpl = eZTemplate::factory();
		//$this->TPL->setVariable( 'wizard', $this );
		$this->Tpl->setVariable( 'step', $this->metaData( 'current_step' ) );

		$persistent_variable = array ();
		$persistent_variable['parameters'] = $this->parameters;
		$persistent_variable['errors'] = $this->ErrorList;
		$persistent_variable['warnings'] = $this->WarningList;

		$this->Tpl->setVariable ('persistent_variable', $persistent_variable);

		$Result = array();
		//$Result['content'] = isset( $result ) ? $result : null;
		$Result['content'] = $this->Tpl->fetch ('design:'. $this->Module->currentModule (). '/'. 'attribute_content.tpl');
		$Result['view_parameters'] = $this->user_parameters;

		$Result['persistent_variable'] = $this->Tpl->variable ('persistent_variable');
		$Result['content_info'] = array (
			'persistent_variable' => $this->Tpl->variable ('persistent_variable'),
			'default_navigation_part' => $this->Module->Functions['attributecontent']['default_navigation_part'],
			'object_id' => false,
			'node_id' => false
		);

		$Result['path'][] = array (
			'text' => $this->Module->Module['name'],
			'url' => '',
			'url_alias' => ''
		);

		$Result['path'][] = array (
			'text' => 'Wizard',//'Index',
			'url' => $this->Module->uri (). '/'. $this->Module->currentView (),
			'url_alias' => $this->Module->currentModule (). '/'. $this->Module->currentView ()
		);

		$Result['default_navigation_part'] = $this->Module->Functions['attributecontent']['default_navigation_part'];

		return $Result;
	}


}