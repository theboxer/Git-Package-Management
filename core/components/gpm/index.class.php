<?php
/**
 * @package gpm
 */

abstract class GPMBaseManagerController extends modExtraManagerController {
    /** @var \GPM\GPM $gpm */
    public $gpm;

    public function initialize(): void
    {
        $this->gpm = $this->modx->services->get('gpm');

        $this->addCss($this->gpm->getOption('cssUrl').'mgr.css');
        $this->addJavascript($this->gpm->getOption('jsUrl').'mgr/gpm.js');
        $this->addHtml('<script type="text/javascript">
        
        Ext.onReady(function() {
            gpm.config = '.$this->modx->toJSON($this->gpm->config).';
        });
        </script>');

        parent::initialize();
    }

    public function getLanguageTopics(): array
    {
        return array('gpm:default');
    }

    public function checkPermissions(): bool
    {
        return true;
    }
}
