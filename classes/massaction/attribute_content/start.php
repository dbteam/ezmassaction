<?php

class MA_Start extends eZWizardBase{
	//protected $xml;
	//protected $session;
	protected $parameters;
	protected $user_parameters = array ();
	protected $params;

	function MA_Start ($tpl, $_params, $_storageName = false){
		//$this->xml = new Massaction_XML_file ();

		$this->eZWizardBase( $tpl, $_params['Module'], $_storageName );
		//$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->currentView ();

		$this->params = $_params;
		$this->user_parameters = $this->params['UserParameters'];

		if ($this->hasVariable ('parameters')){
			$this->parameters = $this->variable ('parameters');
		}
		else{
			$this->parameters = array ();
		}

	}

	function processPostData()
	{

		return true;
	}

	function preCheck()
	{
		return $this->postCheck ();
	}

	function postCheck()
	{
		$this->prepare_to_next_step ();

		return true;
	}
	protected function prepare_to_next_step (){
		$this->WizardURL = $this->Module->currentModule (). '/'. $this->Module->Functions[$this->params['FunctionName']]['custom_view_parameters']
			[$this->Module->currentAction ()]['next_step']['url_alias'];
	}

	function process()
	{
		$this->setVariable ('parameters', $this->parameters);

		return $this->get_view ();
	}
	protected function get_view (){

		//$this->Tpl->setVariable( 'wizard', $this );
		$this->Tpl->setVariable( 'step', $this->metaData( 'current_step' ) );

		$persistent_variable = array ();
		$persistent_variable['parameters'] = $this->parameters;

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

}