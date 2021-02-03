<?php
abstract class {{$name}}BaseManagerController extends modExtraManagerController {
    /** @var \{{$namespace}}\{{$name}} ${{$lowCaseName}} */
    public ${{$lowCaseName}};

    public function initialize(): void
    {
        $this->{{$lowCaseName}} = $this->modx->services->get('{{$lowCaseName}}');

        $this->addCss($this->{{$lowCaseName}}->getOption('cssUrl') . 'mgr.css');
        $this->addJavascript($this->{{$lowCaseName}}->getOption('jsUrl') . 'mgr/{{$lowCaseName}}.js');

        $this->addHtml('
            <script type="text/javascript">
                Ext.onReady(function() {
                    {{$lowCaseName}}.config = '.$this->modx->toJSON($this->{{$lowCaseName}}->config).';
                });
            </script>
        ');

        parent::initialize();
    }

    public function getLanguageTopics(): array
    {
        return array('{{$lowCaseName}}:default');
    }

    public function checkPermissions(): bool
    {
        return true;
    }
}
