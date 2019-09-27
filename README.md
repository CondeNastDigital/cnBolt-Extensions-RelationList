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
    sources:
        content: [pages, otherpages, evenotherpages]
        kraken: [article]
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
    attributes:
        inutfield:
            label: Title
            type: text
        textfield:
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
            sources:
                content: [pages, otherpages, evenotherpages]
                kraken: [article]
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
            attributes:
                inutfield:
                    label: Title
                    type: text
                textfield:
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

### Sources
The Relationlist extension needs one or more sources for the content it can link to. You need to specify one source
configuration for each source later used in field configurations.

#### Content
This is the old Bolt internal relation. It allows relations to any record stored in this bolt instance.
This is the source used in all older versions of the extension.

**Note** The Kraken Connector caches it's content for 60 seconds by default!

``` 
connectors:

    content:
        class: Bolt\Extension\CND\RelationList\Connector\ContentConnector
```

#### Kraken
Allows relations to content from a Condé-Nast Kraken service. This service needs a couple more 
configuration options:
```
connectors:

    kraken:
        class: Bolt\Extension\CND\RelationList\Connector\KrakenConnector
        auth:
            key-private: bolt-private.key
            key-public: bolt-public.key
        api:
            search-filter:
                source.name: {'$in': ['gq','glamour']}
            client-uid: 1234567890abcdefghijklm
            url: https://kraken.condenastdigital.de/
            verify-cert: false
```
**Keys**

The public and private keys used for the kraken api can be sharen with the SSO extension.
They need to be specified in RSA format and can be created with this script.
```
openssl genrsa -out app/config/extensions/bolt-private.key 2048
openssl rsa -in app/config/extensions/bolt-private.key -out app/config/extensions/bolt-private.key
openssl rsa -pubout -in app/config/extensions/bolt-private.key -out app/config/extensions/bolt-public.key
```
**Kraken Client**

You also need to add a new client in Kraken's administration ui. You will need to add your public 
key there. Kraken will then create a UID for this client which you can use for the configuration of
this extension

**Search Filters**

You can optionally add additional filters for the Kraken api that will be added to any search
for content. In this sample, search is restricted to content of GQ and GLAMOUR.

## Usage
Within your twig template, you may get your related items by using the custom twig function, which returns the items as a record directly. Here is an example, how to use the function within a twig template:

```
{% set value = record.myfield %}

{% set related_globals = RelationList.getGlobals(value) %}
{{ dump(related_globals) }}

{% set related_items = RelationList.getItems(value) %}
{% for item in related_items %}
    {{ dump(item->id) }}
    {{ dump(item->type) }}
    {{ dump(item->attributes) }}
    {{ dump(item->object) }}
{% endfor %}
```

## Legacy - Do not use
The old configurations for fields and the old twig functions (see below) are still supported, but should net be
used in any new projects!
```
{% set value = record.myfield %}

{% set related_globals = getRelatedGlobals(value) %}
{{ dump(related_globals) }}

{% set related_items = getRelatedItems(value) %}
{% for item in related_items %}
    {{ dump(item) }}
{% endfor %}
```
