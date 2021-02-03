gpm.window.InstallPackage = function(config) {
    config = config || {};

    var logger = gpm._prepareLogger(
        'GitPackage\\Install',
        {},
        config.onSuccess || function(){}
    );

    logger.requestParams.listeners.beforeSubmit = {
        fn: function() {
            logger.showLogger();
        },
        scope:this
    };

    logger.requestParams.title = _('gpm.package.install');
    logger.requestParams.width = 475;
    logger.requestParams.modal = true;
    logger.requestParams.autoHeight = true;
    logger.requestParams.baseParams = logger.requestParams.params;
    delete logger.requestParams.params;
    logger.requestParams.fields = [{
        xtype: 'textfield',
        fieldLabel: _('gpm.package.dir'),
        name: 'dir',
        anchor: '100%'
    }];

    Ext.applyIf(config,logger.requestParams);
    gpm.window.InstallPackage.superclass.constructor.call(this,config);
};
Ext.extend(gpm.window.InstallPackage, MODx.Window);
Ext.reg('gpm-window-install_package', gpm.window.InstallPackage);
