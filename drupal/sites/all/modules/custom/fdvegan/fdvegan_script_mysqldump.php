<?php
/**
 * fdvegan_script_mysqldump.php
 *
 * A manual script outside of normal functionality for module fdvegan.
 * This script can be used to dump and then reload all fdvegan DB data.
 * This file never actually needs to be run, instead it's better to simply
 * uninstall the fdvegan module, delete it, and reinstall it again; this way
 * all the necessary fdvegan tables will get recreated from scratch correctly.
 * 
 * This script is merely an external backup&restore utility that might help in
 * the case of an unusably slow reinstall and repopulation of all the fdvegan data.
 *
 * Make sure you also make a backup of the images dir: .../drupal/sites/default/files/tmdb
 *
 * PHP version 5.6
 *
 * @category   Install
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.0
 * @see        fdvegan.install
 */


/*
 * This script is not really necessary since /fdvegan-init-load
 * can be run after installing this module, or at any time to refresh
 * our database.
 * But, if desired, this script can be run manually on the command-line via:  
 *   cd ...\drupal\sites\all\modules\custom\fdvegan; php fdvegan_script_mysqldump.php
 */


echo "ERROR: Please read the comments at the end of the code in this file.\n";
exit;

/*
-- To create a dumpfile on Dev if needed:

cd D:\Internet\Bitnami\wampstack-5.6.23-1\mysql\bin
cd D:\Internet\htdocs\GreenGeeks\non-published\fivedegreevegan.aprojects.org\database
D:\Internet\Bitnami\wampstack-5.6.23-1\mysql\bin\mysqldump mobrksco_fivedv --user=mobrksco_fivedv --password=redacted --host=127.0.0.1 --port=3306 --protocol=TCP --complete-insert --comments --dump-date --tables dr_fdvegan_cast_list dr_fdvegan_connections dr_fdvegan_genre dr_fdvegan_media dr_fdvegan_movie dr_fdvegan_movie_genre dr_fdvegan_person dr_fdvegan_person_tag dr_fdvegan_process_status dr_fdvegan_quote dr_fdvegan_tag --result-file=fdvegan_mysqldump_20170907.sql

-- Then to load the dumpfile on Dev if needed:

cd D:\Internet\htdocs\GreenGeeks\non-published\fivedegreevegan.aprojects.org\database
D:\Internet\Bitnami\wampstack-5.6.23-1\mysql\bin\mysql mobrksco_fivedv --user=mobrksco_fivedv --password=redacted --host=127.0.0.1 --port=3306 --protocol=TCP < fdvegan_mysqldump_20170907.sql

 */
