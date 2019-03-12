<template>
    <b-row class="itemlist">
        <div class="col-xs-12">
            <draggable v-model="items" @start="drag=true" @end="drag=false">
                <b-card class="item" v-for="item in items" :key="item.id">
                    <b-row >
                        <div class="col-xs-10">
                            <item
                                    v-bind="item"
                            ></item>
                        </div>
                        <div class="col-xs-2 text-right">
                            <b-button
                                    title="Unlinks this item from the relationlist"
                                    variant="warning"
                                    @click="removeItem(item)"
                            >
                                <font-awesome-icon icon="unlink" />
                            </b-button>
                        </div>
                    </b-row>
                </b-card>
            </draggable>
        </div>
    </b-row>
</template>

<script>
    import item from './Item.vue'
    import draggable from 'vuedraggable'

    export default {

        components: {
            item,
            draggable
        },

        computed: {

            items: {
                get() {
                    return this.$store.getters.getItems;
                },
                set(list) {
                    this.$store.dispatch('updateItemList', list);
                }
            }

        },

        methods: {
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
                        this.$store.dispatch('setError', error);
                        return;
                    }
                }

                this.$store.dispatch('removeItem', item);
            },
        },

        data() {
            return {}
        }

    }

</script>