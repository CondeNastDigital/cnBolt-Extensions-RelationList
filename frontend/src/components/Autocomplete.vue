<template>
    <v-select :multiple="multiple" v-model="computed" :options="computedValues" :taggable="taggable" @search="onSearch" @input="reset">
        <!-- Template is fixed, as we don't have an usecase for dynamic previews -->
        <template slot="option" slot-scope="option">
            {{ option.label }}
            <span v-if="option.hasOwnProperty('info')" class="badge badge-info">{{option.info}}</span>
        </template>
    </v-select>
</template>

<script>

    import VueSelect from 'vue-select';

    export default {
        name: "Autocomplete",

        components: {
            "v-select": VueSelect
        },

        props: [
            "value",
            "class",
            "endpoints",
            "taggable",
            "multiple"
        ],

        data: function() {
            return {
                "model": [],
                "values": [],
                "holdBack": null,
                "founds": [],
            }
        },

        computed: {
            /**
             * Returns all the possible field values, for the field type - select
             * @returns {Array}
             */
            computedValues: function() {
                return this.values;
            },

            computed: {
                get: function() {

                    const that = this;
                    let   ret  = [];

                    if(typeof(this.value) === 'undefined' )
                        return null;

                    // converts the string values to an array - needed by the plugin
                    if(!(this.value instanceof Array) )
                        this.value = [this.value];

                    this.value.forEach(function(el){
                        // assume that the current value is not present in the options.
                        // only the option value is saved in the json. That is why we set the label to the value.
                        let found = {
                            value: el,
                            label: el
                        };

                        ret.push(found);
                    });

                    return ret;

                },
                set: function(value) {
                    this.model = value;
                }
            }

        },

        watch: {

            /**
             *  Watches the Value of the select. On change notifies the parent.
             *  The onchange event was very unstable that is why a watcher is used
             */
            model: function() {

                this.model = this.model || [];
                let data = this.model;

                // isMultiple = false
                if(this.model instanceof Object && this.model.hasOwnProperty('value')){
                    data = this.model.value;
                }

                // is Multiple = true
                if(data instanceof Array)
                    data = this.model.reduce(function(ret, current){
                        if(current !== undefined)
                            ret.push(current.value);
                        return ret;
                    }, []);


                this.$emit('change',data);
            },

            founds: function(){
                this.values = Object.assign([], this.founds, this.values);
            }
        },

        methods: {

            reset(){
                this.values = [];
            },

            // Search router based on the select type
            onSearch(search, loading) {
                loading(true);

                if(!search){
                    loading(false);
                    return;
                }

                this.founds = [];

                // ajax calls
                if(this.holdBack) {
                    clearTimeout(this.holdBack);
                }

                this.holdBack = setTimeout(
                    () => {
                        if(this.endpoints){
                            for (let endpoint of this.endpoints) {
                                this.search(loading, endpoint, search, this);
                            }
                        }

                    }
                ,500)

                // free values - tags
                if(this.taggable){
                    this.values = [{
                        value: search,
                        label: search
                    }];
                }

                loading(false);

            },

            /**
             * Ajax Caller for the list values
             * @param loading
             * @param endpoint
             * @param search
             * @param vm
             */
            search: function(loading, endpoint, search, vm) {

                fetch(
                    endpoint.replace('{{query}}', escape(search))
                ).then(res => {
                    res.json().then(function (json) {
                        json.items.map(item => {
                            vm.founds.push(item);
                        });
                    });
                    loading(false);
                });

            }

        }

    }
</script>

<style>
    div.v-select input.vs__search{
        border: none;
    }
</style>
