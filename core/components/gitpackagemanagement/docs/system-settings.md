All system settings are located under **gitpackagemanagement** namespace.

### Packages directory
Key: `gitpackagemanagement.packages_dir`  
Absolute path to the directory where are all your packages stored.

### Packages base URL
Key: `gitpackagemanagement.packages_base_url`  
Default: `/`  
Base URL for packages directory.  

### Enable remote debugging
Key: `gitpackagemanagement.enable_debug`    
Default: `false`  
Activating this setting, plugins and snippets are no longer created as static elements, but the static file is required from the content field. This way it's possible to debug them directly.  

### Remove extracted transport package
Key: `gitpackagemanagement.remove_extracted_package`  
Default: `false`  
Activating this setting, the extracted transport package is removed after building.

### Disable creating anything in the MODX db
Key: `gitpackagemanagement.disable_create_elements`    
Default: `false`  
Activating this setting, GPM lets the MODX installation alone and doesn't create anything. This is useful, when you want GPM to just create a package, without altering the MODX installation.

### Disable updating anything in the MODX db
Key: `gitpackagemanagement.disable_update_elements`    
Default: `false`  
Activating this setting, GPM lets the MODX installation alone and doesn't update anything. This is useful, when you want GPM to just create a package, without altering the MODX installation.    