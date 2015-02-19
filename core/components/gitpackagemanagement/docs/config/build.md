Define build options here.

**Wrapper:** build
```json
{
    "build":{}
}
```

#### Available properties:
* **resolver** (optional) - Resolver options
* **readMe** (optional, default: docs/readme.txt) - Path to readme file
* **license** (optional, default: docs/license.txt) - Path to license file
* **changeLog** (optional, default: docs/changelog.txt) - Path to change log file
* **setupOptions** (optional) - Setup options object

#### Example
```json
{
    "build":{
        "readme": "docs/readme.txt"        
    }
}
```

## Resolver part
Define resolver options here

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

#### Example
```json
{
    "resolver":{
        "resolverDir": "resolver",
        "after": ["resolve.customresolver.php"],        
    }
}
```

## Setup options part
Define setup options here

**Wrapper:** resolver
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
        "source": "setup.options.php",
    }
}
```