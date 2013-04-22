GitPackageManagement.panel.Home = function(config) {
    config = config || {};
    Ext.apply(config,{
        border: false
        ,baseCls: 'modx-formpanel'
        ,cls: 'container'
        ,items: [{
            html: '<h2>'+_('gitpackagemanagement')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
        },{
            xtype: 'modx-tabs'
            ,defaults: { border: false ,autoHeight: true }
            ,border: true
            ,activeItem: 0
            ,hideMode: 'offsets'
            ,items: [{
                title: _('gitpackagemanagement.packages')
                ,items: [{
                    html: '<p>'+_('gitpackagemanagement.intro_msg')+'</p>'
                    ,border: false
                    ,bodyCssClass: 'panel-desc'
                },{
                    xtype: 'gitpackagemanagement-grid-packages'
                    ,preventRender: true
                    ,cls: 'main-wrapper'
                }]
            }]
        }]
    });
    GitPackageManagement.panel.Home.superclass.constructor.call(this,config);
};
Ext.extend(GitPackageManagement.panel.Home,MODx.Panel);
Ext.reg('gitpackagemanagement-panel-home',GitPackageManagement.panel.Home);