# cnBolt-Extensions-RelationList

Provides a backend field where you can select one or more other content objects from different contenttypes. The field is similar to bolt's select field but can select more than one contenttype at the same time and provides a better preview.

Note: The field does not provide any mechanism for fetching the selected objects. It only stores a JSON string with content ids. You have to fetch them yourself in your template. See sample below.

## Configuration
Add the following field for your content type (within `contenttype.yml`). Those fields are mandatory.
```
myfield:
    type: relationlist
    options:
        allowed-types: [pages, otherpages, evenotherpages]
        min: 1
        max: 3
```

## Usage
Within your twig template, you may access the content type field which comes in form of an JSON string.
There is a custom twig filter, which converts the JSON string into an array. Here is an example, how to fetch the content elements within a twig template:
```
{% set elements = record.myfield|json_decode %}

{% for el in elements %}
    {% setcontent currentElement = el %}
    {{ dump(currentElement) }}
{% endfor %}
```



