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
    * @param array $options
    * @param array $object
    * @return bool
    */
    public function __invoke(&$modx, $action, $options, $object)
    {
        $this->modx =& $modx;
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
