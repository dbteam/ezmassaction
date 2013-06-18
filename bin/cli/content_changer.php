<?php


require 'autoload.php';


// Init script

$cli = eZCLI::instance();
$endl = $cli->endlineString();

$script = eZScript::instance(
	array(
		'description' => (
			"CLI script. \n\n". "Will change attributes content set with wizard. \n". "\n". 'converter.php -s site_admin'
		),
		'use-session' => false,
		'use-modules' => true,
		'use-extensions' => true
	)
);
$script->startup();

$options = $script->getOptions (
	'[db-user:][db-password:][db-database:][db-driver:][sql][parent-catalog:][filename-part:][admin-user:]
	[scriptid:][name]',
  array (
		'db-host' => 'Database host',
		'db-user' => 'Database user',
		'db-password' => 'Database password',
		'db-database' => 'Database name',
		'db-driver' => 'Database driver',
		'sql' => 'Display sql queries',
		'parent-catalog' => 'Catalog contains the file',
		'filename-part' => 'Part of filename to read with serialized object data (without extension)',
		'admin-user' => 'Alternative login for the user to perform operation as',
		'scriptid' => 'Used by the Script Monitor extension, do not use manually'
	)
);
$script->initialize();

// Log in admin user
if ( isset( $options['admin-user'] ) )
{
	$adminUser = $options['admin-user'];
}
else
{
	$adminUser = 'admin';
}
$user = eZUser::fetchByName( $adminUser );
if ( $user )
	eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'id' ) );
else
{
	$cli->error( 'Could not fetch admin user object' );
	$script->shutdown( 1 );
	return;
}


// Take care of script monitoring
$scheduledScript = false;
if (
	isset ($options['scriptid'])
	and in_array ('ezmassaction', eZExtension::activeExtensions())
	and class_exists ('eZScheduledScript')
){
	$scriptID = $options['scriptid'];
	$scheduledScript = eZScheduledScript::fetch ($scriptID);
}


// get data from file
/**
 * @param $filename_part = 'module_name/file_name'
 */
if (!empty ($options['filename-part'])){
	$filename_part = $options['filename-part'];
}
else{
	$cli->error ('Missing filename !');
	$script->shutdown();
}

//$filename = 'file_name_without_file_extension';
// 'my_file'
$filename_part = str_replace ('\.xml', '', $filename_part);

$parent_catalog = $options['parent-catalog'];


//'var/cache/<cache dir setted in site.ini>/module_name/' . $filename_part . '.xml';
// egsample: modulname/modulename_2. '.xml'
// egsample: modulname/modulename_2
// egsample: $parent_catalog = modulename
if (!$parent_catalog){
	$parent_catalog  = preg_replace ('/_\d/', '', $filename_part);
}
$_file_path = eZSys::rootDir (). '/'. eZSys::storageDirectory (). '/'. $parent_catalog. '/' ;

$ma_xml = new MA_XML_File ('', $_file_path, $filename_part );
if (!$ma_xml){
	$cli->error( $ma_xml->get_error() );
	$script->shutdown (1);

	return;
}
if (!$ma_xml->fetch_file ()){

	$cli->error ($ma_xml->get_error (). ' Look to log file.');
	$script->shutdown (1);

	return;
}

$cli->endlineString ();
$cli->output ('Procesing... ');

$xml_arr = array ();
$xml_arr = $ma_xml->get_data_arr ();
if (!isset ($xml_arr['cron'])){
	$xml_arr['cron']['step'] = 0;
	$xml_arr['cron']['offset'] = 0;
	$xml_arr['cron']['limit'] = 10;
	$xml_arr['cron']['subtree'] = array ();
}

if ( $scheduledScript ){
	$scheduledScript->updateProgress( 1 ); // after class conversion set process as 1%
}

$_subtree_counter = 0;

foreach ($xml_arr['parents_nodes_ids'] as $_key => $_id){
	//$ma_tree_nodes = new MA_Content_Object_Tree_Nodes_List($_id, $xml_arr['section_identifier'], $xml_arr['class_identifier'], $xml_arr['locales_codes']);

	$ma_tree_nodes = new MA_Content_Object_Tree_Nodes_List (
		$_id, $xml_arr['section_identifier'], $xml_arr['class_identifier'], $xml_arr['locales_codes'], 0
	);

	$ma_tree_nodes->set_to_change_nodes_tree_attribute_content (
		$xml_arr['attribute_identifier'], $xml_arr['attribute_content'], true, $xml_arr['cron']['offset'], $xml_arr['cron']['limit']
	);

	do{
		$ma_tree_nodes->change_nodes_tree_attribute_content ();
		$xml_arr['cron']['subtree'][$_subtree_counter] = $ma_tree_nodes->get_change_result ();
	}

	while ($xml_arr['cron']['subtree'][$_subtree_counter]['end_flag']);

	// now a small scam
	$progressPercentage = (($xml_arr['cron']['subtree'][$_subtree_counter]['counter'] / $xml_arr['cron']['subtree'][$_subtree_counter]['count'])
		/ (count ($xml_arr['parents_nodes_ids']) - $_subtree_counter) * 100;

	$cli->output( sprintf( ' %01.1f %%', $progressPercentage ) );

	if ($scheduledScript){
		$scheduledScript->updateProgress ($progressPercentage);
	}

	$_subtree_counter++;
}

$ma_xml->set_data_arr ($xml_arr);
$ma_xml->rewrite_file ();

unset ($ma_xml);
unset ($ma_tree_nodes);
unset ($xml_arr);

$cli->endlineString ();
$cli->output ('Well done!');

$script->shutdown();
?>
