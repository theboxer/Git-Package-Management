gpm.page.Manage = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [
            {
                xtype: 'gpm-panel-manage',
                renderTo: 'gpm-panel-manage-div'
            }
        ]
    });
    gpm.page.Manage.superclass.constructor.call(this, config);
};
Ext.extend(gpm.page.Manage, MODx.Component);
Ext.reg('gpm-page-manage', gpm.page.Manage);
