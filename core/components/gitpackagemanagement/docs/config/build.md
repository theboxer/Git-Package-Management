Define build options here.

**Wrapper:** build
```json
{
    "build":{}
}
```

#### Available properties:
* **resolver** (optional) - Resolver options
* **readme** (optional, default: docs/readme.txt) - Path to readme file
* **license** (optional, default: docs/license.txt) - Path to license file
* **changelog** (optional, default: docs/changelog.txt) - Path to change log file
* **schemaPath** (optional, default: /core/components/$lowCaseName$/model/schema/$lowCaseName$.mysql.schema.xml) - Path to the XML schema file
* **setupOptions** (optional) - Setup options object
* **options** (optional) - Options object

#### Example
```json
{
    "build":{
        "readme": "docs/readme.txt",        
        "schemaPath": "_build/schema/mypackage.mysql.schema.xml"
    }
}
```

## Resolver part
Define resolver options here. Resolvers are run when a package is installed, upgraded, or uninstalled inside the MODX package manager; not when a package is updated within Git Package Management.

**Wrapper:** resolver
```json
{
    "resolver":{}
}
```

#### Available properties:
* **resolversDir** (optional, default: resolvers) - Directory for custom resolvers
* **before** (optional, default: empty array) - Array with paths to resolvers, which will be executed before assets & core file resolvers
* **after** (optional, default: empty array) - Array with paths to resolvers, which will be executed after assets & core file resolvers
* **files** (optional, default: empty array) - Array with source and target, used to create a file resolver. (File resolvers for assets & core are created automatically)
    * Available placeholders for source item: [[+assetsPath]], [[+corePath]], [[+packagePath]]

#### Example
```json
{
    "resolver":{
        "resolversDir": "resolvers",
        "after": ["resolve.customresolver.php"],
        "files": [{
            "source": "[[+packagePath]]/move_under_assets",
            "target": "return MODX_ASSETS_PATH . 'components/';"
        }]
    }
}
```

## Setup options part
Define setup options here. Setup options are requested and used during the installation process of a package.

**Wrapper:** setupOptions
```json
{
    "setupOptions":{}
}
```

#### Available properties:
* **source** (required if setup options are used) - Script that will handle setup options, must be placed in _build folder

#### Example
```json
{
    "setupOptions":{
        "source": "setup.options.php"
    }
}
```

## Build options part
Define build options here. These options could be used to modify the build process i.e. in own gitpackage processors.

**Wrapper:** options
```json
{
    "options":{}
}
```

#### Available properties:
* **empty_folders** Could contain an array of emptied folders during the build. The files in the folders are selected with the PHP glob method with the GLOB_BRACE option. The file list is inverted with a leading `!` sign.

#### Example
```json
{
    "options": {
      "empty_folders": {
        "{package_path}core/components/xxx/vendor/mpdf/mpdf/tmp" : "*",
        "{package_path}core/components/xxx/vendor/mpdf/mpdf/ttfonts" : "!{DejaVu,Free}*",
        "{package_path}core/components/xxx/vendor/mpdf/mpdf/ttfontdata" : "*"
      }
      "encrypt": true
    }
}
```

The build options could be used in a custom build processor with the following code:
 
```
$buildOptions = $this->config->getBuild()->getBuildOptions();
if ($this->modx->getOption('encrypt', $buildOptions, false)) {
    ...
}
```

##### empty_folder 

## Build helper methods

There are two build helper methods available, that could be used in a custom build processor: prependVehicles and appendVehicles. The methods are called during the build process of a package.

#### prependVehicles

This method is called in the build process before adding the first vehicle. It could be used to add own custom vehicles to the package or to empty temporary folders in the *assets/components* or *core/components*, that would be packaged otherwise automatically into the package.

#### appendVehicles

This method is called in the build process after adding the last vehicle. It could be used to add own custom vehicles to the package or to do other things at the end of a build process.

An example for the usage of both methods in a custom build processor could be found on https://github.com/Jako/Git-Package-Management/blob/feature/publish/core/components/gitpackagemanagement/processors/mgr/gitpackage/buildpackagepublish.class.php#L132-L187. This processor adds - amongst other things - two custom vehicles before the first default vehicle and one custom vehicle after the last vehicle. These vehicles are used to add methods to decrypt the category vehicle during the install process of the package.  
