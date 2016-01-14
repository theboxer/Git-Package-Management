In this part we will add some elements, system settings and menus.

**Wrapper:** package
```json
{
    "package":{}
}
```
## Actions
**Wrapper:** actions (array)

```json
{
    "package":{
        "actions": []
    }
}
```
#### Available properties:
- **id** (required) - Action ID, used only in config's scope
- **controller** (required) - Controller's name (index in the most of cases)
- **hasLayout** (optional, default: 1) - Include manager layout
- **langTopics** (optional, default: $lowCaseName$:default) - Lexicon's lopic
- **assets** (optional, default: '')

#### Example
```json
{
    "package":{
        "actions": [{
            "id": 1,
            "controller": "index",
            "hasLayout": 1,
            "langTopics": "package:default",
            "assets": ""
        }]
    }
}
```
## Menus
**Wrapper:** menus (array)

```json
{
    "package":{
        "menus": []
    }
}
```
#### Available properties:
- **text** (required) - Menu text
- **description** (optional, default: '') - Menu description
- **action** (required, default: 1) - Action ID from this config / Action name (can be used in MODX 2.3+)
- **parent** (optional, default: components) - Parent menu
- **icon** (optional, default: '')
- **menuIndex** (optional, default: 0)
- **params** (optional, default: '')
- **handler** (optional, default: '')

#### Example
```json
{
    "package":{
        "actions": [{
            "id": 1,
            "controller": "index",
            "hasLayout": 1,
            "langTopics": "package:default",
            "assets": ""
        }],
        "menus": [{
            "text": "package",
            "description": "package.menu_desc",
            "action": 1,
            "parent": "components",
            "icon": "",
            "menuIndex": 0,
            "params": "",
            "handler": ""
        }]
    }
}
```

## Elements
In this section we create some elements. During the installation, all elements are created as static.
**Wrapper:** elements
```json
{
    "package":{
        "elements": {}
    }
}
```

### Chunks

**Wrapper:** chunks (array)
```json
{
    "package":{
        "elements": {
            "chunks": []
        }
    }
}
```

#### Available properties:
- **name** (required) - Chunk's name
- **category** (optional) - Name of category defined in **categories** element
- **file** (optional, default: strtolower($name$).chunk.tpl) - Chunk's filename with extension
- **properties** (optional) - An array of objects, where each object has those properties:
     - **name** (required) - Name of the property
     - **description** (optional) - Description of the property
     - **type** (optional, default: textfield) - Type of property
     - **options** (optional) - Property options
     - **value** (optional) - Property default value
     - **lexicon** (optional, default:strtolower($lowCaseName$):properties) - Lexicon topic for description
     - **area** (optional) - Area of the property

#### Example
```json
{
    "package":{
        "elements": {
            "chunks": [{
                "name": "PackageChunk",
                "file": "packagechunk.chunk.tpl",
                "properties": [{
                    "name": "testproperty",
                    "value": "testvalue"
                }]
            }]
        }
    }
}
```

### Snippets

**Wrapper:** snippets (array)
```json
{
    "package":{
        "elements": {
            "snippets": []
        }
    }
}
```

#### Available properties:
- **name** (required) - Snippet's name
- **category** (optional) - Name of category defined in **categories** element
- **file** (optional, default: strtolower($name$).snippet.php) - Snippet's filename with extension
- **properties** (optional) - An array of objects, where each object has those properties:
    - **name** (required) - Name of the property
    - **description** (optional) - Description of the property
    - **type** (optional, default: textfield) - Type of property
    - **options** (optional) - Property options
    - **value** (optional) - Property default value
    - **lexicon** (optional, default:strtolower($lowCaseName$):properties) - Lexicon topic for description
    - **area** (optional) - Area of the property

#### Example
```json
{
    "package":{
        "elements": {
            "snippets": [{
                "name": "PackageSnippet",
                "file": "packagesnippet.snippet.php",
                "properties": [{
                    "name": "testproperty",
                    "value": "testvalue"
                }]
            }]
        }
    }
}
```

### Templates

**Wrapper:** templates (array)
```json
{
    "package":{
        "elements": {
            "templates": []
        }
    }
}
```

#### Available properties:
- **name** (required) - Template's name
- **category** (optional) - Name of category defined in **categories** element
- **file** (optional, default: strtolower($name$).template.tpl) - Template's filename with extension
- **properties** (optional) - An array of objects, where each object has those properties:
    - **name** (required) - Name of the property
    - **description** (optional) - Description of the property
    - **icon** (optional) - Icon in the Resource Tree for resources using that Template (MODX 2.3+). Choose them from: http://fortawesome.github.io/Font-Awesome/icons/
    - **type** (optional, default: textfield) - Type of property
    - **options** (optional) - Property options
    - **value** (optional) - Property default value
    - **lexicon** (optional, default:strtolower($lowCaseName$):properties) - Lexicon topic for description
    - **area** (optional) - Area of the property

#### Example
```json
{
    "package":{
        "elements": {
            "templates": [{
                "name": "PackageTemplate",
                "file": "packagetemplate.template.tpl",
                "icon": "icon-beer",
                "properties": [{
                    "name": "testproperty",
                    "value": "testvalue"
                }]
            }]
        }
    }
}
```

### Plugins

**Wrapper:** plugins (array)
```json
{
    "package":{
        "elements": {
            "plugins": []
        }
    }
}
```

#### Available properties:
- **name** (required) - Plugin's name
- **category** (optional) - Name of category defined in **categories** element
- **file** (optional, default: strtolower($name$).plugin.php) - Plugin's filename with extension
- **events** (required) - Array of events
- **properties** (optional) - An array of objects, where each object has those properties:
    - **name** (required) - Name of the property
    - **description** (optional) - Description of the property
    - **type** (optional, default: textfield) - Type of property
    - **options** (optional) - Property options
    - **value** (optional) - Property default value
    - **lexicon** (optional, default:strtolower($lowCaseName$):properties) - Lexicon topic for description
    - **area** (optional) - Area of the property

#### Example
```json
{
    "package":{
        "elements": {
            "plugins": [{
                "name": "PackagePlugin",
                "file": "packageplugin.plugin.php",
                "events": ["OnPageNotFound"],
                "properties": [{
                    "name": "testproperty",
                    "value": "testvalue"
                }]
            }]
        }
    }
}
```

### Template Variables

**Wrapper:** tvs (array)
```json
{
    "package":{
        "elements": {
            "tvs": []
        }
    }
}
```

#### Available properties:
- **caption** (required) - TV's caption
- **name** (optional, default: strtolower($caption$)) - TV's name
- **category** (optional) - Name of category defined in **categories** element
- **type** (optional, default: text) - TV's type
- **description** (optional, default: null) - TV's description
- **defaultValue** (optional, default: null) - Default value of the TV
- **inputOptionValues** (optional, default: null) - Input options, for example items for single select list
- **sortOrder** (optional, default: 0) - If you're using more than one TV, this will determine which appear at the top (1 = top, bigger numbers sink to the bottom)
- **templates** (optional) - Array of template names for which will be this TV allowed
- **display** (optional) - Output type
- **properties** (optional) - An array of objects, where each object has those properties:
    - **name** (required) - Name of the property
    - **description** (optional) - Description of the property
    - **type** (optional, default: textfield) - Type of property
    - **options** (optional) - Property options
    - **value** (optional) - Property default value
    - **lexicon** (optional, default:strtolower($lowCaseName$):properties) - Lexicon topic for description
    - **area** (optional) - Area of the property
- **inputProperties** (optional) - Object with input properties, list of available properties depends on TV type
- **outputProperties** (optional) - Object with output properties, list of available properties depends on `display` (e.g. `delimiter`; `format`; `tagname`, `tagid`, `class`, `style`, `attrib`; ...)

#### Example
```json
{
    "package":{
        "elements": {
            "tvs": [{
                "caption": "MyTV",
                "name": "mytv",
                "type": "list",
                "description": "This is the best TV",
                "defaultValue": "second",
                "inputOptionValues": "First==first||Second==second",
                "templates": ["PackageTemplate"],
                "properties": [{
                    "name": "testproperty",
                    "value": "testvalue"
                }],
                "inputProperties": {
                    "allowBlank": false
                },
                "outputProperties": {
                    "delimiter": ","
                }               
            }]
        }
    }
}
```

### Widgets

**Wrapper:** widgets (array)
```json
{
    "package":{
        "elements": {
            "widgets": []
        }
    }
}
```

lexicon, size

#### Available properties:
- **name** (required) - Widget title
- **description** (optional, default: null) - Widget description
- **type** (optional, default: file) - Widget type. Could be set to: 
    - `snippet` widgets are MODX Snippets that are run and return their output. content has to be filled with a snippet name. 
    - `html` widgets are just straight HTML. content has to be filled with HTML code.
    - `file` widgets are loaded directly from PHP files, which can either return their output or the name of the modDashboardWidgetClass-extended class to load. content has to be filled with the name of a PHP file.
    - `php` widgets are widgets that are straight PHP in the widget content, similar to a Snippet. content has to be filled with the PHP code (with or without PHP tags).
- **content** (optional, default: null or strtolower($name$).widget.php for file type) - Widget content
- **lexicon** (optional, default: strtolower($name$):default) - Widget lexicon
- **size** (optional, default: half) - Widget size. Could be set to `half`, `full`, `double`

#### Example
```json
{
    "package":{
        "elements": {
            "widgets": [{
                "name": "MyWidget",
                "description": "This is the best widget",
                "type": "file",
                "content": "mywidget.widget.php",
                "lexicon": "mywidget:default",
                "size": "half",
            }]
        }
    }
}
```


### Categories
Category with name of the package is created automatically and all categories defined here will be nested under it.

**Wrapper:** categories (array)
```json
{
    "package":{
        "elements": {
            "categories": []
        }
    }
}
```

#### Available properties:
- **name** (required) - Category's name
- **parent** (optional) - Name of category that will be used as a parent

#### Example
```json
{
    "package":{
        "elements": {
            "categories": [{
                "name": "First category"
            },{
                "name": "Second category",
                "parent": "First category"
            }]
        }
    }
}
```

## Resources
Here you can specify Resources that should be created. Note that this is only one way process, so all changes to those Resources from MODX Manager will be lost during package update process.

**Wrapper:** resources (array)

```json
{
    "package":{
        "resources": []
    }
}
```
#### Available properties:
- **pagetitle** (required) - Page title
- **longtitle** (optional) - Long title
- **description** (optional) - Description
- **introtext** (optional) - Intro text
- **alias** (optional) - Alias
- **parent** (optional) - ID or Pagetitle of Resource that will be used as parent. Default: 0
- **content** (optional) - Content of the Resource
- **file** (optional) - path to the file that will be used as a content field
- **suffix** (optional) - Suffix that will append to the alias, which will create filename that will be check if exists and used as content. Default: .html
- **context_key** (optional) - Context that will be used for Resource. Default: web
- **template** (optional) - Template name or 0
- **class_key** (optional) - Class key of Resource. Default: modDocument
- **content_type** (optional) - Name of the content type that will be used for Resource
- **link_attributes** (optional) - Link attributes of the Resource
- **published** (optional) - 1/0 to set Resource published or not
- **isfolder** (optional) - 1/0 to set Resource as folder
- **richtext** (optional) - 1/0 to allow RichText editor
- **menuindex** (optional) - Resoruce's menu index
- **searchable** (optional) - 1/0 to set Resource searchable
- **cacheable** (optional) - 1/0 to set Resource cacheable
- **deleted** (optional) - 1/0 to set Resource as deleted. Default: 0
- **menutitle** (optional) - Menu title. Default:
- **hidemenu** (optional) - 1/0 to show Resource in menu
- **hide_children_in_tree** (optional) - 1/0 to show Resource's children in tree. Default: 0
- **show_in_tree** (optional) - 1/0 to show Resource in tree. Default: 1
- **tvs** (optional) - Array of name & value pair, where name is TV's name and value is desired value. Also can be used file option, to provide value from file.
- **others** (optional) - Array of name & value pair that will be sent to Resource's create & update processor
- **setAsHome** (optional) - 1/0 to link this Resource with Resource that is set as site_start
- **properties** (optional) - Path to the file that will be used for the properties field

#### Example
```json
{
    "package":{
        "resources": [{
            "pagetitle": "Test Resources",
            "alias": "test-resources",
            "content": "Test content",
            "tvs": [{
                "name": "test-tv",
                "value": "Value for test TV"
            }]
        }]
    }
}
```

## System settings
Here you can specify all system settings that you need for your component. Note that all settings are automaticaly prefixed with lowCaseName (from General part) and settings core_path, assets_path and assets_url are created automatically.

**Wrapper:** systemSettings (array)

```json
{
    "package":{
        "systemSettings": []
    }
}
```
#### Available properties:
- **key** (required) - Setting's key
- **namespace** (optional, default: lowCaseName) - Setting's namespace (will be used only as a prefix for the key)
- **type** (optional, default: textfield) - Setting's type, like textfield, textarea, combo-boolean, etc.
- **area** (optional, default: default) - Setting's area
- **value** (required) - Setting's value

#### Example
```json
{
    "package":{
        "systemSettings": [{
            "key": "test_key",
            "type": "textfield",
            "area": "default",
            "value": "it works"
        }]
    }
}
```
