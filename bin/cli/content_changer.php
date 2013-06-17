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

$db = eZDB::instance();

// Take care of script monitoring
$scheduledScript = false;
if (
	isset ($options['scriptid'])
	and in_array ('ezmassaction', eZExtension::activeExtensions())
	and class_exists ('eZScheduledScript')
){
	$scriptID = $options['scriptid'];
	$scheduledScript = eZScheduledScript::fetch( $scriptID );
}


// get data from file
/**
 * @param $filename_part = 'module_name/file_name'
 */
if (isset ($options['filename-part'])){
	$filename_part = $options['filename-part'];
}
else{
	$cli->error( 'Missing datafile name !' );
	$script->shutdown();
}

//$filename = 'var/cache/' . $filename_part . '.xml';
$filename_part = str_replace ('\.xml', '', $filename_part);

$parent_catalog = $options['parent-catalog'];


// modulname/modulename_2
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
$xml_arr = $ma_xml->get_data_arr ();




//$object = unserialize( $contents );


$cli->endlineString();

//$handler_name = $object->variable( 'source_datatype_string') . '2' . $object->variable( 'target_datatype_string' );

//$cli->output( 'Procesing handler: ' . $handler_name );


//xxxx there I brake the work

// initialize convert handler
$converter = new $handler_name();

// transaction begin - note that with transaction it's not possible to see progress in script monitoring tool
$db = eZDB::instance();
//$db->begin();

// do preAction()
$converter->preAction( $object );


// do class attribute conversion
$contentClassAttribute = eZContentClassAttribute::fetch( $object->variable( 'attribute_id' ) );

$converter->convertClassAttribute( $contentClassAttribute, $object );

if ( $scheduledScript ){

	$scheduledScript->updateProgress( 1 ); // after class conversion set process as 1%
}

// do postConvertClassAttributeAction()
$converter->postConvertClassAttributeAction( $object );

// do preConvertObjectAttributesAction()
$converter->preConvertObjectAttributesAction( $object );
			
// fetch attributes just to count			
$total_attribute_count = DBAttributeConverter::getContentObjectAttributeCount( $object->variable( 'attribute_id' ) );

// do object attributes conversion - all versions
$conditions = array( "contentclassattribute_id" => $object->variable( 'attribute_id' ) );
$offset = 0;
$limit = 100;
$counter = 0;

while ( true )
{
	$objectsArray = eZPersistentObject::fetchObjectList( eZContentObjectAttribute::definition(),
		null,
		$conditions,
		null,
		array( 'limit' => $limit,
			'offset' => $offset ),
		$asObject = true);

	if ( !$objectsArray || count( $objectsArray ) == 0 )
	{
		break;
	}

	$offset+=$limit;

	foreach ( $objectsArray as $attributeObject )
	{
		$converter->convertObjectAttribute( $attributeObject, $object );
		$cli->output( '#', false );
		$counter++;
	}

	// Progress bar and Script Monitor progress
	$progressPercentage = ( $counter / $total_attribute_count ) * 100;
	$cli->output( sprintf( ' %01.1f %%', $progressPercentage ) );
	if ( $scheduledScript )
	{
		$scheduledScript->updateProgress( $progressPercentage );
	}

}
// do postAction()
$converter->postAction( $object );

// transaction commit
//$db->commit();

// remove used file
unlink( $filename );


$script->shutdown();
?>
