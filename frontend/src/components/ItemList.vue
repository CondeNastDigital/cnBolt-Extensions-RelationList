<template>
    <b-row class="itemlist">
        <div class="col-xs-12">
            <draggable v-model="items" @start="drag=true" @end="drag=false">
                <b-card class="item" v-for="item in items" :key="item.id">
                    <b-row >
                        <div class="col-xs-10 item-preview">
                            <item
                                    v-bind="item"
                            ></item>
                        </div>
                        <div class="col-xs-2 text-right item-buttons">

                            <b-button class="btn-settings" v-b-toggle="'attributes-'+item.id" variant="secondary" v-if="hasDefinitions">
                                <font-awesome-icon icon="cogs"/>
                            </b-button>

                            <b-button
                                    title="Unlinks this item from the relationlist"
                                    variant="warning"
                                    @click="removeItem(item)"
                            >
                                <font-awesome-icon icon="unlink" />
                            </b-button>

                        </div>
                        <b-collapse :id="'attributes-'+item.id" class="col-12 col-xs-12 item-fields" v-if="hasDefinitions">

                            <Fields
                                    :settingsid="'input'+Date.now()"
                                    :definitions="definitions"
                                    :state="attributes[item.id]"
                                    @input="setAttributes(item.id, $event)"
                            ></Fields>

                        </b-collapse>
                    </b-row>
                </b-card>
            </draggable>
        </div>
    </b-row>
</template>

<script>
    import item from './Item.vue'
    import draggable from 'vuedraggable'
    import Fields from './Fields.vue'

    export default {

        components: {
            item,
            draggable,
            Fields
        },

        computed: {

            items: {
                get() {
                    return this.$store.getters.getItems;
                },
                set(list) {
                    this.$store.dispatch('updateItemList', list);
                }
            },

            hasDefinitions : function() {
                return Object.keys(this.$store.getters.getDefinitions.attributes || {}).length > 0;
            },

            definitions: function() {
                return this.$store.getters.getDefinitions.attributes || {};
            },

            attributes: {
                get() {
                    return this.$store.getters.getAttributes;
                },
                set(list) {
                    this.$store.dispatch('setAttributes', list);
                }
            }

        },

        methods: {

            setAttributes: function(key, attributes) {
                let state = Object.assign({}, this.attributes);
                state[key] = attributes;
                this.attributes = state;
            },

            /**
             * removes an item from the stored list
             * @param item
             */
            removeItem(item) {

                // validation
                let options = this.$store.getters.getOptions;
                if (options.hasOwnProperty('validation') && options.validation.hasOwnProperty('min')){
                    if(this.$store.getters.getItems.length <= (options.validation.min)){
                        let error = {
                            status: true,
                            message: 'Error: At least ' + options.validation.min + ' element(s) have to be selected!'
                        };
                        this.$root.$emit('cnrl-relation-error', error);
                        return;
                    }
                }

                const context = this;
                this.$root.$emit('cnrl-relation-confirm', {
                    message: 'Do you really want to remove "' + item.title + '" ?',
                    ok: function(){
                        context.$store.dispatch('removeItem', item);
                    }
                });

            },
        },

        data() {
            return {}
        }

    }

</script>