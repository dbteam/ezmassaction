<?php

class Result extends MAWizardBase{
	//protected $xml;
	//protected $session;
	protected $content_object_attribute_post_key;
	protected $storage_path;


	function __construct ($_tpl, $_params, $_storageName){
		parent::__construct ($_tpl, $_params, $_storageName);

		echo __METHOD__;
	}

	function postCheck()
	{
		if (!$this->Module->isCurrentAction ('show_result')){
			$this->prepare_to_repeat_step();

			return false;
		}

		return true;
	}

}