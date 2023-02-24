<?php
namespace GPM\Endpoint;


use GPM\Model\APIKey;
use GPM\Operations\Build;
use GPM\Operations\Remove;
use GPM\Operations\Update;
use MODX\Revolution\modX;

class Endpoint {
    private $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function run(): void
    {
        $action = $this->modx->getOption('action', $_REQUEST, '');
        $key = $this->modx->getOption('key', $_REQUEST, '');
        $alterDB = intval($this->modx->getOption('alter_db', $_REQUEST, '0')) === 1;
        $recreateDB = intval($this->modx->getOption('recreate_db', $_REQUEST, '0')) === 1;

        if (empty($key)) {
            $this->failure('key is required');
            return;
        }

        if (empty($action)) {
            $this->failure('action is required');
            return;
        }

        /** @var APIKey $apiKey */
        $apiKey = $this->modx->getObject(APIKey::class, ['key' => $key]);
        if (!$apiKey) {
            $this->failure('invalid key');
            return;
        }

        $permissions = $apiKey->get('permissions');
        if (!isset($permissions[$action])) {
            $this->failure('insufficient permissions: ' . $action);
            return;
        }

        if ($action === 'update') {
            if ($alterDB && !isset($permissions['update']['alterDB'])) {
                $this->failure('insufficient permissions: ' . $action . ':alterDB');
                return;
            }

            if ($recreateDB && !isset($permissions['update']['recreateDB'])) {
                $this->failure('insufficient permissions: ' . $action . ':recreateDB');
                return;
            }
        }

        $package = $apiKey->Package;

        switch ($action) {
            case 'update': {
                /** @var Update $operation */
                $operation = $this->modx->services->get(Update::class);
                $operation->execute($package, $recreateDB, $alterDB);
                $this->success();
                return;
            }

            case 'build': {
                /** @var Build $operation */
                $operation = $this->modx->services->get(Build::class);
                $operation->execute($package->dir_name);
                $this->success();
                return;
            }

            case 'remove': {
                /** @var Remove $operation */
                $operation = $this->modx->services->get(Remove::class);
                $operation->execute($package);
                $this->success();
                return;
            }
        }

        $this->failure('unsupported action');
    }

    private function failure($msg): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => $msg]);
    }

    private function success()
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true]);
    }
}