<?php

namespace GPM\Operations\Key;


use GPM\Model\APIKey;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;

class Remove extends Operation
{
    public function execute(GitPackage $package, string $key): void
    {
        /** @var APIKey $key */
        $key = $this->modx->getObject(APIKey::class, ['package' => $package->id, 'key' => $key]);

        if (!$key) {
            $this->logger->warning('Key ' . $key . ' not found.');
            return;
        }

        $removed = $key->remove();
        if ($removed) {
            $this->logger->warning('Key ' . $key . ' successfully removed');
            return;
        }

        $this->logger->error('Failed removing key ' . $key);
    }
}
