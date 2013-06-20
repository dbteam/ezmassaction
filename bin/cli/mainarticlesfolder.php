<?php
require 'autoload.php';


function microtime_float()
{
	return microtime( true );
}

set_time_limit( 0 );

$cli = eZCLI::instance();

$script = eZScript::instance(
	array(
		'description' => "Sets nodes localize in main article folder node as main node of article. \n\n".
			"mainarticlefolder.php -s site_admin \n",
		'use-session' => false,
		'use-modules' => true,
		'use-extensions' => true
	)
);


$main_articles_folder = new MainArticlesFolder ($script, $cli);
$main_articles_folder->set_current_node_as_main_node ();

unset ($main_articles_folder);

$script->shutdown( 0 );

class MainArticlesFolder {

	protected $article_folder_node_id;
	protected $main_article_folder_node_id;
	protected $main_article_folder_node;
	protected $nodes;
	protected $node;
	protected $objects_class_identifier;
	protected $objects;
	protected $depth;
	protected $nodes_changed_counter;

	protected $db;
	protected $script;
	protected $cli;
	protected $options;

	protected $siteaccess;
	protected $user_admin_name;
	protected $user_admin;
	protected $show_SQL_flag;

	protected $log_file_name;


	function __construct (eZScript $_script, eZCLI $_cli){
		$this->log_file_name = __CLASS__;

		$this->script = $_script;
		$this->cli = $_cli;

		$this->script->startup();
		$this->options = $this->script->getOptions (
			"[sql][user-admin-name:][parent-node-id:][objects-class-identifier:]",
			"",
			array (
				'sql' => 'Display sql queries',
				'user-admin-name' => 'Alternative login for the user to perform operation as',
				'parent-node-id' => 'node id of node contain articles',
				'objects-class-identifier' => 'objects class identifier to change main node'
			)
		);
		$this->script->initialize();

		$this->siteaccess = ($this->options['siteaccess'] ? $this->options['siteaccess'] : false);


		$this->db = eZDB::instance ();

		$this->user_admin_name = ($this->options['user-admin-name']? $this->options['user-admin-name']: 'admin');
		$this->user_admin = eZUser::fetchByName( $this->user_admin_name );
		if ($this->user_admin){
			eZUser::setCurrentlyLoggedInUser ($this->user_admin, $this->user_admin->attribute ('id'));
		}

		$this->show_SQL_flag = $this->options['sql'] ? true : false;
		$this->db->setIsSQLOutputEnabled ($this->show_SQL_flag);

		$this->main_article_folder_node_id = ($this->options['parent-node-id']? (int) $this->options['parent-node-id']: 269584);

		$this->objects_class_identifier = ($this->options['objects-class-identifier']?
			$this->options['objects-class-identifier']: 'article_simple');

		$this->depth = 1;
		$this->nodes_changed_counter = 0;

	}

	protected function fetch_articles (){
		$this->nodes = eZFunctionHandler::execute (
			'content', 'list',
			array (
				'parent_node_id' => $this->main_article_folder_node_id,
				//'sort_by' => array ('path', false()),
				'sort_by' => array ('published', true),
				'class_filter_type' => array ('include'),
				'class_filter_array' => array ($this->objects_class_identifier),
				'as_object' => true,
				'depth' => $this->depth,
				'ignore_visibility' => true,
				'load_data_map' => false
			)
		);
	}

	/**
	 * Sets node localize in main_article_folder_node as main node of article
	 *
	 */
	public function set_current_node_as_main_node (){
		$this->cli->output ('Starting change main node of objects: ');

		eZLog::write (
			//__METHOD__. ' line: '. __LINE__. " \n".
			'Starting change main node of objects: ',
			$this->log_file_name. '.log'
		);

		$startTS = microtime_float();

		$this->fetch_articles ();
		$this->change_objects_main_node ();

		$endTS = microtime_float();


		eZLog::write (
			"\n".
			'  Works finished.'. "\n".
			'  works time: '. ( $endTS - $startTS ). ' secs '. "\n".
			'  Count of changed nodes:'. $this->nodes_changed_counter. "\n".
			'  main_article_folder_node_id '. $this->main_article_folder_node_id. "\n",
			$this->log_file_name. '.log'
		);

		$this->cli->output (
			"\n".
			'  Works finished.'. "\n".
			'  works time: '. ( $endTS - $startTS ). ' secs '. "\n".
			'  Count of changed nodes:'. $this->nodes_changed_counter. "\n".
			'  main_article_folder_node_id '. $this->main_article_folder_node_id. "\n".
			'  Log file name: '. $this->log_file_name. '.log'. "\n"
		);
	}
	protected function change_objects_main_node (){

		if (!isset ($this->nodes[0])){
			$this->cli->output('  No nodes fetched.');
			eZLog::write (
				'  No nodes fetched',
				$this->log_file_name. '.log'
			);
		}
		else{

			foreach ($this->nodes as $key => $this->node){
				if ($this->node->attribute ('main_node_id') != $this->node->attribute ('node_id')){
					$previous_main_node_id = $this->node->attribute ('main_node_id');

					$mainAssignmentParentID = $this->node->attribute ('parent_node_id');
					if ( eZOperationHandler::operationIsAvailable ('content_updatemainassignment'))
					{
						$operationResult = eZOperationHandler::execute (
							'content', 'updatemainassignment',
							array(
								'main_assignment_id' => $this->node->attribute ('node_id'),
								'object_id' => $this->node->attribute ('contentobject_id'),
								'main_assignment_parent_id' => $this->node->attribute ('parent_node_id'), null, true
							)
						);
					}
					else
					{
						eZContentOperationCollection::UpdateMainAssignment (
							$this->node->attribute ('node_id'), $this->node->attribute ('contentobject_id'), $this->node->attribute ('parent_node_id')
						);
					}

					eZLog::write (
						"\n".
						'  Node id: '. $this->node->attribute ('node_id'). "\n".
						'  Object id: '. $this->node->attribute ('contentobject_id'). "\n".
						'  Previous Main node id: '. $previous_main_node_id. "\n".
						'  New Main node id: '. $this->node->attribute ('node_id'). "\n",
						$this->log_file_name. '.log'
					);

					$this->nodes_changed_counter++;
				}

			}
		}


	}


}