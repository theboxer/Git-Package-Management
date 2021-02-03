{{$lowCaseName}}.page.Manage = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [
            {
                xtype: '{{$lowCaseName}}-panel-manage',
                renderTo: '{{$lowCaseName}}-panel-manage-div'
            }
        ]
    });
    {{$lowCaseName}}.page.Manage.superclass.constructor.call(this, config);
};
Ext.extend({{$lowCaseName}}.page.Manage, MODx.Component);
Ext.reg('{{$lowCaseName}}-page-manage', {{$lowCaseName}}.page.Manage);
