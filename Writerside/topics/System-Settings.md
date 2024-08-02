# System Settings

All system settings are located under `gitpackagemanagement` namespace.

## Packages directory
Key: `gitpackagemanagement.packages_dir`

Absolute path to the directory where are all your packages stored.

## Packages base URL
Key: `gitpackagemanagement.packages_base_url`

Default: `/`

Base URL for packages directory.

## Enable remote debugging
Key: `gitpackagemanagement.enable_debug`

Default: `false`

Activating this setting, plugins and snippets are no longer created as static elements, but the static file is required from the content field. This way it's possible to debug them directly.

## Build Path
Key: `gitpackagemanagement.build_path`

Default: `_packages`

Name of the folder to store built packages.