<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radek
 * Date: 28.06.13
 * Time: 10:02
 * To change this template use File | Settings | File Templates.
 */

$module = $Params['Module'];
$tpl = eZTemplate::factory();
//$http = eZHTTPTool::instance();
//$template_name = 'attribute_content';

$stepArray = array();
/*
$stepArray[] = array(
	'file' => 'start.php',
	'class' => 'Start'
);
*/
$stepArray[] = array(
	'file' => '.php',
	'class' => ''
);
$stepArray[] = array(
	'file' => '.php',
	'class' => ''
);
$stepArray[] = array(
	'file' => '.php',
	'class' => ''
);
$stepArray[] = array(
	'file' => '.php',
	'class' => ''
);
//path = 'extension/ezmassaction/classes/'. $module->currentView (). '/';
//$path = preg_replace ('/modules\/?/', '', $module->Path, 1). 'classes/'. $module->currentView ().'/';
$count = 1;
$path = str_replace('modules', '/', $module->Path, $count). 'classes/'. $module->currentView ().'/';
$path = str_replace("//", '/', $path);

if ($module->isCurrentAction ('reset-process')){
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
	//echo '<br /> step restart metadata';
	//var_dump($step2->parameters);
	//var_dump($step2->MetaData);

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
	echo '<br />parameters: <br />';
	var_dump($parameters);//['subtrees']
	//var_dump($step->MetaData);

	//echo '<br /> Errors: ';
	//var_dump($step->ErrorList);

	return $Result;
}

return $module->redirectURL ($module->currentModule (). '/'. $module->currentView ());
