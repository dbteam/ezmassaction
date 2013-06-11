<?php

$module = $Params['Module'];

$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();

//$template_name = 'attribute_content';
//$persistent_variable = array ();
$userParameters = array ();

if (isset ($Params['UserParameters']) )
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

$path = preg_replace ('/modules.*$/', '', $module->Path, 1). 'classes/wizard/';

if ($module->isCurrentAction ('restart_process')){
	$step = eZWizardBaseClassLoader::createClass ( $tpl, $Params, $stepArray,  $path, $module->currentModule (),
		array (
			'current_step' => 0,
			'current_stage' => eZWizardBase::STAGE_PRE
		)
	);
	$step->cleanUp ();

	unset ($step);

	$step2 = eZWizardBaseClassLoader::createClass ( $tpl, $Params, $stepArray,  $path, $module->currentModule (),
		array (
			'current_step' => 0,
			'current_stage' => eZWizardBase::STAGE_PRE
		)
	);

	echo '<br /> step restart metadata';
	//var_dump($step2->parameters);
	var_dump($step2->MetaData);

	return $step2->run();
}
else{
	$step_number = Attributes_list::get_step_number($module->currentModule ());
	$step = eZWizardBaseClassLoader::createClass ( $tpl, $Params, $stepArray,  $path, $module->currentModule (),
		array (
			'current_stage' => eZWizardBase::STAGE_POST,
			'current_step' => 0
		)
	);

	echo '<br /> else step metadata';
	var_dump($step->MetaData);
	//$module->redirectURL ($module->currentModule (). '/'. $module->Functions [$Params ['FunctionName'] ]['custom_view_parameters']['start']['url_alias']);

	$Result =  $step->run();

	var_dump($step->parameters);
	var_dump($step->MetaData);

	echo '<br /> Errors: ';
	//var_dump($step->ErrorList);

	return $Result;
}



