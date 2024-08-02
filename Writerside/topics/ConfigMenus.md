<show-structure for="none" depth="0"></show-structure>

# Menus

<tldr>
<p><b>Wrapper:</b> menus (array)</p>
<p><b>Properties:</b> <a href="#text">text</a>, <a href="#description">description</a>, <a href="#action">action</a>, <a href="#parent">parent</a>, <a href="#icon">icon</a>, <a href="#menuindex">menuIndex</a>, <a href="#params">params</a>, <a href="#handler">handler</a>, <a href="#permission">permission</a></p>
</tldr>

## Properties
### text
`required`

Text that will show in the menu
### description
`optional`

Description that will show in the menu
### action
`optional`

### parent
`optional` `default: components`

### icon
`optional`

### menuIndex
`optional`

### params
`optional`

### handler
`optional`

### permission
`optional`


## Example
```yaml
menus:
  - text: GPM
    description: Manage packages
    action: manage
```