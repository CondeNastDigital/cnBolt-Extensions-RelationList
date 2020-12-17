# cnBolt-Extensions-RelationList

Provides a backend field where you can select one or more content objects from different contenttypes. The field 
is similar to bolt's select field but can select more than one contenttype at the same time and provides a better 
preview. This extension also allows to connect to multiple content sources at the same time.

## Installation

Add the RelationList to the required extensions in extension/composer.json and call composer update. You may also need
the `cnd/library` extension for improved selection of bolt content and/or the `cnd/kraken-sdk` if you want to use the 
kraken content connector.

```
"cnd/relationlist": "*"
```

## Configuration
You need to configure three settings in two differen files.

### Contenttypes
To use a relationlist field, add the required configuration to a field for your content type (within `contenttype.yml`).
The main difference to older versions of relationlist is the `pool` property. This property specifies, from which
content sources this field can select content. 
```
myfield:
    type: relationlist
    label: Title
    pool: mypool
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

The Sir Trevor Configuration consists of adding an extended block, of type relationlist. 
Example:
```
structuredcontent:
    type: structuredcontentfield
    blocks: [Heading, Text, Items]
    extend:
        Items:
            type: relationlist
            label: Something
            
            # Options 1 - Only one pool for everything
            pool: mypool
            # Option 2 - One pool for manually searched items and one pool for auto fill
            pool: 
                search: mysearchpool
                fill: myfillpool

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

### Pool
The Relationlist extension needs a pool for the content it can link to. You need to specify one pool
that is present within the extensions configuration file`relationlist.cnd.yml`.

```
pools:

    mypool:
        order: !date
        position: order
        sources:

            content:
                connector: content
                query:
                    contenttypes: [teasers]

            kraken:
                connector: kraken
                query:
                    filter:
                        source.name: {"$in": %site%}
                defaults:
                    site: ['mysite']
```

the pools key contains a list of pools that can be used in your contenttypes.yml. Within a pool, you 
can specify a list of sources. Each source contains the reference to the connector class to use, a query
and a set or default parameters if no parameter was given during execution (see template).

- `order` order all resulting items by this teaser attribute. Available attributes are title, description, date. Prefix
   the key with `!` to reverse order. Default's to `!date`
- `position` all fixed items will be injected at a numeric position stored in an attribute with this name. Otherwise,
   all will be prepended to the beginning of the list. 
- `sources` a list of connectors and their configuration (see below)

A source has a key that serves as it's service id and the following properties:

- `connector` a reference to the connector configurationm top use for this source
- `query` a storage query as required by the connector class to use. Most use a couple of filter and order attributes. 
   The query can also contain `%placeholders%` that refer to values inside the 
- `defaults` This is a key/value list of values to replace the placeholders above (the keys needs to be the placeholder 
   without the `%` characters). Dynamic values will be specified while templating, these values are used when nothing 
   was specified.. See twig section below.

### Connector
Each pool uses one or more sources, which need a connector configuration. A connector configuration needs a key
that is also used insode the pool configurations. The connector config has these properties:

- `connector`

#### Content
This is the old Bolt internal relation. It allows relations to any record stored in this bolt instance.
This is the source used in all older versions of the extension.

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

**Note** The Kraken Connector caches it's content for 60 seconds by default!

## Usage

### RelationList
This service returns the selected content items from your pool according to the settings in your record's 
field.

Within your twig template, you may get your related items by using the custom twig function, which returns 
the items as a record directly. Here is an example, how to use the function within a twig template:

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

### RelationFill
This service can select other content according to the searches specified in it's pool.
Sample: You want to show a list of 10 teasers. An editor selected 3 teasers manually via the relationlist
field in the backend. This service fills the results with additional content up to the requested 10 items.

```
{% set value = record.myfield %}
{% set related_globals = RelationList.getGlobals(value) %}
{% set related_items = RelationList.getItems(value) %}

{# You can use global attributes in your field to let an editor modify the query #}
{% set parameters = RelationList.getGlobals(record.myfield) %}
{% set filled_items = RelationFill.getItems('mypool', 10, parameters, related_items, 'mybucket') %}
```

Function parameters for `RelationFill.getItems`:
 - poolname
 - total number of items
 - parameter array to inject into query (Optional)
 - array or manually selected items to inject into the results (Optional)
 - name of a bucket that contains all your already shown items that will be excluded. (Optional - defaults to 'default')   

Also note, that you can specify a `position` attribute in your pool's configuration. If your manually selected
items have a numeric attribute with this name, the contents will be used as a position to inject this item into the
list. If not, all manually selected items will be added to the beginning of the results.

## Legacy - Do not use
**This version does not support older configurations**. It can auto convert old data in your database, but everything 
else needs to be changed to fit the new mechanics.




order-direction