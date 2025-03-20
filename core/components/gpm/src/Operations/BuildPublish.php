<?php

namespace GPM\Operations;

use Exception;
use FilesystemIterator;
use GPM\Config\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use MODX\Revolution\Transport\modPackageBuilder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TreehillStudio\Packeteer\Packeteer;
use xPDO\xPDO;

class BuildPublish extends Build
{

    /** @var Packeteer $packeteer */
    public $packeteer;

    private $vendorPath;
    private $tempVendorPath;
    private $phpVersion;

    public function execute(string $dir): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');

        try {
            $corePath = $this->modx->getOption('packeteer.core_path', null, $this->modx->getOption('core_path') . 'components/packeteer/');
            $this->packeteer = $this->modx->getService('packeteer', 'Packeteer', $corePath . 'model/packeteer/', array(
                'core_path' => $corePath
            ));

            $this->config = Config::load($this->modx, $this->logger, $packages . $dir . DIRECTORY_SEPARATOR);

            $this->phpVersion = $this->getPhpVersion();

            $this->scanPacketeerPackages();
            $this->moveTempComposer();

            $this->builder = new modPackageBuilder($this->modx);

            $this->prepareExternalScripts();
            $this->cleanupLexicons();

            $this->loadSmarty();
            $this->package = $this->createPackage();

            $this->packInstallValidator();

            $this->packNamespace();
            $this->packScripts('before');

            $this->packSystemSettings();
            $this->packMenu();
            $this->packDB();
            $this->packMainCategory();
            $this->packWidgets();

            $this->packFred();

            $this->packMigrations();

            $this->packScripts('after');

            $this->packUnInstallValidator();

            $this->setPackageAttributes();

            $this->package->pack();

            $this->moveBackComposer();

            $this->createUpload();

            $this->scanPacketeerPackages();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->warning('Package built.');
    }

    private function getPhpVersion()
    {
        if (!empty($this->config->build->options['php_version'])) {
            return $this->config->build->options['php_version'];
        }
        exec('/Applications/MAMP/bin/php/php/bin/php -r "echo phpversion();" 2>&1', $execResult, $execVal);
        if ($execVal != 0) {
            $this->logger->error('Get PHP version issue!');
            return '7.4.33';
        }
        return implode('', $execResult);
    }

    /**
     * @throws Exception
     */
    private function prepareExternalScripts()
    {
        $execVal = 0;
        $execResult = array();
        if (file_exists($this->config->paths->package . 'Gruntfile.js')) {
            exec('export PATH=$PATH:/usr/local/bin; /usr/local/bin/grunt --gruntfile=' . $this->config->paths->package . 'Gruntfile.js default 2>&1', $execResult, $execVal);
            if ($execVal != 0) {
                $this->logger->error('Grunt issue!' . "\n" . implode("\n", $execResult));
                throw new Exception('Package not built.');
            }
            $this->logger->notice('Grunt successful.');
        }

        $execVal = 0;
        $execResult = array();
        if (file_exists($this->config->paths->package . 'gulpfile.js')) {
            exec('export PATH=$PATH:/usr/local/bin; /usr/local/bin/gulp --gulpfile=' . $this->config->paths->package . 'gulpfile.js default 2>&1', $execResult, $execVal);
            if ($execVal != 0) {
                $this->logger->error('Gulp issue!' . "\n" . implode("\n", $execResult));
                throw new Exception('Package not built.');
            }
            $this->logger->notice('Gulp successful.');
        }

        $execVal = 0;
        $execResult = array();
        if (file_exists($this->config->paths->package . 'core/components/' . $this->config->general->lowCaseName . '/composer.json')) {
            exec('export PATH=$PATH:/usr/local/bin:/Applications/MAMP/bin/php/php' . $this->phpVersion . '/bin; export COMPOSER_HOME=/Applications/MAMP/bin/php/composer; /Applications/MAMP/bin/php/composer licenses --format=json --working-dir=' . $this->config->paths->package . 'core/components/' . $this->config->general->lowCaseName . '/' . ' 2>&1', $execResult, $execVal);
            if ($execVal != 0) {
                $this->logger->error('Composer issue!');
                throw new Exception('Composer issue!' . '<br>' . implode('<br>', $execResult));
            } else {
                $result = json_decode(implode('', $execResult), true);
                $dependencies = $result['dependencies'] ?? [];
                $packages = [];
                foreach ($dependencies as $name => $info) {
                    $packages[] = '* ' . $name . '@' . ($info['version'] ?? 'unknown') . ' [' . ($info['license'][0] ?? 'unknown') . ']';
                }
            }
            $packages = implode("\n", $packages);

            if ($packages) {
                $packages = "## Third party licenses\n\nThis extra includes third party software, for which we are thankful.\n\n" . $packages;
                $filename = $this->config->paths->package . 'core/components/' . $this->config->general->lowCaseName . '/docs/readme.md';
                $content = file_get_contents($filename);
                if ($content && strpos($content, '## Third party licenses')) {
                    $content = preg_replace('/## Third party licenses.*$/s', $packages, $content);
                } else {
                    $content = $content . "\n\n" . $packages;
                }
                file_put_contents($filename, $content);
            }

            exec('export PATH=$PATH:/usr/local/bin:/Applications/MAMP/bin/php/php' . $this->phpVersion . '/bin; export COMPOSER_HOME=/Applications/MAMP/bin/php/composer; /Applications/MAMP/bin/php/composer install --prefer-dist --no-dev --no-progress --optimize-autoloader --working-dir=' . $this->config->paths->package . 'core/components/' . $this->config->general->lowCaseName . '/' . ' 2>&1', $execResult, $execVal);
            $this->logger->info('Running composer for ' . $this->config->general->name . ' ' . $this->config->general->version);
            if ($execVal != 0) {
                $this->logger->error('Composer issue!' . "\n" . implode("\n", $execResult));
                throw new Exception('Package not built.');
            }
            $this->logger->notice('Composer successful.');
        }

        $execVal = 0;
        $execResult = array();
        if (file_exists($this->config->paths->package . 'test/phpunit.xml')) {
            exec('export PATH=$PATH:/usr/local/bin:/Applications/MAMP/bin/php/php' . $this->phpVersion . '/bin; /usr/local/bin/phpunit --configuration ' . $this->config->paths->package . 'test/phpunit.xml 2>&1', $execResult, $execVal);
            if ($execVal != 0) {
                $this->logger->error('phpUnit issue!' . "\n" . implode("\n", $execResult));
                throw new Exception('Package not built.');
            }
            $this->logger->notice('phpUnit successful.');
        }
    }

    /**
     * @return mixed
     */
    private function cleanupLexicons()
    {
        $lexiconPath = $this->config->paths->package . '/core/components/' . $this->config->general->lowCaseName . '/lexicon/';
        if (file_exists($lexiconPath)) {
            $lexiconPathIterator = new RecursiveDirectoryIterator($lexiconPath, FilesystemIterator::SKIP_DOTS);
            $filesExist = false;
            foreach (new RecursiveIteratorIterator($lexiconPathIterator, RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD) as $file => $info) {
                if (in_array($info->getFilename(), array('_variable.php', '_missing.php', '_superfluous.php'))) {
                    @unlink($info->getRealPath());
                    $filesExist = true;
                }
            }
            if ($filesExist) {
                $this->logger->info('Lexicon test files deleted.');
            }
        }
    }

    /**
     * @return void
     */
    private function moveTempComposer()
    {
        $useComposer = $this->config->build->options['composer'] ?? false;
        if ($useComposer) {
            // Don't include the vendor folder in the package
            $this->vendorPath = $this->config->paths->package . '/core/components/' . $this->config->general->lowCaseName . '/vendor/';
            $this->tempVendorPath = $this->config->paths->package . '/temp_vendor/';
            rename($this->vendorPath, $this->tempVendorPath);
            $this->logger->notice('Temporary move the vendor folder from the package.');
        }
    }

    /**
     * @return void
     */
    private function moveBackComposer(): void
    {
        $useComposer = $this->config->build->options['composer'] ?? false;
        if ($useComposer) {
            // Move the vendor folder back
            rename($this->tempVendorPath, $this->vendorPath);
            $this->logger->notice('Move the vendor folder back into the package.');
        }
    }

    private function deleteDir($dirPath)
    {
        if (is_dir($dirPath)) {
            $files = scandir($dirPath);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dirPath . '/' . $file))
                        $this->deleteDir($dirPath . '/' . $file);
                    else
                        unlink($dirPath . '/' . $file);
                }
            }
            rmdir($dirPath);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function createUpload(): void
    {
        $this->deleteDir($this->config->paths->package . '/_packages/' . $this->package->signature . '/');
        chmod($this->config->paths->package . '/_packages/', octdec($this->modx->getOption('new_folder_permissions', null, '0775')));
        chmod($this->config->paths->package . '/_packages/' . $this->package->signature . '.transport.zip', octdec($this->modx->getOption('new_file_permissions', null, '0644')));

        $source = $this->config->paths->package . '/_packages/' . $this->package->signature . '.transport.zip';
        chmod($source, 0666);
        $packageAttributes = $this->package->attributes;

        // the build options can have changed in the external scripts

        $packageInfoArray = array(
            'name' => $this->config->general->lowCaseName,
            'displayname' => $this->config->general->name,
            'description' => $this->config->general->description,
            'author' => $this->config->general->author,
            'instructions' => mb_convert_encoding($packageAttributes['readme'], 'UTF-8', 'ISO-8859-1'),
            'changelog' => mb_convert_encoding($packageAttributes['changelog'], 'UTF-8', 'ISO-8859-1'),
            'license' => mb_convert_encoding($packageAttributes['license'], 'UTF-8', 'ISO-8859-1'),
            'modx_version' => $this->config->build->__get('requires')['modx'] ?? $this->packeteer->getOption('minimal_modx_version')
        );
        $packageInfo = "<?php\n" .
            'return json_decode(\'' . json_encode($packageInfoArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '\', true);' . "\n";

        if ($this->packeteer->getOption('sftp_user')) {

            $filesystem = new Filesystem(new SftpAdapter(
                new SftpConnectionProvider(
                    $this->packeteer->getOption('sftp_serverurl'),
                    $this->packeteer->getOption('sftp_user'),
                    null,
                    $this->packeteer->getOption('sftp_privatekey'),
                    $this->packeteer->getOption('sftp_secret')
                ),
                $this->packeteer->getOption('sftp_serverpath'),
                PortableVisibilityConverter::fromArray([
                    'file' => [
                        'public' => 0664,
                        'private' => 0644,
                    ],
                    'dir' => [
                        'public' => 0775,
                        'private' => 0755,
                    ],
                ])
            ));

            try {
                $file = fopen($source, 'r');
                $filesystem->writeStream(basename($source), $file);
            } catch (FilesystemException|UnableToWriteFile $exception) {
                $this->logger->error('SFTP Error uploading package: ' . $exception->getMessage());
                throw new Exception('Upload error.');
            }

            $this->logger->notice('Upload the package per FTP to the package provider.');

            $package_info = $this->config->paths->package . '/_packages/' . $this->config->general->name . '.info.php';
            $info_file = fopen($package_info, 'w');
            fwrite($info_file, $packageInfo);
            fclose($info_file);
            chmod($package_info, 0666);

            try {
                $file = fopen($package_info, 'r');
                $filesystem->writeStream(basename($package_info), $file);
            } catch (FilesystemException|UnableToWriteFile $exception) {
                $this->logger->error('SFTP Error uploading package info: ' . $exception->getMessage());
                throw new Exception('Upload error.');
            }

            $this->logger->notice('Upload the package info per FTP to the package provider.');
        } else {
            $targetPath = realpath(MODX_BASE_PATH . $this->packeteer->getOption('site_extras_path'));
            $target = $targetPath . '/_packages/' . $this->package->signature . '.transport.zip';
            copy($source, $target);
            chmod($targetPath . '/_packages/', 0777);
            chmod($target, 0666);

            $package_info = $targetPath . '/_packages/' . $this->config->general->name . '.info.php';
            $info_file = fopen($package_info, 'w');
            fwrite($info_file, $packageInfo);
            fclose($info_file);
            chmod($package_info, 0666);
            $this->logger->notice('Update the package info file.');
        }
    }

    /**
     * @return mixed
     */
    private function scanPacketeerPackages()
    {
        $packageName = $this->config->general->lowCaseName;
        $beta = (bool)preg_match('/.*?-(dev|a|alpha|b|beta|rc)\\d*/i', $this->package->signature);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->packeteer->getOption('site_url') . 'rest/packeteer/package/scan/' . $packageName . '?' . http_build_query(array(
                    'beta' => (string)$beta,
                    'hash' => hash('sha256', $this->packeteer->getOption('site_id') . $packageName . ((string)$beta))
                )),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        if ($result == null) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'cURL Error scan package: ' . curl_error($ch));
            throw new Exception('Scan package error.');
        }
        if ($result['success']) {
            $this->logger->notice('Scan package: ' . $result['message']);
        } else {
            $this->logger->error('Scan package: ' . $result['message']);
        }

        curl_close($ch);
        return $result;
    }
}
