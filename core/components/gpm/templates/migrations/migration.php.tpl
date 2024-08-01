<?php

return new class() {
    // migration runs if self::VERSION > currently installed version
    const VERSION = '{{$version}}';
{literal}
    /**
    * @var \MODX\Revolution\modX
    */
    private $modx;

    /**
     * @param \MODX\Revolution\modX $modx
     * @return void
     */
    public function __invoke(&$modx)
    {
        $this->modx =& $modx;
        // Migration code goes here
    }
};
{/literal}