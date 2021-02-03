var GPM = function (config) {
    config = config || {};
    GPM.superclass.constructor.call(this, config);
};

Ext.extend(GPM, Ext.Component, {
    page: {},
    window: {},
    grid: {},
    tree: {},
    panel: {},
    combo: {},
    config: {},

    _prepareLogger: function(action, params, onSuccess) {
        if (!onSuccess) {
            onSuccess = function(){};
        }

        var topic = '/' + action.replace('\\', '/') + '/';
        action = '\\GPM\\Processors\\' + action;

        var register = 'gpm';

        params.action = action;
        params.register = register;
        params.topic = topic;

        var modxConsole = null;

        var showLogger = function () {
            modxConsole = MODx.load({
                xtype: 'modx-console',
                closeAction: 'close',
                register: register,
                topic: topic,
                clear: true,
                show_filename: 0
            });

            modxConsole.show(Ext.getBody());
        };

        return {
            showLogger: showLogger,
            requestParams: {
                url: MODx.config.connector_url,
                params: params,
                listeners: {
                    success: {
                        fn: function() {
                            onSuccess();
                        },
                        scope:this
                    },
                    failure: {
                        fn: function() {
                            modxConsole.fireEvent('complete');
                            onSuccess();
                        },
                        scope:this
                    }
                }
            }
        };
    },

    loggedAction: function(action, params, onSuccess) {
        var logger = this._prepareLogger(action, params, onSuccess);

        logger.showLogger();
        MODx.Ajax.request(logger.requestParams);
    },

    loggedConfirmAction: function(title, text, action, params, onSuccess) {
        var logger = this._prepareLogger(action, params, onSuccess);

        Ext.Msg.confirm(title, text, function(e) {
            if (e === 'yes') {
                logger.showLogger();
                MODx.Ajax.request(logger.requestParams);
            }
        });
    }
});

Ext.reg('gpm', GPM);
gpm = new GPM();
