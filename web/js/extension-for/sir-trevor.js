var RelationlistST = function(properties) {

    var that = this;

    that.blockPrototype = {

        extensionUrl: properties.extensionUrl,
        extensionWebPath: properties.extensionWebPath,
        extensionOptions: properties.extensionOptions,
        extensionDefinitions: properties.extensionDefinitions,

        type: '',
        title: function() {
            return this.custom.label
        },
        icon_name: 'list',
        toolbarEnabled: true,
        // Custom html that is shown when a block is being edited.
        textable: false,
        realtionListInstance: null,
        custom: {
            type: '',
            label: '',
            contenttype: ''
        },
        editorHTML:
          '<div class="frontend-target relationlist scontent">'+
          '    <div class="relationlistApp" id=""></div>'+
          '    <textarea style="display:none" class="connector" id=""></textarea>'+
          '</div>',

        /**
         * Loads the json data in to the field
         * @param data
         */
        loadData: function(data){
            $(this.$('.connector')).val(JSON.stringify(data));
        },

        /**
         * Sets the data form the Relationlist into the Block store
         */
        save: function(){
            var data = $(this.$('.connector')).val();
            if(data) {
                this.blockStorage.data = JSON.parse(data);
            }
        },

        /**
         * Creates the new relationlist block
         */
        onBlockRender: function() {

            let fieldId = 'relationlist-st-' + String(new Date().valueOf());
            let connector = '#connector-'+fieldId;

            $('.frontend-target.relationlist').on('dragenter dragover drop', function(e){
                e.stopPropagation();
            });

            $(this.$('.block-title')).html(this.custom.label);
            $(this.$('.relationlistApp')).attr('id', 'relationlist-'+fieldId);
            $(this.$('.connector')).attr('id', 'connector-'+fieldId);

            let values = JSON.parse($(connector).val() || '{}');
            let field = this.custom || {};
            let pool = JSON.stringify(field.pool || {})
            let definitions = JSON.stringify({
                globals: field.globals || {},
                attributes: field.attributes || {}
            });
            let searchurl = this.extensionUrl + "relationlist/search/" + field.contenttype + "/" + SirTrevor.getInstance(this.instanceID).el.name + "/" + field.subFieldName + "/";
            let fetchurl = this.extensionUrl + "relationlist/fetch";
            let options = {
                searchurl: searchurl,
                fetchurl: fetchurl,
                config: this.extensionOptions || '{}',
                pools: pool,
                element: '#relationlist-'+fieldId,
                validation: {}
            };

            if (field.hasOwnProperty('min')){
                options.validation.min = field.min;
            }

            if (field.hasOwnProperty('max')){
                options.validation.max = field.max;
            }

            values = that.migrate(values);

            this.realtionListInstance = new CnRelationList({

                options: options,
                value: JSON.stringify(values),
                definitions: definitions,
                onRelationUpdated: function (data) {
                    $(connector).val(JSON.stringify(data));
                }
            });
        }

    };


    that.init = function(options) {

        if(typeof(SirTrevor)) {
            Object.keys(options).forEach(function (block) {

                if (!(options[block] instanceof Object && options[block].hasOwnProperty('type') && options[block].type == 'relationlist'))
                    return;

                var newBlock = {
                    type: block,
                    custom: options[block]
                };

                newBlock.custom.subFieldName = block;
                newBlock.custom.contenttype = $('[name="contenttype"]').val();

                if (typeof(SirTrevor.Blocks[block]) === 'undefined') {
                    newBlock = jQuery.extend({}, that.blockPrototype, newBlock);
                    SirTrevor.Blocks[block] = SirTrevor.Block.extend(newBlock);
                }
            });
        }

    };

    /**
     * migrates the old json format to the new one
     * @param values
     * @returns {*}
     */
    that.migrate = function(values){
        let items = values;
        if (typeof values === 'object' && !values.hasOwnProperty('items')){
            let elements = [];
            for (let id in values){
                if (values.hasOwnProperty(id))
                    elements.push(values[id]);
            }
            items = {'items': elements};
        }
        return items;
    };

    return that;
};

var relationListST = new RelationlistST({
    extensionUrl:     document.currentScript.getAttribute('data-extension-url'),
    extensionWebPath: document.currentScript.getAttribute('data-root-url'),
    extensionOptions: document.currentScript.getAttribute('data-extension-relationlist-config'),
    extensionDefinitions: document.currentScript.getAttribute('data-extension-relationlist-definitions')
});

$(document).on('SirTrevor.DynamicBlock.All', function(){
    $(document).trigger('SirTrevor.DynamicBlock.Add', [relationListST] );
});
$(document).trigger('SirTrevor.DynamicBlock.Add', [relationListST] );
