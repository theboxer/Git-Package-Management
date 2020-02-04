<?php
namespace GitPackageManagement\Model;

use xPDO\xPDO;

/**
 * Class GitPackage
 *
 * @property string $name
 * @property string $description
 * @property string $version
 * @property string $author
 * @property string $dir_name
 * @property string $config
 * @property string $key
 * @property integer $updatedon
 *
 * @package GitPackageManagement\Model
 */
class GitPackage extends \xPDO\Om\xPDOSimpleObject
{
}
