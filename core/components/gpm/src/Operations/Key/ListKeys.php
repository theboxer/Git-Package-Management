<?php

namespace GPM\Operations\Key;


use GPM\Model\APIKey;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;

class ListKeys extends Operation
{
    public function execute(GitPackage $package): void
    {
        /** @var APIKey $key[] */
        $keys = $this->modx->getIterator(APIKey::class, ['package' => $package->id]);

        foreach ($keys as $key) {
            $this->logger->warning('- ' . $key->key . ': ' . $key->permissions);
        }
    }
}
