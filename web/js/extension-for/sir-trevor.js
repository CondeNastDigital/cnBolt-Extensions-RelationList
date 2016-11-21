

var RelationlistST = function(properties) {

    var that = this;

    that.blockPrototype = {

        extensionUrl: properties.extensionUrl,
        extensionWebPath: properties.extensionWebPath,
        extensionOptions: properties.extensionOptions,

        fieldId: null,

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
        editorHTML: '<div class="frontend-target relationlist">'+
        '    <div class="block-title"></div>'+
        '    <input class="form-control search" id="" type="text">'+
        '    <div class="searchResultWrapper">'+
        '        <div class="searchResultList" id=""></div>'+
        '    </div>'+
        '    <div class="selectedElementsList" id=""></div>'+
        '    <input class="data-target" id="" type="hidden" value="">'+
        '</div>',

        /**
         * Loads the json data in to the field
         * @param data
         */
        loadData: function(data){
            $(this.$('.data-target')).val(JSON.stringify(data));
        },

        /**
         * Sets the data form the ImageService into the Block store
         */
        save: function(){
            var data = $(this.$('.data-target')).val();
            if(data) {
                this.setData(JSON.parse(data));
            }
        },

        /**
         * Creates the new image service block
         */
        onBlockRender: function() {

            this.fieldId = 'relationlist-st-' + String(new Date().valueOf());

            $(this.$('.block-title')).html(this.custom.label);
            $(this.$('input.search')).attr('id', 'search-' + this.fieldId);
            $(this.$('input.data-target')).attr('id', this.fieldId);
            $(this.$('.searchResultList')).attr('id', 'searchResult-'+this.fieldId);
            $(this.$('.selectedElementsList')).attr('id', 'selectedElements-'+this.fieldId);

            this.realtionListInstance = new RelationListComponent({
                contenttype: this.custom.contenttype,
                storageFieldName: this.fieldId,
                subFieldName: this.custom.subFieldName,
                fieldName: SirTrevor.getInstance(this.instanceID).el.name,
                boltUrl: this.extensionUrl,
                baseUrl: this.extensionWebPath,
                validation: {
                    min: this.custom.min | this.extensionOptions.min | 0,
                    max: this.custom.max | this.extensionOptions.max| 0
                }
            });
        }

    };


    that.init = function(options) {

        if(typeof(SirTrevor)) {
            Object.keys(options).forEach(function (block) {

                if (options[block].type != 'relationlist')
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

var relationListST = RelationlistST({
    extensionUrl: document.currentScript.getAttribute('data-extension-url'),
    extensionWebPath: document.currentScript.getAttribute('data-root-url'),
    extensionOptions: document.currentScript.getAttribute('data-extension-relationlist-config')
});

$(document).on('SirTrevor.DynamicBlock.All', function(){
    $(document).trigger('SirTrevor.DynamicBlock.Add', [relationListST] );
});
$(document).trigger('SirTrevor.DynamicBlock.Add', [relationListST] );



