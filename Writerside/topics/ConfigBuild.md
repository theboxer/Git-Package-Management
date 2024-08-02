<show-structure for="none" depth="0"></show-structure>

# Build

<tldr>
<p><b>Wrapper:</b> menus</p>
<p><b>Properties:</b> <a href="#readme">readme</a>,<a href="#license">license</a>,<a href="#changelog">changelog</a>,<a href="#scriptsbefore">scriptsBefore</a>,<a href="#scriptsafter">scriptsAfter</a>,<a href="#requires">requires</a>,<a href="#setupoptions">setupOptions</a>,<a href="#installvalidator">installValidator</a>,<a href="#uninstallvalidator">unInstallValidator</a></p>
</tldr>

## Properties

### readme
`optional` `default: README.md (if file exists)`

### license
`optional` `default: LICENSE.md (if file exists)`

### changelog
`optional` `default: CHANGELOG.md (if file exists)`

### scriptsBefore
`optional` `array`

List of scripts that will execute right after namespace is installed, before all other objects.

You can create <tooltip term="GPM Script">GPM Script</tooltip> with a help of CLI command `gpm {pkg}:script:create script-name`. This script will than run during the build and also during GPM install/update/remove actions.  
### scriptsAfter
`optional` `array`

List of scripts that will execute after all objects and migrations are installed.

You can create <tooltip term="GPM Script">GPM Script</tooltip> with a help of CLI command `gpm {pkg}:script:create script-name`. This script will than run during the build and also during GPM install/update/remove actions.

### requires
`optional`

### setupOptions
`optional`

### installValidator
`optional`

### unInstallValidator
`optional`