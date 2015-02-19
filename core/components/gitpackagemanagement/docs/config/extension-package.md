Define extension package here. If you don't need to specify **serviceName** and **serviceClass**, but you need to register extension package, leave the **extensionPackage** object empty.

**Wrapper:** extensionPackage
```json
{
    "extensionPackage":{}
}
```

#### Available properties:
* **serviceName** (required both, or optional both) - Service's name
* **serviceClass** (required both, or optional both) - Service's class

#### Example
```json
{
    "extensionPackage":{
        "serviceName": "package"
        ,"serviceClass": "Package"
    }
}
```