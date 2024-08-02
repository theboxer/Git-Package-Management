<show-structure for="none" depth="0"></show-structure>

# General

<tldr>
<p><b>Wrapper:</b> none</p>
<p><b>Properties:</b> <a href="#name">name</a>, <a href="#lowcasename">lowCaseName</a>, <a href="#description">description</a>, <a href="#namespace">namespace</a>, <a href="#author">author</a>, <a href="#version">version</a></p>
</tldr>

## Properties

### name 
`required`

Name of the extra.

### lowCaseName

`optional` `default: name in lower case without spaces`

Name of the extra in lower case

### description
`optional`

Brief description of the extra
### namespace
`optional` `default: lowCaseName with first letter capitalized`

Used as a namespacePrefix when parsing schema
### author
`optional`

Identification of the extra's author.
### version
`required`

Version of the extra

## Example
```yaml
name: GPM
lowCaseName: gpm
namespace: Gpm
description: Dev tool for building MODX extras
author: John Peca <john@peca.io>
version: 3.0.0-pl
```