# cnBolt-Extensions-RelationList

Provides a backend field where you can select one or more other content objects from different contenttypes. The field is similar to bolt's select field but can select more than one contenttype at the same time and provides a better preview.

Note: The field does not provide any mechanism for fetching the selected objects. It only stores a JSON string with content ids. You have to fetch them yourself in your template. See sample below.

## Installation

Add the RelationList to the required extensions in extension/composer.json and call composer update:

```
"cnd/relationlist": "*"
```

## Configuration
Add the following field for your content type (within `contenttype.yml`).
```
myfield:
    type: relationlist
    label: Title
    options:
        allowed-types: [pages, otherpages, evenotherpages]
        min: 1
        max: 3
    globals: 
        title:
            label: Title
            type: text
        description:
            label: Description
            type: textarea
        checkit:
            label: CheckIt
            type: checkbox
        fieldselect:
            label: Field select
            type: select
            options:
                - value: key1
                  text: Key 1
                - value: key2
                  text: Key 2
```

The Sir Trevor Configuration consits of adding an extended block, of type relationlist. 
Example:
```
structuredcontent:
    type: structuredcontentfield
    blocks: [Heading, Text, Items]
    extend:
        Items:
            type: relationlist
            label: Something
            options:
                allowed-types: [pages, otherpages, evenotherpages]
                min: 1
                max: 3
            globals: 
                title:
                    label: Title
                    type: text
                description:
                    label: Description
                    type: textarea
                checkit:
                    label: CheckIt
                    type: checkbox
```

## Usage
Within your twig template, you may get your related items by using the custom twig function, which returns the items as a record directly. Here is an example, how to use the function within a twig template:
```
{% set value = record.myfield %}

{% set related_globals = getRelatedGlobals(value) %}
{{ dump(related_globals) }}

{% set related_items = getRelatedItems(value) %}
{% for item in related_items %}
    {{ dump(item) }}
{% endfor %}
```
