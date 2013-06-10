<?php
/**
 * File containing ezmassaction class
 *
 * @copyright
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @author Radosław Zadroga
 * @version //autogentag//
 * @package ezmassaction
 */
/**
 * Class description here
 *
 * @version //autogentag//
 * @package ezmassaction
 */
class eZMassaction
{
    // set manually - is used in email header, and in file header @version
    const SOFTWARE_VERSION = '0.0.000124';

    static function info()
    {
        return array( 'Name'             => 'DB Team',
                      'Version'          => self::SOFTWARE_VERSION,
                      'eZ version'       => '4.4 =<',
                      'Copyright'        => '(C) 2013-' . date( 'Y' ) . ' <a href="http://"></a> [ <a href="http://"></a> &amp; <a href="http://"></a> &amp; <a href="http://"></a> ]',
                      'License'          => 'GNU General Public License v2.0',
                      'More Information' => '<a href="http://projects.ez.no/">http://projects.ez.no/</a>'
                    );
    }

    /**
     * get some additional infos about the
     * for future use
     */
    static function packageInfo()
    {
        $infoArray = array();
        $infoArray[ 'release_version' ] = '//release_version//';

        // is set when building the package
        $infoArray[ 'release_svn_revision' ] = '//release_svn_revision//';
        return $infoArray;
    }
}
?>
