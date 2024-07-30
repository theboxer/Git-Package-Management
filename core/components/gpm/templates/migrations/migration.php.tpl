<?php

class Migration{{$versionPackaged}} {
    // migration runs if self::VERSION > currently installed version
    const VERSION = '{{$version}}';
{literal}
    /**
     * @param \MODX\Revolution\modX $modx
     * @return void
     */
    public function __invoke($modx)
    {
        // Migration code goes here
    }
}
{/literal}