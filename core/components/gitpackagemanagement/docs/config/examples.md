## Full config example
```json
{
    "name": "Package"
    ,"lowCaseName": "package"
    ,"description": "Package description"
    ,"author": "Author name"
    ,"version": "1.0.0"
    ,"package":{
        "actions": [{
            "id": 1
            ,"controller": "index"
            ,"hasLayout": 1
            ,"langTopics": "package:default"
            ,"assets": ""
        }]
        ,"menus": [{
            "text": "package"
            ,"description": "package.menu_desc"
            ,"action": 1
            ,"parent": "components"
            ,"icon": ""
            ,"menuIndex": 0
            ,"params": ""
            ,"handler": ""
        }]
        ,"elements": {
            "plugins": [{
                "name": "PackagePlugin"
                ,"file": "packageplugin.plugin.php"
                ,"events": ["OnPageNotFound"]
            }]
            ,"snippets": [{
                "name": "PackageSnippet"
                ,"file": "packagesnippet.snippet.php"
                ,"properties": [{
                    "name": "testproperty"
                    ,"desc": "package.packagesnippet.testproperty"
                    ,"type": "textfield"
                    ,"options": ""
                    ,"value": "test value"
                    ,"lexicon": "package:properties"    
                    ,"area": ""
                }]
            }]
            ,"chunks": [{
                "name": "PackageChunk"
                ,"file": "packagechunk.chunk.tpl"
            }]
            ,"templates": [{
                "name": "PackageTemplate"
                ,"file": "packagetemplate.template.tpl"
            }]
            ,"tvs": [{
                "caption": "MyTV"
                ,"name": "mytv"
                ,"type": "text"
                ,"description": "This is the best TV"
                ,"templates": ["PackageTemplate"]
            }]
        }
        ,"resources": [{
            "pagetitle": "Test Resources"
            ,"alias": "test-resources"
            ,"content": "Test content"
            ,"parent": "Some Resource"
            ,"tvs": [{
                "name": "test-tv",
                "value": "Value for test TV"
            }]
            ,"others": [{
                "name": "tagger-1",
                "value": "Tag #1, Tag #2"
            }]
        }]
        ,"systemSettings": [{
            "key": "test_key"
            ,"type": "textfield"
            ,"area": "default"
            ,"value": "it works"
        }]
    }
    ,"database": {
        "tables": ["PackageItem"]
        ,"prefix": "modx_"
        ,"simpleObjects": ["SimplePackageItem"]
    }
    ,"extensionPackage": {
        "serviceName": "package"
        ,"serviceClass": "Package"
    }
    ,"build": {
        "readme": "docs/readme.txt"
        ,"resolver": {
            "resolversDir": "resolvers"
            "after": ["resolver.customresolver.php"]
        }
    }
}
```

## Minimal config example
This config shows only required params for each section. If you don't need any section, remove it.
```json
{
    "name": "Package"
    ,"lowCaseName": "package"
    ,"description": "Package description"
    ,"author": "Author name"
    ,"version": "1.0.0"
    ,"package":{
        "actions": [{
            "id": 1
            ,"controller": "index"
        }]
        ,"menus": [{
            "text": "package"
            ,"action": 1
        }]
        ,"elements": {
            "plugins": [{
                "name": "PackagePlugin"
                ,"events": ["OnPageNotFound"]
            }]
            ,"snippets": [{
                "name": "PackageSnippet"
                ,"properties": [{
                    "name": "testproperty"
                    ,"value": "test value"
                }]
            }]
            ,"chunks": [{
                "name": "PackageChunk"
            }]
            ,"templates": [{
                "name": "PackageTemplate"
            }]
            ,"tvs": [{
                 "caption": "MyTV"
                 ,"templates": ["PackageTemplate"]
             }]
        }
        ,"resources": [{
            "pagetitle": "Test Resources"
            ,"alias": "test-resources"
            ,"content": "Test content"
            ,"tvs": [{
                "name": "test-tv",
                "value": "Value for test TV"
            }]
        }]
        ,"systemSettings": [{
            "key": "test_key"
            ,"value": "it works"
        }]
    }
    ,"database": {
        "tables": ["PackageItem"]
    }
    ,"extensionPackage": {}
    ,"build": {
        "resolver": {
            "after": ["resolver.customresolver.php"]
        }
    }
}
```
