In the **_build** folder create **config.json** file.  
All sections except the general are optional.  
If a section is not needed/used, remove it from the config.json.  

#### Available properties:
* **name** (required) - Name of the component
* **lowCaseName** (required) - Name of the component in lower case
* **description** (optional, default: '') - Some piece of information about the component
* **author** (required) - Probably your name / nickname or whatever you want
* **version** (required) - Version of your component

#### Example
```json
{
    "name": "Package",
    "lowCaseName": "package",
    "description": "Package description",
    "author": "Author name",
    "version": "1.0.0-pl"
}
```