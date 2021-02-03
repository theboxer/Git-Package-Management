gpm.grid.Packages = function (config) {
    config = config || {};

    Ext.applyIf(config, {
        url: MODx.config.connector_url,
        baseParams: {
            action: 'GPM\\Processors\\GitPackage\\GetList'
        },
        save_action: 'GPM\\Processors\\GitPackage\\UpdateFromGrid',
        autosave: true,
        preventSaveRefresh: false,
        fields: ['id', 'name', 'description', 'author', 'version', 'updatedon'],
        paging: true,
        remoteSort: true,
        autoHeight: true,
        emptyText: _('gpm.packages.none'),
        columns: [
            {
                header: _('id'),
                dataIndex: 'id',
                sortable: true,
                hidden: true,
                width: 0.1
            },
            {
                header: _('gpm.package.name'),
                dataIndex: 'name',
                sortable: true,
                width: 0.3
            },
            {
                header: _('gpm.package.description'),
                dataIndex: 'description',
                sortable: true,
                width: 0.3
            },
            {
                header: _('gpm.package.author'),
                dataIndex: 'author',
                sortable: true,
                width: 0.1
            },
            {
                header: _('gpm.package.version'),
                dataIndex: 'version',
                sortable: true,
                width: 0.1
            },
            {
                header: _('gpm.package.updatedon'),
                dataIndex: 'updatedon',
                sortable: true,
                width: 0.2
            }
        ],
        tbar: [{
            text: _('gpm.package.install'),
            handler: this.installPackage,
            scope: this
        }]
    });
    gpm.grid.Packages.superclass.constructor.call(this, config);
};
Ext.extend(gpm.grid.Packages, MODx.grid.Grid, {

    getMenu: function (g, ri, e) {
        var m = [];

        m.push({
            text: _('gpm.package.build'),
            handler: this.build
        });

        if (this.menu.record && (this.menu.record.name !== 'gpm')) {
            m.push({
                text: _('gpm.package.uninstall_short'),
                handler: this.uninstall
            });
        }

        return m;
    },

    installPackage: function (btn, e) {
        var self = this;
        const installPackageWindow = MODx.load({
            xtype: 'gpm-window-install_package',
            onSuccess: function() {
                self.refresh();
            }
        });

        installPackageWindow.fp.getForm().reset();
        installPackageWindow.show(e.target);
    },

    uninstall: function (btn, e) {
        if (!this.menu.record) return false;
        var self = this;

        gpm.loggedConfirmAction(
            _('gpm.package.uninstall'),
            _('gpm.package.uninstall_desc', {name: this.menu.record.name}),
            'GitPackage\\Remove',
            {
                id: this.menu.record.id
            },
            function() {
                self.refresh();
            }
        );

        return true;
    },

    build: function (btn, e) {
        if (!this.menu.record) return false;

        gpm.loggedAction(
            'GitPackage\\Build',
            {
                id: this.menu.record.id
            }
        );

        return true;
    }
});
Ext.reg('gpm-grid-packages', gpm.grid.Packages);
