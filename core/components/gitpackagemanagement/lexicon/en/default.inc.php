<?php
/**
 * Default English Lexicon Entries for GitPackageManagement
 *
 * @package gitpackagemanagement
 * @subpackage lexicon
 */
$_lang['gitpackagemanagement'] = 'Git Package Management';
$_lang['gitpackagemanagement.menu_desc'] = 'Manage packages from GIT. Packages can be stored outside modx folder.';
$_lang['gitpackagemanagement.search'] = 'Search';
$_lang['gitpackagemanagement.packages'] = 'Packages';
$_lang['gitpackagemanagement.intro_msg'] = 'Manage packages downloaded from GIT.';


//Packages
$_lang['gitpackagemanagement.url'] = 'Url';
$_lang['gitpackagemanagement.folder'] = 'Folder';
$_lang['gitpackagemanagement.name'] = 'Name';
$_lang['gitpackagemanagement.description'] = 'Description';
$_lang['gitpackagemanagement.author'] = 'Author';
$_lang['gitpackagemanagement.version'] = 'Version';
$_lang['gitpackagemanagement.add_package'] = 'Add package';
$_lang['gitpackagemanagement.remove_package'] = 'Remove package';
$_lang['gitpackagemanagement.remove_package_confirm'] = 'Do you really want to remove this package?';
$_lang['gitpackagemanagement.update_config'] = 'Push local config to database';
$_lang['gitpackagemanagement.config_update_success'] = 'Config file was successfully pushed to database';
$_lang['gitpackagemanagement.delete_package_folder'] = 'Delete package folder';

//Errors
$_lang['gitpackagemanagement.package_err_ns_folder_name'] = 'You have to enter folder name.';
$_lang['gitpackagemanagement.package_err_ns_url'] = 'You have to enter URL.';
$_lang['gitpackagemanagement.package_err_ae_url'] = 'Package from this URL has been already installed.';
$_lang['gitpackagemanagement.package_err_ns_packages_dir'] = 'Folder for packages is not set. Set package folder in system settings.';
$_lang['gitpackagemanagement.package_err_ae_folder_name'] = 'Folder with this name already exists.';
$_lang['gitpackagemanagement.package_err_url_config_nf'] = 'This GIT repository does not contain config file.';
$_lang['gitpackagemanagement.package_err_url_config_nfif'] = 'Folder with cloned repository doesn\'t contain config file.';