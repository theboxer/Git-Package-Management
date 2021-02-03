# GPM

This is the new version of GPM specifically for MODX Revolution 3.x. 

It doesn't work with 2.x and packages generated from this version also work only with Revolution 3.x.

## Docs
Documentation is still in progress, as this is a new major version, there isn't much that is backward compatible, not even config.json (it was renamed to gpm.json or gpm.yaml and the structure is similar but different).

Not all features of the first version of GPM are implemented here, please be patient.

## Setup
There **won't** be a transport package for this version for some time (maybe never). The only way how to install GPM is via CLI.

The required directory structure for running GPM is same as for the 1st version ([docs](http://theboxer.github.io/Git-Package-Management/directory-structure/)).

- Clone the repo
- Go to `core/components/gpm`
- Run `composer install`
- Go to the `bin` and run `chmod +x ./gpm`
- Run `./gpm` -- you should get info about available commands
- To install GPM: 
  - Check `./gpm gpm:install -h` for available options
  - Run `./gpm gpm:install` with all necessary arguments and options

## JSON Schema for the config file
You probably noticed above, the config.json is now gpm.json or gpm.yaml. YAML version is preferred, but you what suits you.

Due to the lack of documentation and for better DX, there is a JSON Schema for the config file, which will help you create the config using the new structure.

URL of the schema: `https://raw.githubusercontent.com/theboxer/Git-Package-Management/3.x/gpm.schema.json`

I'll try to submit the schema to the schemastore.org after I finish all descriptions in it. Here's manual setup:

### PhpStorm / WebStorm
- Settings / Languages & Frameworks / Schemas and DTDs / JSON Schema Mappings
- Add new schema with the url above and map it to the file `_build/gpm.yaml`

### Others
If you use other IDE/editor, please submit a PR on how to set up the custom JSON Schema.


## Issues

Did you run into an issue installing or using GPM?
- Try to figure out what and why it happened
- Try to fix it
  - It works? [Submit a PR](https://github.com/theboxer/Git-Package-Management/pulls)
  - Still doesn't work? [Submit an Issue](https://github.com/theboxer/Git-Package-Management/issues/new?labels=3.x,bug)  

## Feature Requests / Enhancements
Please hold on with all your ideas, I have my list I want to handle first. (Yes, it will be public, later)
