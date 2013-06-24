<?php

class MA_Result extends MAWizardBase{
	//protected $xml;
	//protected $session;
	protected $content_object_attribute_post_key;
	protected $storage_path;



	function __construct ($_tpl, $_params, $_storageName){
		parent::__construct ($_tpl, $_params, $_storageName);

		$this->tpl_name = 'attribute_content/result';

		echo __METHOD__;
	}

	function postCheck(){
		if (!$this->Module->isCurrentAction ('show_result')){
			$this->prepare_to_repeat_step ();
			return false;
		}

		//to stop on this step
		$this->setMetaData ('current_step', $this->metaData ('current_step') - 1);
		return true;
	}

	function process (){
		if ($this->parameters['cli_flag']){
			$this->delegate_work_to_cron ();
		}
		else{
			$this->change_attribute_content_now ();
		}

		$this->prepare_current_step ();
		$this->savePersistentData ();
		//$this->setVariable ('parameters', $this->parameters);

		$this->set_view ();
		return $this->get_view ();
	}
	protected function delegate_work_to_cron (){
		echo '<br />';
		echo __METHOD__;
	}
	protected function change_attribute_content_now (){



		//return eZModule::HOOK_STATUS_CANCEL_RUN;
	}



}
