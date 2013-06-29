<?php
$module = $Params['Module'];
$tpl = eZTemplate::factory();
//$http = eZHTTPTool::instance();
$userParameters = '';
if ( isset( $Params['UserParameters'] ) )
{
	$userParameters = $Params['UserParameters'];
}

$stepArray = array();
/*
$stepArray[] = array(
	'file' => 'start.php',
	'class' => 'Start'
);
*/
$stepArray[] = array(
	'file' => 'attributes_list.php',
	'class' => 'Attributes_list'
);
$stepArray[] = array(
	'file' => 'attribute.php',
	'class' => 'Attribute'
);
$stepArray[] = array(
	'file' => 'attribute_content.php',
	'class' => 'Attribute_content'
);
$stepArray[] = array(
	'file' => 'ma_result.php',
	'class' => 'MA_Result'
);
//path = 'extension/ezmassaction/classes/'. $module->currentView (). '/';
//$path = preg_replace ('/modules\/?/', '', $module->Path, 1). 'classes/'. $module->currentView ().'/';
$count = 1;
$path = str_replace('modules', '', $module->Path, $count). 'classes/'. $module->currentModule ().'/'. $module->currentView (). '/';
$path = str_replace("//", '/', $path);

if ($module->isCurrentAction ('restart_process')){
	$step = eZWizardBaseClassLoader::createClass ( $tpl, $Params, $stepArray,  $path, $module->currentModule (),
		array (
			'current_step' => 0,
			'current_stage' => eZWizardBase::STAGE_POST
		)
	);
	$step->cleanUp ();
	unset ($step);

	$step2 = eZWizardBaseClassLoader::createClass ( $tpl, $Params, $stepArray,  $path, $module->currentModule (),
		array (
			'current_step' => 0,
			'current_stage' => eZWizardBase::STAGE_POST
		)
	);
	return $step2->run();
}
else{
	$step_number = MAWizardBase::get_step_number ($module->currentModule ());
	$step = eZWizardBaseClassLoader::createClass ( $tpl, $Params, $stepArray,  $path, $module->currentModule (),
		array (
			'current_stage' => eZWizardBase::STAGE_POST,
			'current_step' => $step_number
		)
	);

	//echo '<br /> else step metadata';
	//var_dump($step->MetaData);
	//$module->redirectURL ($module->currentModule (). '/'. $module->Functions [$Params ['FunctionName'] ]['custom_view_parameters']['start']['url_alias']);

	$Result =  $step->run();

	$parameters = $step->get_parameters ();
	//echo '<br />parameters: <br />';
	//var_dump($parameters);//['subtrees']
	//var_dump($step->MetaData);

	//echo '<br /> Errors: ';
	//var_dump($step->ErrorList);

	return $Result;
}
/*
if (strpos (trim ($module->Functions['index']['custom_view_parameters']['index']['url_alias'], "/"), "/")){
	$url_alias_arr = explode("/", $module->Functions['index']['custom_view_parameters']['index']['url_alias']);
	if (isset ($url_alias_arr[1])){
		$module_dir_name = $url_alias_arr[0];
		$view_alias = $url_alias_arr[1];
	}
	else{
		//it shouldnt happen
		$module_dir_name = $module->currentModule ();
		$view_alias = $url_alias_arr[0];
	}
}
else{
	$module_dir_name = $module->currentModule ();
	$view_alias = $module->Functions['index']['custom_view_parameters']['index']['url_alias'];
}

return $module->redirectionURI($module_dir_name, $view_alias, array(), null, $userParameters);
*/

