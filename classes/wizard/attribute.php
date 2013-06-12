<?php

class Attribute extends MAWizardBase{

	public function __construct ($_tpl, $_params, $_storageName = false, $_userParameters = null){
		parent::__construct ($_tpl, $_params, $_storageName);

		echo __METHOD__;
	}

	function postCheck (){
		if (!$this->Module->isCurrentAction ('get_attribute')){
			$this->prepare_to_repeat_step ();

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

		$this->set_var_parameters_attr_identifier();
		$this->set_var_parameters_class_identifier();

		return true;
	}

	function process(){
		//$this->set_var_parameters_attr_identifier();
		//$this->set_var_parameters_class_identifier();

		$this->prepare_to_repeat_step ();

		$this->set_view ();
		return $this->get_view ();
	}

	protected function set_var_parameters_attr_identifier (){
		$this->parameters['attribute_identifier'] = eZContentClassAttribute::classAttributeIdentifierByID ($this->parameters['attribute_id']);
	}
	protected function set_var_parameters_class_identifier (){
		$this->parameters['class_identifier'] = eZContentClass::classIdentifierByID ($this->parameters['class_id']);
	}

}
