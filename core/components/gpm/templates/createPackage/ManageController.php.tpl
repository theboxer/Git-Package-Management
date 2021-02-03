<?php
require_once dirname(dirname(__FILE__)) . '/index.class.php';

class {{$name}}ManageManagerController extends {{$name}}BaseManagerController
{

    public function process(array $scriptProperties = []): void
    {
    }

    public function getPageTitle(): string
    {
        return $this->modx->lexicon('{{$lowCaseName}}');
    }

    public function loadCustomCssJs(): void
    {
        $this->addLastJavascript($this->{{$lowCaseName}}->getOption('jsUrl') . 'mgr/widgets/manage.panel.js');
        $this->addLastJavascript($this->{{$lowCaseName}}->getOption('jsUrl') . 'mgr/sections/manage.js');

        $this->addHtml(
            '
            <script type="text/javascript">
                Ext.onReady(function() {
                    MODx.load({ xtype: "{{$lowCaseName}}-page-manage"});
                });
            </script>
        '
        );
    }

    public function getTemplateFile(): string
    {
        return $this->{{$lowCaseName}}->getOption('templatesPath') . 'manage.tpl';
    }

}
