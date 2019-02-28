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
                                    variant="danger"
                                    @click="removeItem(item)"
                            >
                                <font-awesome-icon icon="trash-alt"/>
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

        props: {},

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
                this.$store.dispatch('removeItem', item);
            },
        },

        data() {
            return {}
        }

    }

</script>