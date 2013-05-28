<?php
$Module = array ("name" => "Mass action");

$ViewList = array ();
$ViewList['index'] = array (
	"script" => "index.php",
	'functions' => array ('read'),
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'AttributeContent' => 'attributecontent',
	),
	'custom_view_parameters' => array (
		'attributecontent' => array (
			'next_module' => '',
			'url_alias' => 'attributecontent'
		),
		'default' => array(
			'url_alias' => 'attributecontent'
		)
	),

	'params' => array ()
);
$ViewList['attributecontent'] = array (
	"script" => "change_attribute_content.php",
	'functions' => array ('edit'),
	//'ui_context' => 'edit',
	//'ui_component' => 'content',
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'Cancel' => 'cancel',
		'StepBack' => 'back_step',
		'GetAttributesList' => 'get_attributes_list',
		'GetAttribute' => 'get_attribute',
		'ChangeAttributeContent' => 'change_attribute_content'
	),
	'post_action_parameters' => array (
		'cancel' => array (),

		'get_attributes_list' => array (
			'parents_nodes_ids' => 'Nodes_IDs',
			'section_id' => 'Section',
			'class_id' => 'Class',
			'locales_codes' => 'Locales'
		),
		'get_attribute' => array (
			'attribute_id' => 'AttributeID'
		),
		'change_attribute_content' => array (
			'attribute_content' => 'Content'
		)
	),
	'custom_view_parameters' => array (
		'back_step' => array (
			'next_module' => '',
			'url_alias' => 'attributecontent'
		),
		'get_attributes_list' => array (
			'url_alias' => 'attributecontent'
		),
		'get_attribute' => array (
			'url_alias' => 'attributecontent'
		),
		'change_attribute_content' => array (
			'url_alias' => 'attributecontent'
		),
		'default' => array(
			'url_alias' => 'attributecontent'
		)
	),
	"params" => array ()
);

$FunctionList = array ();
$FunctionList['read'] = array ();
$FunctionList['edit'] = array ();
