<?php

class Attributes_list extends MAWizardBase{

	function __construct ( $_tpl, $_params, $_storageName){
		parent::__construct($_tpl, $_params, $_storageName);

		echo __METHOD__;
	}

	public function postCheck(){
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


}
