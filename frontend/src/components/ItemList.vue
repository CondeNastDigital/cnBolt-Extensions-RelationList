<template>
    <b-row class="itemlist">
        <div class="col-xs-12">
            <div class="inner">
                <draggable
                        v-model="items"
                        v-bind="dragOptions"
                        @start="drag=true"
                        @end="drag=false"
                >
                    <transition-group type="transition" :name="!drag ? 'flip-list' : null">
                    <b-card
                            class="item"
                            v-for="item in items"
                            v-on:drop.stop.prevent
                            :key="item.id"
                    >
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
                                        :state="getAttributes(item.id)"
                                        @input="setAttributes(item, $event)"
                                ></Fields>

                            </b-collapse>
                        </b-row>
                    </b-card>
                    </transition-group>
                </draggable>
            </div>
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

            dragOptions: function() {
                return {
                  animation: 200
                };
            }

        },

        methods: {

            getAttributes: function(id){
                return this.$store.getters.getAttributes(id) || {};
            },

            setAttributes: function(item, attributes) {
                this.$store.dispatch('setAttributes', { item, attributes });
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
            return {
              drag: false
            }
        }

    }

</script>

<style scoped>
    button {
        width: 40px;
    }
    .card.item {
        min-height: 100px;
    }
    .itemlist button.btn svg{
        margin-top: 2px;
    }

    .itemlist .item .item-fields {
        padding: 30px;
    }
    .itemlist .item-buttons button{
         margin: 2px;
    }
    .itemlist .inner {
        max-height: 300px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .item-field{
        margin-bottom: 5px;
    }

    .col-4 {
        width: calc(33% - 30px);
        display: inline-block;
    }

    .col-8 {
        width: calc(67% - 30px);
        display: inline-block;
    }

</style>
