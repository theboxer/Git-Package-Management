
GitPackageManagement.grid.Packages = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'gitpackagemanagement-grid-packages'
        ,url: GitPackageManagement.config.connectorUrl
        ,baseParams: {
            action: 'mgr/gitpackage/getlist'
        }
        ,fields: ['id','name','description', 'author', 'version']
        ,autoHeight: true
        ,paging: true
        ,remoteSort: true
        ,enableDragDrop: false
        ,columns: [{
            header: _('id')
            ,dataIndex: 'id'
            ,width: 70
            ,hidden: true
        },{
            header: _('gitpackagemanagement.name')
            ,dataIndex: 'name'
            ,width: 200
        },{
            header: _('gitpackagemanagement.description')
            ,dataIndex: 'description'
            ,width: 250
        },{
            header: _('gitpackagemanagement.author')
            ,dataIndex: 'author'
            ,width: 250
        },{
            header: _('gitpackagemanagement.version')
            ,dataIndex: 'version'
            ,width: 250
        }]
        ,tbar: [{
            text: _('gitpackagemanagement.add_package')
            ,handler: this.createItem
            ,scope: this
        },'->',{
            xtype: 'textfield'
            ,id: 'gitpackagemanagement-search-filter'
            ,emptyText: _('gitpackagemanagement.search')+'...'
            ,listeners: {
                'change': {fn:this.search,scope:this}
                ,'render': {fn: function(cmp) {
                    new Ext.KeyMap(cmp.getEl(), {
                        key: Ext.EventObject.ENTER
                        ,fn: function() {
                            this.fireEvent('change',this);
                            this.blur();
                            return true;
                        }
                        ,scope: cmp
                    });
                },scope:this}
            }
        }]
    });
    GitPackageManagement.grid.Packages.superclass.constructor.call(this,config);
};
Ext.extend(GitPackageManagement.grid.Packages,MODx.grid.Grid,{
    windows: {}
    ,console: null
    ,getMenu: function() {
        var m = [];
        m.push({
            text: _('gitpackagemanagement.update_package')
            ,handler: this.updatePackage
        });
        m.push({
            text: _('gitpackagemanagement.update_package_database')
            ,handler: this.updatePackageAndDatabase
        });
        m.push('-');
        m.push({
            text: _('gitpackagemanagement.remove_package')
            ,handler: this.removeItem
        });
        this.addContextMenuItem(m);
    }

    ,updatePackage: function(){
        MODx.Ajax.request({
            url: GitPackageManagement.config.connectorUrl
            ,params: {
                action: 'mgr/gitpackage/update'
                ,id: this.menu.record.id
                ,recreateDatabase: 0
            }
            ,listeners: {
                'success':{fn:function(r) {
                    MODx.msg.alert(_('gitpackagemanagement.update_package'), _('gitpackagemanagement.update_package_success'));
                    this.refresh();
                },scope:this}
            }
        });
    }

    ,updatePackageAndDatabase: function(){
        MODx.Ajax.request({
            url: GitPackageManagement.config.connectorUrl
            ,params: {
                action: 'mgr/gitpackage/update'
                ,id: this.menu.record.id
                ,recreateDatabase: 1
            }
            ,listeners: {
                'success':{fn:function(r) {
                    MODx.msg.alert(_('gitpackagemanagement.update_package'), _('gitpackagemanagement.update_package_success'));
                    this.refresh();
                },scope:this}
            }
        });
    }
    
    ,createItem: function(btn,e) {
        if (!this.windows.addPackage) {
            this.windows.addPackage = MODx.load({
                xtype: 'gitpackagemanagement-window-add-package'
                ,listeners: {
                    'success': {fn:function() { this.refresh(); },scope:this}
                    ,'beforeSubmit': {fn:function() {
                        var topic = '/gitpackageinstall/';
                        var register = 'mgr';
                        if(this.console == null){
                            this.console = MODx.load({
                                xtype: 'modx-console'
                                ,register: register
                                ,topic: topic
                                ,show_filename: 0
                            });
                        }
                        this.console.show(Ext.getBody());
                    },scope:this}
                }
            });
        }
        this.windows.addPackage.fp.getForm().reset();
        this.windows.addPackage.show(e.target);
    }
    
    ,removeItem: function(btn,e) {
        if (!this.menu.record) return false;

        if (!this.windows.removePackage) {
            this.windows.removePackage = MODx.load({
                xtype: 'gitpackagemanagement-window-remove-package'
                ,listeners: {
                    'success': {fn:function() { this.refresh(); },scope:this}
                    ,'beforeSubmit': {fn:function() {
                        var topic = '/gitpackageuninstall/';
                        var register = 'mgr';
                        if(this.console == null){
                            this.console = MODx.load({
                                xtype: 'modx-console'
                                ,register: register
                                ,topic: topic
                                ,show_filename: 0
                            });
                        }
                        this.console.show(Ext.getBody());
                    },scope:this}
                }
            });
        }
        this.windows.removePackage.fp.getForm().reset();
        this.windows.removePackage.setValues(this.menu.record);
        this.windows.removePackage.show(e.target);
    }

    ,search: function(tf,nv,ov) {
        var s = this.getStore();
        s.baseParams.query = tf.getValue();
        this.getBottomToolbar().changePage(1);
        this.refresh();
    }

});
Ext.reg('gitpackagemanagement-grid-packages',GitPackageManagement.grid.Packages);