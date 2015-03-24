Ext.onReady(function() {
    MODx.load({ xtype: 'gitpackagemanagement-page-home'});
});

GitPackageManagement.page.Home = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        components: [{
            xtype: 'gitpackagemanagement-panel-home'
            ,renderTo: 'gitpackagemanagement-panel-home-div'
        }]
    });
    GitPackageManagement.page.Home.superclass.constructor.call(this,config);
};
Ext.extend(GitPackageManagement.page.Home,MODx.Component);
Ext.reg('gitpackagemanagement-page-home',GitPackageManagement.page.Home);