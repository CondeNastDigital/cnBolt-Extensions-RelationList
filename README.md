# cnBolt-Extensions-RelationList

Provides a backend field for a content type which defines a list of relations to content objects


## Installation

1.) Edit your extensions/composer.json file and add the **cnd-relationlist** repository:
```
    "repositories": {
        "packagist": false,
        "bolt": {
            "type": "composer",
            "url": "https://extensions.bolt.cm/satis/"
        },
        "cnd-shortcodes": {
            "type": "git",
            "url": "https://github.com/CondeNastDigital/cnBolt-Extensions-RelationList.git"
        }
    },
```
2.) Change to the extensions folder and install via composer.
```
composer require cnd/relationlist
```
Installing or updating via the Bolt admin interface is also possible but would require the web-server's user to have proper access to the GitHup repository. This is usually not the case.

## Configuration
Add the following field for your content type (within `contenttype.yml`). Those fields are mandatory.
```
myNewField:
    type: relationlist
    group: content
    options:
        allowed-types: [pages]
        min: 1
        max: 3
```

## Usage
Within your twig template, you may access the content type field which comes in form of an JSON string.
There is a custom twig filter, which converts the JSON string into an array. Here is an example, how to fetch the content elements within a twig template:
```
{% set elements = myNewField|json_decode %}

{% for el in elements %}
    {% setcontent currentElement = el %}
    {{ dump(currentElement) }}
{% endfor %}
```



