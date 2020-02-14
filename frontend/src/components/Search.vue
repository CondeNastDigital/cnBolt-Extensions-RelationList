<template>
    <div class="search">
        <div class="searchbar">
            <b-input
                    type="text"
                    placeholder="Search..."
                    v-model="search"
                    @input.native="findItems"
            ></b-input>
        </div>
        <div class="results" v-if="foundItems.length > 0">
            <b-card class="item" v-for="item in foundItems" :key="item.id">
                <b-row>
                    <div class="col-xs-12">
                        <item
                                v-bind="item"
                                @click.native="addItem(item)"
                        ></item>
                    </div>
                </b-row>
            </b-card>
        </div>
    </div>
</template>

<script>

    import item from './Item.vue'

    export default {
        name: 'Search',

        components: {
            item
        },

        methods: {

            /**
             * adds an item to the stored item list
             * emits an event that the item has been added
             */
            addItem: function (item) {

                let options = this.$store.getters.getOptions;
                let storeditems = this.$store.getters.getItems;

                // validation
                if (options.hasOwnProperty('validation') && options.validation.hasOwnProperty('max')){
                    if(storeditems.length >= (options.validation.max)){
                        let error = {
                            status: true,
                            message: 'Error: You tried to add too many items. Only ' + options.validation.max + ' are allowed!'
                        };
                        this.$root.$emit('cnrl-relation-error', error);

                        this.reset();
                        return;
                    }
                }

                // Check, if element was already selected
                let found = false;
                for (let idx in storeditems){
                    if (storeditems.hasOwnProperty(idx) && storeditems[idx].id === item.id)
                        found = true;
                }
                if (found) {
                    this.reset();
                    return;
                }

                // add item
                this.$store.dispatch('addItem', item);
                this.$root.$emit('cnrl-relation-updated');
                this.reset();
            },

            /**
             * finds an item in the database
             * has a timeout to minimize the database connections
             */
            //todo refactor this function to prevent v-model and input.native
            findItems: function () {

                const search = this.search.trim();

                if(search.length > 0) {

                    if (this.timer) {
                        clearTimeout(this.timer);
                        this.timer = null;
                    }
                    this.timer = setTimeout(() => {
                        let endpoint = this.$store.getters.getOptions['searchurl'] || false;
                        this.foundItems = this._ajaxCall(endpoint, search);
                        this.$emit('cnrl-items-found');

                    }, 800);
                }
                else{
                    clearTimeout(this.timer);
                    this.reset();
                }

            },

            /**
             * makes the ajaxcall
             * @param endpoint
             * @param val
             * @returns {Array}
             * @private
             */
            _ajaxCall: function (endpoint, val) {

                if (endpoint && typeof(val) !== 'undefined') {
                    this.$store.dispatch('setReady', false);

                    let uri = endpoint + val;
                    let globals = this.$store.getters.getGlobals;

                    this.$http
                        .get(uri, { params: {
                                params: globals
                            }})
                        .then(response => {
                            if (response.data.status === true) {
                                this.foundItems = response.data.items;
                                this.$store.dispatch('setReady', true);
                            }
                        })
                        .catch(error => console.error(error))
                        .finally(() => {
                            this.$store.dispatch('setReady', true);
                        });

                }

                return this.foundItems;

            },

            /**
             * resets the searchbar with a blank string
             * resets the founditems array to clear the found items list
             */
            reset: function () {
                this.search = '';
                this.foundItems = [];
            },
        },

        computed: {},

        data() {
            return {
                search: '',
                foundItems: []
            }
        },


    }

</script>
