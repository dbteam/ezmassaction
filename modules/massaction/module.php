<?php
$Module = array ("name" => "Mass action");

$ViewList = array ();
$ViewList['index'] = array (
	"script" => "index.php",
	'functions' => array ('read'),
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'Main' => 'wizard',
	),
	'custom_view_parameters' => array (
		'wizard' => array (
			'url_alias' => 'wizard'
		),
		'default' => array(
			'url_alias' => 'wizard'
		)
	),

	'params' => array ()
);

$ViewList['wizard'] = array (
	"script" => "wizard.php",
	'functions' => array ('edit'),
	'ui_context' => 'read',
	//'ui_context' => 'edit',
	'ui_component' => 'content',
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'GetAttributesList' => 'get_attributes_list',
		'GetAttribute' => 'get_attribute',
		'ChangeAttributeContent' => 'change_attribute_content',
		'ShowResult' => 'show_result',

		'PreviousButton' => 'step_back',
		'RestartButton' => 'restart_process'
	),
	'post_action_parameters' => array (
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
			'attribute_content' => 'Content',
			'step_by_step' => 'StepByStep'
		),
		'show_result' => array (),

		'step_back' => array (),
		'restart_process' => array ()
	),
	'custom_view_parameters' => array (
		'get_attributes_list' => array (
			'next_step' => array (
				'url_alias' => 'wizard'
			),
			'step_back' => array (
				'url_alias' => 'wizard'
			)
		),
		'get_attribute' => array (
			'next_step' => array (
				'url_alias' => 'wizard'
			),
			'step_back' => array (
				'url_alias' => 'wizard'
			),
		),
		'change_attribute_content' => array (
			'next_step' => array (
				'url_alias' => 'wizard'
			),
			'step_back' => array (
				'url_alias' => 'wizard'
			),
		),
		'show_result' => array (
			'next_step' => array (
				'url_alias' => 'wizard'
			),
			'step_back' => array (
				'url_alias' => 'wizard'
			),
		),

		'start' => array(
			'url_alias' => 'wizard'
		),
		'default' => array(
			'url_alias' => 'wizard'
		)
	),
	"params" => array ()
);


$FunctionList = array ();
$FunctionList['read'] = array ();
$FunctionList['edit'] = array ();

