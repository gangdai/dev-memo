<?php
/*
  $Id: file_manager.php 1744 2007-12-21 02:22:21Z hpdl $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/

define('HEADING_TITLE', 'File Manager');

define('TABLE_HEADING_FILENAME', 'Name');
define('TABLE_HEADING_SIZE', 'Size');
define('TABLE_HEADING_PERMISSIONS', 'Permissions');
define('TABLE_HEADING_USER', 'User');
define('TABLE_HEADING_GROUP', 'Group');
define('TABLE_HEADING_LAST_MODIFIED', 'Last Modified');
define('TABLE_HEADING_ACTION', 'Action');

define('TEXT_INFO_HEADING_UPLOAD', 'Upload');
define('TEXT_FILE_NAME', 'Filename:');
define('TEXT_FILE_SIZE', 'Size:');
define('TEXT_FILE_CONTENTS', 'Contents:');
define('TEXT_LAST_MODIFIED', 'Last Modified:');
define('TEXT_NEW_FOLDER', 'New Folder');
define('TEXT_NEW_FOLDER_INTRO', 'Enter the name for the new folder:');
define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this file?');
define('TEXT_UPLOAD_INTRO', 'Please select the files to upload.');
define('TEXT_UPLOAD_ROYALMAIL', 'Royalmail Daily Update');
define('TEXT_AMAZON_SHIPPING_CONFIRMATION', 'amazon/lcp update');
define('TEXT_AMAZON_STOCK_UPDATE', 'amazon stock update');
/*
define('TEXT_UPLOAD_AMAZON2ROYALMAIL', 'amazon -> royalmail');
define('TEXT_UPLOAD_AMAZON2LCP', 'amazon -> lcp');
define('TEXT_UPLOAD_EBAY2ROYALMAIL', 'ebay -> royalmail');
define('TEXT_UPLOAD_EBAY2LCP', 'ebay -> lcp');
*/
define('TEXT_UPLOAD_RM', 'royalmail');
define('TEXT_UPLOAD_LCP', 'lcp');
define('TEXT_UPLOAD_WEIGHT_FIRST', '1st/24');
define('TEXT_UPLOAD_WEIGHT_SECOND', '2nd/48');

define('TEXT_UPLOAD_SIZE_NONE', 'n/a');
define('TEXT_UPLOAD_SIZE_PARCEL', 'Parcel');

define('ERROR_DIRECTORY_NOT_WRITEABLE', 'Error: I can not write to this directory. Please set the right user permissions on: %s');
define('ERROR_FILE_NOT_WRITEABLE', 'Error: I can not write to this file. Please set the right user permissions on: %s');
define('ERROR_DIRECTORY_NOT_REMOVEABLE', 'Error: I can not remove this directory. Please set the right user permissions on: %s');
define('ERROR_FILE_NOT_REMOVEABLE', 'Error: I can not remove this file. Please set the right user permissions on: %s');
define('ERROR_DIRECTORY_DOES_NOT_EXIST', 'Error: Directory does not exist: %s');
define('ERROR_FILENAME_EMPTY', 'Error: Please enter a filename to store the contents in.');
?>
