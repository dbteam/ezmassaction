<?php

class MAWizardBase extends eZWizardBase{
	//protected $xml;
	//protected $session;
	const FIRST_STEP = 0;

	protected $parameters;
	protected $user_parameters;
	protected $params;
	protected $view;
	protected $view_uri;


	function __construct ($_tpl, $_params, $_storageName = false){
		//$this->xml = new Massaction_XML_file ();
		$_module = $_params['Module'];

		$this->view_uri = $_module->currentModule (). '/'. $_module->currentView ();
		$this->WizardURL = $this->view_uri;
		$this->eZWizardBase( $_tpl, $_module, $_storageName );

		$this->params = $_params;

		$this->user_parameters = null;
		if (count ($this->params['UserParameters'])){
			$this->user_parameters = $this->params['UserParameters'];
		}

		if ($this->hasVariable ('parameters')){
			$this->parameters = $this->variable ('parameters');
		}
		else{
			$this->parameters = array ();
			$this->parameters['first_step_id'] = MAWizardBase::FIRST_STEP;
			$this->parameters['step'] = MAWizardBase::FIRST_STEP;
			//$this->parameters['step'] = MAWizardBase::FIRST_STEP;
		}

		//$this->parameters['first_step_id'] = MAWizardBase::FIRST_STEP;
		$_current_step = $this->metaData ('current_step');
		if (!$_current_step or $_current_step < MAWizardBase::FIRST_STEP){
			$this->setMetaData ('current_step', MAWizardBase::FIRST_STEP);
		}
	}

	/**
	 * Useless method but overridden if someone will to use it
	 * @return bool
	 */
	function preCheck()
	{
		$this->prepare_to_repeat_step ();

		return false;
	}

	function postCheck()
	{
		if (!$this->Module->isCurrentAction ('get_attributes_list')){
			$this->prepare_to_repeat_step ();

			return false;
		}

		if (count ($this->Module->actionParameter ('parents_nodes_ids') ) < 1){
			$this->ErrorList[] = 'Required data is either missing or is invalid';
		}
		else{
			$this->parameters['parents_nodes_ids'] = $this->Module->actionParameter ('parents_nodes_ids');

			foreach ($this->parameters['parents_nodes_ids'] as $key => $node_id){
				$this->parameters['parents_nodes_ids'][$key] = (int) $node_id;
			}
		}

		if ($this->Module->hasActionParameter ('section_id') ) {
			$this->parameters['section_id'] = (int) $this->Module->actionParameter ('section_id');
		}

		if ($this->Module->actionParameter ('class_id') < 1){
			$this->ErrorList[] = 'Required data is either missing or is invalid';
		}
		else{
			$this->parameters['class_id'] = (int) $this->Module->actionParameter ('class_id');
		}

		if (count ($this->Module->actionParameter ('locales_codes') ) < 1){
			$this->ErrorList[] = 'Required data is either missing or is invalid';
		}
		else{
			$this->parameters['locales_codes'] = $this->Module->actionParameter ('locales_codes');
		}

		if (isset ($this->ErrorList[0]) ){
			$this->prepare_to_repeat_step ();

			return false;
		}

		return true;
	}

	protected function prepare_to_repeat_step (){
		$this->WizardURL = $this->view_uri;
		//$this->setMetaData ('current_step', Attributes_list::FIRST_STEP);

		//$this->parameters['form']['action']['url_alias'] = $this->Module->currentModule (). '/'. $this->Module->currentView ();
		$this->parameters['form']['action']['url_alias'] = $this->view_uri;
		$this->setVariable ('parameters', $this->parameters);
	}

	function nextStep (){
		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->Functions[$this->params['FunctionName']]
			['custom_view_parameters'][$this->Module->currentAction ()]['next_step']['url_alias'];

		$this->parameters['form']['action']['url_alias'] = $this->WizardURL;
		$this->setVariable ('parameters', $this->parameters);

		$this->setMetaData ('current_stage', eZWizardBase::STAGE_POST);
		$this->setMetaData ('current_step', $this->metaData( 'current_step' ) + 1);
		$this->savePersistentData();

		return $this->Module->redirectTo ($this->WizardURL);
	}

	function process(){
		$this->prepare_to_repeat_step();
		$this->savePersistentData ();
		//$this->setVariable ('parameters', $this->parameters);

		$this->set_view ();
		return $this->get_view ();
	}

	protected function set_view (){
		$this->Tpl = eZTemplate::factory();
		//$this->Tpl->setVariable( 'wizard', $this );

		//$this->Tpl->setVariable( 'step', $this->metaData( 'current_step' ) );
		$this->parameters['step'] = $this->metaData ('current_step');

		$persistent_variable = array ();
		$persistent_variable['parameters'] = $this->parameters;
		$persistent_variable['errors'] = $this->ErrorList;
		$persistent_variable['warnings'] = $this->WarningList;

		$this->Tpl->setVariable ('persistent_variable', $persistent_variable);

		$this->view = array();
		//$this->view['content'] = isset( $result ) ? $result : null;
		$this->view['content'] = $this->Tpl->fetch ('design:'. $this->Module->currentModule (). '/'. 'attribute_content.tpl');
		$this->view['view_parameters'] = $this->user_parameters;

		$this->view['persistent_variable'] = $this->Tpl->variable ('persistent_variable');
		$this->view['content_info'] = array (
			'persistent_variable' => $this->Tpl->variable ('persistent_variable'),
			'default_navigation_part' => $this->Module->Functions[$this->params['FunctionName']]['default_navigation_part'],
			'object_id' => false,
			'node_id' => false
		);

		$this->view['path'][] = array (
			'text' => $this->Module->Module['name'],
			'url' => '',
			'url_alias' => ''
		);

		$this->view['path'][] = array (
			'text' => 'Wizard',//'Index',
			'url' => $this->Module->uri (). '/'. $this->Module->currentView (),
			'url_alias' => $this->Module->currentModule (). '/'. $this->Module->currentView ()
		);
		$this->view['default_navigation_part'] = $this->Module->Functions[$this->params['FunctionName']]['default_navigation_part'];

	}
	protected function get_view (){
		return $this->view;
	}
	public function get_parameters(){
		return $this->parameters;
	}

	public static function get_step_number ($StorageName = 'eZWizard', $MetaDataName = '_meta' ){
		$MetaData = eZSession::get ($StorageName . $MetaDataName);
		if (!isset ($MetaData['current_step']) ){
			return 0;
		}

		return $MetaData['current_step'];
	}

}
