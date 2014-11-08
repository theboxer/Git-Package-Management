<?php
/**
 * @property string $name Name of the package
 * @property string $description Description of the package
 * @property string $version Version of the package
 * @property string $author Author of the package
 * @property string $config JSON config
 * @property string $dir_name Name of directory where package's repository is cloned
 * @property string $key Key used to access package via cli
 *
 * @package gitpackagemanagement
 */
class GitPackage extends xPDOSimpleObject {
    public function save($cacheFlag = null) {
        if (empty($this->key)) {
            $this->key = md5($this->id . time() . $this->dir_name);
        }

        return parent::save($cacheFlag);
    }
}
?>