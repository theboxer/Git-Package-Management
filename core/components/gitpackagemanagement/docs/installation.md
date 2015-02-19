## Package

Download the latest package from [_packages](https://github.com/TheBoxer/Git-Package-Management/tree/master/_packages) and copy it to your MODX **core/packages/** directory.

Open the MODX manager and go to the **Extras/Installer** (System/Package Management in Revolution 2.2) and search for local packages. Git Package Management package will appear in the list.

Install the package (during installation you'll be asked to provide some [information](system-settings.md)).

## CLI

- Clone GPM to your **[packages directory](system-settings/#packages-directory)**
- Open **cli** directory and run `composer install`
- Make **bin/gpm** executable `chmod +x bin/gpm`
- Run `./bin/gpm gpm:install --corePath=/absolute/path/to/modx/core/`
    - To get list of all command options run `./bin/gpm gpm:install --help`
    
## Directories required as writable

- \_build
- core/components/*$lowCaseName$*/model/*$lowCaseName$*