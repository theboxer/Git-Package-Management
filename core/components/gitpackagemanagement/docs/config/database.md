Define table's prefix and tables here

**Wrapper:** database
```json
{
    "database":{}
}
```

#### Available properties:
* **prefix** (optional, default: modx_) - Prefix for tables
* **tables** (required) - Array of object class names
* **simpleObjects** (optional) - Array of simple objects that should be loaded

#### Example
```json
{
    "database":{
        "tables": ["PackageItem"]
        ,"prefix": "modx_"
        ,"simpleObjects": ["SimplePackageItem"]
    }
}
```