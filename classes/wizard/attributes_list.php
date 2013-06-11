<?php

class Attributes_list extends eZWizardBase{
	//protected $xml;
	//protected $session;
	const FIRST_STEP = 0;

	public $parameters;
	public $user_parameters = array ();
	public $params;

	function attributes_list ( $tpl, $_params, $storageName = false){
		//$this->xml = new Massaction_XML_file ();
		$module = $_params['Module'];

		$this->WizardURL = $module->currentModule (). '/'. $module->currentView ();
		$this->eZWizardBase( $tpl, $module, $storageName );

		$this->params = $_params;
		$this->user_parameters = $this->params['UserParameters'];

		if ($this->hasVariable ('parameters')){
			$this->parameters = $this->variable ('parameters');
		}
		else{
			$this->parameters = array ();
			$this->parameters['step'] = Attributes_list::FIRST_STEP;
		}

		$this->parameters['first_step_id'] = Attributes_list::FIRST_STEP;
		if (!$this->parameters['step']){
			$this->parameters['step'] = Attributes_list::FIRST_STEP;
		}

		//$this->parameters['step'] = Attributes_list::FIRST_STEP;
		$this->parameters['form']['action']['url_alias'] = $this->Module->currentModule (). '/'. $this->Module->currentView ();

		echo __METHOD__;
	}

	protected function processPostData()
	{

		return true;
	}

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

		$this->prepare_to_next_step ();

		return true;
	}
	function prepare_to_repeat_step (){
		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->currentView ();
		//$this->parameters['step'] = $this->variable('current_step');

		$this->setMetaData( 'current_step', Attributes_list::FIRST_STEP );

		$this->setVariable ('parameters', $this->parameters);

		$this->savePersistentData ();
	}

	function prepare_to_next_step (){
		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->Functions[$this->params['FunctionName']]
			['custom_view_parameters'][$this->Module->currentAction ()]['next_step']['url_alias'];

		//$this->parameters['step'] = $this->variable('current_step') + 1;
		$this->setMetaData( 'current_step', Attributes_list::FIRST_STEP + 1 );
		$this->parameters['step']++;

		$this->setVariable ('parameters', $this->parameters);
		$this->savePersistentData ();
	}

	function process()
	{
		//$this->prepare_to_repeat_step();
		//$this->setVariable ('parameters', $this->parameters);

		return $this->get_view ();
	}
	protected function get_view (){
		$this->Tpl = eZTemplate::factory();
		//$this->Tpl->setVariable( 'wizard', $this );

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
			'default_navigation_part' => $this->Module->Functions[$this->params['FunctionName']]['default_navigation_part'],
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
		$Result['default_navigation_part'] = $this->Module->Functions[$this->params['FunctionName']]['default_navigation_part'];

		return $Result;
	}

	function nextStep (){
		if ( $this->metaData( 'current_stage' ) == eZWizardBase::STAGE_PRE )
		{
			$this->setMetaData( 'current_stage', eZWizardBase::STAGE_POST );
		}
		else
		{
			$this->setMetaData( 'current_stage', eZWizardBase::STAGE_PRE );
			$this->setMetaData( 'current_step', $this->metaData( 'current_step' ) + 1 );
			$this->savePersistentData();

			return $this->Module->redirectTo( $this->WizardURL );
		}

		$this->savePersistentData();
	}

	static function get_step_number ($StorageName = 'eZWizard', $MetaDataName = '_meta' ){

		$MetaData = eZSession::get ($StorageName . $MetaDataName);
		if (!isset ($MetaData['current_step']) ){
			return 0;
		}

		return $MetaData['current_step'];
	}

}
