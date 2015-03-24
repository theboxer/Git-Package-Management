var GitPackageManagement = function(config) {
    config = config || {};
    GitPackageManagement.superclass.constructor.call(this,config);
};
Ext.extend(GitPackageManagement,Ext.Component,{
    page:{},window:{},grid:{},tree:{},panel:{},combo:{},config: {}
});
Ext.reg('gitpackagemanagement',GitPackageManagement);
GitPackageManagement = new GitPackageManagement();