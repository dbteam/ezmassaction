<?php
$module = $Params['Module'];

$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();

$template_name = 'attribute_content';
$persistent_variable = array ();
$userParameters = array ();

if (isset ($Params['UserParameters']) )
{
	$userParameters = $Params['UserParameters'];
}

$stepArray = array();

$stepArray[] = array(
	'file' => 'attribute_content.php',
	'class' => 'Attribute_content'
);

$attribute_content = new Attribute_content($Params,);
$persistent_variable = $attribute_content->get_parameters();
$persistent_variable['errors'] = $attribute_content->get_errors();

$tpl->setVariable( 'persistent_variable', $persistent_variable );
$tpl->setVariable ('params', $Params);
var_dump ($Params);


$Result = array();
//$Result['content'] = isset( $result ) ? $result : null;
$Result['content'] = $tpl->fetch ('design:massaction/'. $template_name. '.tpl');
$Result['view_parameters'] = $userParameters;

$Result['persistent_variable'] = $tpl->variable ('persistent_variable');
$Result['content_info'] = array(
	'persistent_variable' => $tpl->variable ('persistent_variable'),
	'default_navigation_part' => $module->Functions['attributecontent']['default_navigation_part'],
	'object_id' => false,
	'node_id' => false
);

$Result['path'][] = array(
	'text' => $module->Module['name'],
	'url' => '',
	'url_alias' => ''
);

$Result['path'][] = array(
	'text' => 'Change attribute content',//'Index',
	'url' => $module->uri (). '/'. $module->currentView (),
	'url_alias' => $module->currentModule (). '/'. $module->currentView ()
);
$Result['default_navigation_part'] = $module->Functions['attributecontent']['default_navigation_part'];
