<?php
{literal}
return new class() {
    /**
     * @var \MODX\Revolution\modX
     */
    private $modx;

    /**
     * @var int
     */
    private $action;

    /**
    * @param \MODX\Revolution\modX $modx
    * @param int $action
    * @return bool
    */
    public function __invoke($modx, $action)
    {
        $this->modx = $modx;
        $this->action = $action;

        switch ($this->action) {
            case \xPDO\Transport\xPDOTransport::ACTION_INSTALL:
                break;
            case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:
                break;
            case \xPDO\Transport\xPDOTransport::ACTION_UNINSTALL:
                break;
        }

        return true;
    }
};
{/literal}