<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gpc/gitpackageconfig.class.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/builder/gitpackagebuilder.class.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/processors/mgr/gitpackage/buildpackage.class.php';
/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementBuildPackagePublishProcessor extends GitPackageManagementBuildPackageProcessor {

    public function process() {

        parent::process();

        $source = $this->config->getPackagePath() . '/_packages/' . $this->builder->getTPBuilder()->getSignature() . '.transport.zip';
        $target = MODX_BASE_PATH . 'extras/_packages/' . $this->builder->getTPBuilder()->getSignature() . '.transport.zip';
        copy($source, $target);
        chmod($target, 0666);

        $package_info = MODX_BASE_PATH . 'extras/_packages/' . $this->builder->getTPBuilder()->package->name . '.info.php';
        if (!file_exists($package_info)) {
            $info_file = fopen($package_info, 'w');
            fwrite($info_file, "<?php\n" .
                "return array('repo' => 'Main',\n" .
                "    'name' => '{$this->config->getLowCaseName()}',\n" .
                "    'displayName' => '{$this->config->getName()}',\n" .
                "    'version' => \$pp->getLatestVersion('{$this->config->getLowCaseName()}'),\n" .
                "    'dir' => '_packages',\n" .
                "    'description' => '{$this->config->getDescription()}',\n" .
                "    'author' => '{$this->config->getAuthor()}',\n" .
                "    'modx_version' => '2.3',\n" .
                "    'users' => 'none-{$this->modx->site_id}');\n");
            fclose($info_file);
        }
        chmod($package_info, 0666);

        return $this->success();
    }
}
return 'GitPackageManagementBuildPackagePublishProcessor';
