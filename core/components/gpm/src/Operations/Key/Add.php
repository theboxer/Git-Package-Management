<?php

namespace GPM\Operations\Key;


use GPM\Model\APIKey;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;

class Add extends Operation
{
    public function execute(GitPackage $package, array $permissions): void
    {
        /** @var APIKey $key */
        $key = $this->modx->newObject(APIKey::class);
        $key->set('package', $package->id);
        $key->set('permissions', $permissions);
        $key->save();

        $this->logger->warning('Key generated: ' . $key->key);
    }
}
