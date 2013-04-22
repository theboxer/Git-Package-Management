<?php
/**
 * @package gitpackagemanagement
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\', '/') . '/gitpackage.class.php');
class GitPackage_mysql extends GitPackage {}
?>