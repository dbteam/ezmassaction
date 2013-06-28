<?php
$Module = array ("name" => "Mass action Objects creation");

$ViewList = array ();
$ViewList['index'] = array (
	"script" => "index.php",
	'functions' => array ('read'),
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'Index' => 'index',
		'Objects' => 'creation'
	),
	'custom_view_parameters' => array (
		'index' => array (
			'url_alias' => 'massaction/index'
		),
		'creation' => array (
			'url_alias' => 'maobjects/creation'
		),
		'default' => array(
			'url_alias' => 'massaction/index'
		)
	),
	'params' => array ()
);

$ViewList['creation'] = array (
	"script" => "creation-objects.php",
	'functions' => array ('edit'),
	'ui_context' => 'read',
	//'ui_context' => 'edit',
	'ui_component' => 'content',
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'SetSection' => 'set-section',
		'SetClass' => 'set-class',
		'SetLanguages' => 'set-languages',
		'SetName' => 'set-base-name',
		'SetParentNode' => 'set-parent-node',
		'CreateObjects' => 'create-objects',
		'ShowResult' => 'show-result',

		'PreviousButton' => 'step_back',
		'FirstStep' => 'back-to-first-step',
		'RestartButton' => 'reset-process'
	),
	'post_action_parameters' => array (
		'set-section' => array (
			'section-id' => 'SectionID',
			'section-identifier' => 'SectionIdentifier'
		),
		'set-class' => array (
			'class-id' => 'ClassID',
			'class-identifier' => 'ClassIdentifier'
		),
		'set-languages' => array (
			'languages-codes' => 'Languages'
		),
		'set-name' => array (
			'base-name' => 'Name'
		),
		'set-parent-node' => array (
			'parent-node-id' => 'ParentNode'
		),
		'create-objects' => array (
			'attribute_content' => 'Content',
			'step_by_step' => 'StepByStep'
		),
		'show-result' => array (),

		'back-to-first-step' => array (),
		'step-back' => array (),
		'reset-process' => array ()
	),
	'custom_view_parameters' => array (
		'set-section' => array (
			'next-step' => array (
				'url_alias' => 'maobjects/creation'
			),
			'step-back' => array (
				'url_alias' => 'maobjects/creation'
			)
		),
		'set-class' => array (
			'next-step' => array (
				'url_alias' => 'creation'
			),
			'step-back' => array (
				'url_alias' => 'creation'
			)
		),
		'set-languages' => array (
			'next-step' => array (
				'url_alias' => 'creation'
			),
			'step-back' => array (
				'url_alias' => 'creation'
			)
		),
		'show-result' => array (
			'next-step' => array (
				'url_alias' => 'index'
			),
			'step-back' => array (
				'url_alias' => 'creation'
			),
			'default' => array (
				'objects'
			)
		),

		'start' => array(
			'url_alias' => 'maobjects/creation'
		),
		'default' => array(
			'url_alias' => 'maobjects/creation'
		)
	),
	"params" => array ()
);

$FunctionList = array ();
$FunctionList['read'] = array ();
$FunctionList['edit'] = array ();

