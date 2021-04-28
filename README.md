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
        order: %oder%
        order-direction: false
        position: order
        sources:

            content:
                customfields:
                    customfield_1: teaser_title 
                connector: content
                query:
                    contenttypes: [teasers]
                    order: %customfields%order%    

            kraken:
                customfields:
                    customfield_1: teaser.title 
                connector: kraken
                query:
                    filter:
                        source.name: {"$in": %site%}
                    order: %customfields%order%    
                defaults:
                    site: ['mysite']
```

the pools key contains a list of pools that can be used in your contenttypes.yml. Within a pool, you 
can specify a list of sources. Each source contains the reference to the connector class to use, a query
and a set or default parameters if no parameter was given during execution (see template).

- `order` order all resulting items by this teaser attribute. Available attributes are title, description, date and 
   all the custom attributes defined in `sources.mysource.customfields`. 
   You can also use `%placeholders%`, where value of the placeholder must be a valid teaser attribute. 
  
- `order-direction` the order direction. The defualt is `false`. False stands for descending, while true is for ascending order. 

- `position` all fixed items will be injected at a numeric position stored in an attribute with this name. Otherwise,
   all will be prepended to the beginning of the list. 
- `sources` a list of connectors and their configuration (see below)
  
- `defaults` This is a key/value list of values to replace the placeholders above (the keys needs to be the placeholder
  without the `%` characters). Dynamic values will be specified while templating, these values are used when nothing
  was specified.. See twig section below.

A source has a key that serves as it's service id and the following properties:

- `connector` a reference to the connector configurationm top use for this source
  
- `customfields` a map  of type `[teaser.attribute => content.path.to.field.value]`. The key is the path of the teaser attribute to set.
   The right side is the path, which contains the value to use for the new teaser attribute. The Fields **can not** 
   contain `%placeholders%`.
  
- `query` a storage query as required by the connector class to use. Most use a couple of filter and order attributes. 
   The query can also contain `%placeholders%` that refer to values inside the 
- `defaults` This is a key/value list of values to replace the placeholders above (the keys needs to be the placeholder 
   without the `%` characters). Dynamic values will be specified while templating, these values are used when nothing 
   was specified.. See twig section below.

### Placeholders

The placeholders are used to dynamically change the Pool configuration, based on some user input.
A placeholder is a value, or a key in the Configuration that starts and ends with a `%`. e.g: %my_placeholder%.
When calling the `RelationFill.getItems` you can pass an array of key => values, where the key is the name of the placeholder
and the value its representation. 

A simple example:
```
pools:
    mypool:
        sources:
            kraken:
                connector: kraken
                query:
                    filter:
                        control.updateDate: { $lte: %date% } 
                        source.name: {"$in": %site%} 
                defaults:
                    site: ['mysite']
                    date: '$cnCurrentDate'
```

When you call RelationFill service, you need to provide an array of placeholder values:

```
[
   "site" => "glamour", 
   "date" =< "2028-12-01"
]
```

The resulting pool looks like this:
```
pools:
    mypool:
        sources:
            kraken:
                connector: kraken
                query:
                    filter:
                        control.updateDate: { $lte: "2028-12-01" } 
                        source.name: {"$in": "glamour"} 
                defaults:
                    site: ['mysite']
                    date: '$cnCurrentDate'
```

You can also use placeholder to create custom sorting: 

```
pools:
    mypool:
        order: %order%
        sources:
            kraken:
                connector: kraken
                customfields:
                     custom_field: control.updateDate
                query:
                    filter:
                        control.updateDate: { $lte: "2028-12-01" } 
                        source.name: {"$in": "glamour"}
                    order: 
                       %customfields%order%: false     
                defaults:
                    site: ['mysite']
                    date: '$cnCurrentDate'
```

You may notice that we have used a new placeholder `%customfield%order%`. This is a special way to say:
Take the value of placeholder %order%, check if a custom field with this name exists, and use its definition (source path) as the value of
%customfields%order%.

For example: if we pass an array with the following properties:
```
[
   "site"  => "glamour", 
   "date"  => "2028-12-01"
   "order" => "custom_field"
]
```

The pool above will be converted into:

```
pools:
    mypool:
        order: 'custom_field'
        sources:
            kraken:
                connector: kraken
                customfields:
                     custom_field: control.updateDate
                query:
                    filter:
                        control.updateDate: { $lte: "2028-12-01" } 
                        source.name: {"$in": "glamour"}
                    order: 
                       control.updateDate: false     
                defaults:
                    site: ['mysite']
                    date: '$cnCurrentDate'
```

 - `%order%` will be replaced by the direct value form the placeholders array - 'custom_field'
 - `%customfields%order%` will be replaced by the value of the customfield.custom_field - 'control.updateDate'

At the moment we only support `%customfield%xxx%`, and it will not work for any other pool sub-configuration.

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

#### Tipser
This connector selects products from the Tipser API via different methods.

**Configuration**
The connector needs some values to access the api correctly. These are:

```
connectors:
    tipser:
        class: Bolt\Extension\CND\RelationList\Connector\TipserProductConnector
        api:
            market: de                   # Your market 2-letter code
            env: production              # 'production' or 'stage' selects the tipser api to use
            key: ABCDEFGHIJKLMNOPQRST    # API Key for selected environment (prod and stage have different keys)
            posId: 1234567890            # POS Id. Needed for more 'products'
            user: someone@condenast.de   # A user account with access to tipser backend and the give POS Id. Needed for more 'products'
            password: abcdefghijk        # Passwort. Needed for more 'products'
``` 

**Similar Products**
This select mode retrieves products similar to a given product id.

```
    tipser-products:
        connector: tipser
        fill:
            mode: 'similar'
            productid: 5f1e9aa0db740c0001422f5a
```

**All products**
Retrieves a list of all products sorted by date.

```
    tipser-products:
        connector: tipser
        fill:
            mode: all
```

**Collection**
Retrieves a list of products within a specific collection 

```
    tipser-products:
        connector: tipser
        fill:
            mode: 'collection'
            collectionid: 607da544bf5a4ff6c0c682e4
```

**Products**
Retrieves a filteres list of products. This mode requires you to specify posid, user and password in your connector configuration.
The available filters can be derived from the calls used inside the shopping backend itself. The api is undocumented atm.

```
    tipser-products:
        connector: tipser
        fill:
            mode: 'products'
            filters:
                categoryIds: [6006bafdbc862c8865d437e4]
            onlyAvailable: true
            order:
                name: relevance
                direction: ASC
            query: ''
```

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