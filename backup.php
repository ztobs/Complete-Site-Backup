<?php
ini_set('max_execution_time', 7200);
ini_set('memory_limit', '512M');
set_time_limit(0);

// Dropbox Paramemters
$key = "ats6lbh040sgo36";
$secret = "0gwo8sw8jdvy922";
$accessToken = "i4Na0MD9-WAAAAAAAAAACvmnu8solLDOifGTrdOcyXlFozZQJOYV3D27lhXqnttF";
require_once "backuptools/dropbox-sdk-php-1.1.6/lib/Dropbox/autoload.php";
use \Dropbox as dbx;
$dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");


// Zipping parameters
require_once "backuptools/zip-lib/FlxZipArchive.php";
$za = new FlxZipArchive;
$folder_to_zip = '/hermes/bosnaweb17a/b2189/ipg.whatcareerng/whatcareerng/';


// Database Export Params
require('backuptools/db-export-lib/bk_zip.php'); /* include zip lib */
require('backuptools/db-export-lib/bk_db.php'); /*include export code lib*/
require "blog/wp-config.php";



function pushDropbox($key, $secret, $accessToken, $upFile )
{
    global $dbxClient;


    $dropbox_config = array(
                              'key'    => $key,
                              'secret' => $secret
                            );


    // Uploading the file
    $f = fopen($upFile, "rb");
    $result = $dbxClient->uploadFile("/".$upFile, dbx\WriteMode::add(), $f);
    fclose($f);

    // Get file info
    $file = $dbxClient->getMetadata("/".$upFile);

    // sending the direct link:
    $dropboxPath = $file['path'];
    $pathError = dbx\Path::findError($dropboxPath);
    if ($pathError !== null) {
        fwrite(STDERR, "Invalid <dropbox-path>: $pathError\n");
        die;
    }

    // The $link is an array!
    $link = $dbxClient->createTemporaryDirectLink($dropboxPath);
    // adding ?dl=1 to the link will force the file to be downloaded by the client.
    $dw_link = $link[0]."?dl=1";

    echo "$upFile pushed to dropbox <br>";
    return $link;
}



function zipAll($the_folder, $zip_file_name)
{
    global $za;
    $date_stamp = str_replace(":", "", date("Y-m-d H:i:s"));
    $zip_file_name = $zip_file_name.$date_stamp.".zip";
    $res = $za->open($zip_file_name, ZipArchive::CREATE);
    if($res === TRUE)    {
        $za->addDir($the_folder, basename($the_folder)); $za->close();
        echo $zip_file_name." created <br>";
        return $zip_file_name;
    }
    else  { echo 'Could not create a zip archive <br>';}
}





backup_tables(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME); //dumping db to sql
$zip = zipAll($folder_to_zip, "whatcareer_backup"); //zipping all files
pushDropbox($key, $secret, $accessToken, $zip); //pushing to dropbox

/*Delete file zip and sql after backup*/
$files_db = glob("*.sql");
foreach($files_db as $file_db) {
    if(is_file($file_db)) { 
        unlink($file_db);
    }
}
unlink($zip);