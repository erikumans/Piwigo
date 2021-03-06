<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2016 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

$upgrade_description = 'remove user upload as core feature and save config for Community plugin';

$user_upload_conf = array();

// upload_user_access
$conf_orig = $conf;
load_conf_from_db();
$user_upload_conf['upload_user_access'] = $conf['upload_user_access'];
$conf = $conf_orig;

// unvalidated photos submitted by users
$query = '
SELECT *
  FROM '.PREFIX_TABLE.'waiting
;';
$result = pwg_query($query);
$user_upload_conf['waiting_rows'] = array();
while ($row = pwg_db_fetch_assoc($result)) {
  array_push($user_upload_conf['waiting_rows'], $row);
}

// uploadable categories
$query = '
SELECT id
  FROM '.CATEGORIES_TABLE.'
  WHERE uploadable = \'true\'
;';
$result = pwg_query($query);
$user_upload_conf['uploadable_categories'] = array();
while ($row = pwg_db_fetch_assoc($result)) {
  array_push($user_upload_conf['uploadable_categories'], $row['id']);
}

// save configuration for a future use by the Community plugin
$backup_filepath = PHPWG_ROOT_PATH.$conf['data_location'].'plugins/core_user_upload_to_community.php';
$save_conf = true;
if (is_dir(dirname($backup_filepath)))
{
  if (!is_writable(dirname($backup_filepath)))
  {
    $save_conf = false;
  }
}
elseif (!is_writable( PHPWG_ROOT_PATH.$conf['data_location'] ))
{
  $save_conf = false;
}

if ($save_conf)
{
  mkgetdir(dirname($backup_filepath));

  file_put_contents(
    $backup_filepath,
    '<?php $user_upload_conf = \''.serialize($user_upload_conf).'\'; ?>'
    );
}

//
// remove all what is related to user upload in the database
//

// categories.uploadable
pwg_query('ALTER TABLE '.CATEGORIES_TABLE.' DROP COLUMN uploadable;');

// waiting
pwg_query('DROP TABLE '.PREFIX_TABLE.'waiting;');

// config parameter settings : upload_user_access, upload_link_everytime
$query = '
DELETE FROM '.PREFIX_TABLE.'config
  WHERE param IN (\'upload_user_access\', \'upload_link_everytime\', \'email_admin_on_picture_uploaded\')
;';
pwg_query($query);

echo
"\n"
. $upgrade_description
."\n"
;
?>
