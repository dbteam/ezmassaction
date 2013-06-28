<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radek
 * Date: 17.06.13
 */

//this name is used only for visible name of module (not for url)
$Module = array ("name" => "Mass action");

$ViewList = array ();
/*
 * if You want to url to current module just write view alias
 * $ViewList['creation'] = array (
 * creation <- view alias
 * 'url_alias' => 'creation'
 *
 * if to other module, write module directory name and view alias ('moduleDirName/viewAlias'):
 * this module directory: massaction
 * some other module directory: maobjects
 * creation <- view alias
 * 'url_alias' => 'maobjects/creation'
 */

/*
 * single_post_actions - those data support eZP kernel it is very usefull
 * keys an values programmer define/write yourself
 *
 * custom_view_parameters - aren\'t use by eZPublish kernel/lib
 * but of course the Programmer can use those data in views
 * I create this data array for better/easier design and manage modules
 *
 * ralations between single_post_actions and custom_view_parameters - will describe in the future
 *
 */
$ViewList['index'] = array (
	"script" => "index.php",
	'functions' => array ('read'),
	'default_navigation_part' => 'ezmassaction_navigationpart',
	'single_post_actions' => array (
		'Main' => 'attribute_content',
		'Objects' => 'objects'
	),
	'custom_view_parameters' => array (
		'attribute_content' => array (
			'url_alias' => 'attribute_content'
		),
		'objects' => array (
			'url_alias' => 'creation'
		),
		'default' => array(
			'url_alias' => 'attribute_content'
		)
	),
	'params' => array ()
);

$ViewList['attribute_content'] = array (
	"script" => "changing-attribute-content.php",
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
				'url_alias' => 'attribute_content'
			),
			'step_back' => array (
				'url_alias' => 'attribute_content'
			)
		),
		'get_attribute' => array (
			'next_step' => array (
				'url_alias' => 'attribute_content'
			),
			'step_back' => array (
				'url_alias' => 'attribute_content'
			),
		),
		'change_attribute_content' => array (
			'next_step' => array (
				'url_alias' => 'attribute_content'
			),
			'step_back' => array (
				'url_alias' => 'attribute_content'
			),
		),
		'show_result' => array (
			'next_step' => array (
				'url_alias' => 'attribute_content'
			),
			'step_back' => array (
				'url_alias' => 'attribute_content'
			),
		),

		'start' => array(
			'url_alias' => 'attribute_content'
		),
		'default' => array(
			'url_alias' => 'attribute_content'
		)
	),
	"params" => array ()
);

$FunctionList = array ();
$FunctionList['read'] = array ();
$FunctionList['edit'] = array ();

