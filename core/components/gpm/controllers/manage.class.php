<?php

require_once dirname(dirname(__FILE__)) . '/index.class.php';

/**
 * Loads the home page.
 *
 * @package gpm
 * @subpackage controllers
 */
class GPMManageManagerController extends GPMBaseManagerController
{

    public function process(array $scriptProperties = []): void
    {
    }

    public function getPageTitle(): string
    {
        return $this->modx->lexicon('gpm');
    }

    public function loadCustomCssJs(): void
    {
        $this->addLastJavascript($this->gpm->getOption('jsUrl') . 'mgr/widgets/packages.window.js');
        $this->addLastJavascript($this->gpm->getOption('jsUrl') . 'mgr/widgets/packages.grid.js');
        $this->addLastJavascript($this->gpm->getOption('jsUrl') . 'mgr/widgets/manage.panel.js');
        $this->addLastJavascript($this->gpm->getOption('jsUrl') . 'mgr/sections/manage.js');

        $this->addHtml(
            '
            <script type="text/javascript">
                Ext.onReady(function() {
                    MODx.load({ xtype: "gpm-page-manage"});
                });
            </script>
        '
        );
    }

    public function getTemplateFile(): string
    {
        return $this->gpm->getOption('templatesPath') . 'manage.tpl';
    }

}
