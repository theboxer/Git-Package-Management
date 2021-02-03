var {{$namespace}} = function (config) {
    config = config || {};
    {{$namespace}}.superclass.constructor.call(this, config);
};
Ext.extend({{$namespace}}, Ext.Component, {
{literal}
    page: {},
    window: {},
    grid: {},
    tree: {},
    panel: {},
    combo: {},
    field: {},
    config: {},
{/literal}
});
Ext.reg('{{$lowCaseName}}', {{$namespace}});
{{$lowCaseName}} = new {{$namespace}}();
