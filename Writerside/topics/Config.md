# Config

If you're starting new extra, consider using the [Scaffolding Command](Scaffolding-Extra.md). 

If you'd like to start on your own, create an empty `_build` directory and `gpm.json` or `gpm.yaml` file in it.

## JSON Schema
JSON schema is available here:
```
https://raw.githubusercontent.com/theboxer/Git-Package-Management/3.x/gpm.schema.json
```

Use it with your IDE for autosuggestions & validation of the gpm config.

## Partial configs
GPM supports all config files as YAML or JSON and you can also combine them. Most of the config parts can be separated to own file.

### Example
`_buid/gpm.yaml`
```yaml
name: myextra
lowCaseName: myextra
author: John Peca
namespace: myextra
version: 1.0.0-pl

systemSettings: partial/settings.json
```

`_buid/partial/settings.json`
```json
[
  {
    "key": "setting1",
    "area": "random-settings",
    "value": "hello there"
  },
  {
    "key": "setting2",
    "area": "system",
    "type": "combo-boolean",
    "value": 0
  }
]
```