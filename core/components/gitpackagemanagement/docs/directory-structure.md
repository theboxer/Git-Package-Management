## All in one folder
This is the option I prefer and set up on my local dev environment.

Localhost `http://localhost` points to `/var/www/` directory, where is located MODX directory and all packages.

- var
    - www
        - modx `http://localhost/modx`
            - assets
            - core
            - setup    
            - ...
        - package1 `http://localhost/package1`
            - assets
            - core
            - ...
        - package2 `http://localhost/package2`
            - assets
            - core
            - ...
        
In this case set [Packages directory](system-settings/#packages-directory) to `/var/www/` and [Packages base URL](system-settings/#packages-base-url) to `/`.

## Separate MODX, separate packages

Localhost `http://localhost` points to `/var/www/` directory, where the MODX directory is located.  
All packages are located in `/var/www/packages/`.

- var
    - www
        - modx `http://localhost/modx`
            - assets
            - core
            - setup
            - ...
        - packages
            - package1 `http://localhost/packages/package1`
                - assets
                - core
                - ...
            - package2 `http://localhost/packages/package2`
                - assets
                - core
                - ...

In this case set [Packages directory](system-settings/#packages-directory) to `/var/www/packages/` and [Packages base URL](system-settings/#packages-base-url) to `/packages/`.

## Packages under MODX
This option I mostly use on remote environments like [MODX cloud](https://modxcloud.com).

Localhost `http://localhost` points to `/var/www/` directory, where is located MODX.  
All packages are located in `/var/www/packages/`.

- var
    - www `http://localhost`
        - assets
        - core
        - manager
        - setup
        - packages
            - package1 `http://localhost/packages/package1`
                - assets
                - core
                - ...
            - package2 `http://localhost/packages/package1`
                - assets
                - core
                - ...
        - ...
        
In this case set [Packages directory](system-settings/#packages-directory) to `/var/www/packages/` and [Packages base URL](system-settings/#packages-base-url) to `/packages/`.