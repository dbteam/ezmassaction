<?php
$module = $Params['Module'];
 //eZModule::
//$Module->actionParameter( 'ContentObjectID' );
$userParameters = '';
if ( isset( $Params['UserParameters'] ) )
{
	$userParameters = $Params['UserParameters'];
}
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

/*
$http = eZHTTPTool::instance();
$userParameters = array ();
$persistent_variable = array ();

if ( isset( $Params['UserParameters'] ) )
{
	$userParameters = $Params['UserParameters'];
}
$tpl = eZTemplate::factory();
*/

/*

$template_name = 'index';
if ($module->isCurrentAction ('change_attribute_content') ){
	$action['url_alias'] = $module->currentModule (). '/'. $module->Functions['index']['custom_view_parameters']['change_attribute_content']['url_alias'];

	$persistent_variable['form']['action'] = $action;

	$template_name = 'attribute_content';
}
else{
	$attribute_content = new Attribute_content($Params);
	$persistent_variable = $attribute_content->get_parameters();

	$action['url_alias'] = $module->currentModule (). '/'. $module->Functions['index']['custom_view_parameters']['default']['url_alias'];
	$persistent_variable['form']['action'] = $action;
//	$tpl->setVariable('params', $Params);

	$template_name = 'attribute_content';
}


$tpl->setVariable( 'persistent_variable', $persistent_variable );


$Result = array();
//$Result['content'] = isset( $result ) ? $result : null;
$Result['content'] = $tpl->fetch ('design:massaction/'. $template_name. '.tpl');
$Result['view_parameters'] = $userParameters;
$Result['persistent_variable'] = $tpl->variable ('persistent_variable');
$Result['content_info'] = array(
	'persistent_variable' => $tpl->variable ('persistent_variable'),
	'object_id' => false,
	'node_id' => false
);
/*
$Result['path'][] = array(
	'text' => $module->Module['name'],
	'url' => '',
	'url_alias' => ''
);
*/
/*
$Result['path'][] = array(
	'text' => $module->Module['name'],//'Index',
	'url' => $module->uri (). '/'. $module->currentView (),
	'url_alias' => $module->currentModule (). '/'. $module->currentView ()
);
$Result['default_navigation_part'] = $module->Functions['index']['default_navigation_part'];
*/
