gpm.panel.Manage = function (config) {
    config = config || {};
    Ext.apply(config, {
        border: false,
        baseCls: 'modx-formpanel',
        cls: 'container',
        items: [{
            html: '<h2>' + _('gpm.manage.page_title') + '</h2>',
            border: false,
            cls: 'modx-page-header'
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            activeItem: 0,
            hideMode: 'offsets',
            items: [{
                title: _('gpm.manage.packages'),
                layout: 'form',
                items: [
                    {
                        xtype: 'gpm-grid-packages',
                        preventRender: true,
                        cls: 'main-wrapper'
                    }
                ]
            }]
        }]
    });
    gpm.panel.Manage.superclass.constructor.call(this, config);
};
Ext.extend(gpm.panel.Manage, MODx.Panel);
Ext.reg('gpm-panel-manage', gpm.panel.Manage);
