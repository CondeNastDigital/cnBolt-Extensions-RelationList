var RelationlistST = function(properties) {

    var that = this;
    let rdm = Math.floor(Math.random() * 9999);

    let fieldId = 'relationlist-st-' + String(new Date().valueOf());
    let connector = '#connector-'+fieldId;

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
            '    <div class="block-title"></div>'+
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
         * Sets the data form the ImageService into the Block store
         */
        save: function(){
            var data = $(this.$('.connector')).val();
            if(data) {
                this.setData(JSON.parse(data));
            }
        },

        /**
         * Creates the new image service block
         */
        onBlockRender: function() {

            $('.frontend-target.relationlist').on('dragenter dragover drop', function(e){
                e.stopPropagation();
            });

            $(this.$('.block-title')).html(this.custom.label);
            $(this.$('.relationlistApp')).attr('id', 'relationlist-'+fieldId);
            $(this.$('.connector')).attr('id', 'connector-'+fieldId);

            let definitions = SirTrevor.getInstance(this.instanceID).options.options.Items.globals || '{}';
            let apiurl = this.extensionUrl + "relationlist/finditems/" + this.custom.contenttype + "/" + SirTrevor.getInstance(this.instanceID).el.name + "/" + this.custom.subFieldName + "/";

            this.realtionListInstance = new CnRelationList({

                options: {
                    apiurl: apiurl,
                    element: '#relationlist-' + fieldId,
                    timeout: 800
                },
                value: $(connector).val() || '{}',
                definitions: JSON.stringify(definitions),
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


