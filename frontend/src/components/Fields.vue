<template>
    <div>
        <b-form-group
                v-for="(field, key, index) in definitions"
                :key="key"
                :id="settingsid+'-group-'+key"
                :label="field.label"
                :label-for="settingsid+'key'"
        >
            <!-- todo dynamic compoents! -->
            <b-form-input v-focus="index===0" v-if="isInput(field)" v-model="values[key]" @change="updateStore(key, $event)" :id="settingsid+'key'" :type="field.type"/>
            <b-form-textarea v-focus="index===0" v-if="isTextarea(field)" v-model="values[key]" @change="updateStore(key, $event)" :id="settingsid+'key'" rows="2" max-rows="3"/>
            <b-form-select  v-focus="index===0" v-if="isSelect(field)" v-model="values[key]" @change="updateStore(key, $event)" :id="settingsid+'key'" :type="field.type" :options="field.options"></b-form-select>
            <b-form-checkbox v-focus="index===0" v-if="isCheckbox(field)" v-model="values[key]" @change="updateStore(key, $event)" :id="settingsid+'key'" :type="field.type"></b-form-checkbox>

        </b-form-group>
    </div>
</template>

<script>
    export default {

        props: {
            settingsid: String,
            definitions: Object,
            state: Object
        },

        computed: {

            values: {
                get: function () {

                    let ret = {};

                    for(let index in this.definitions) {

                        let el = this.definitions[index];
                        let def = el.default || null;

                        ret[index] = !this.state || !this.state.hasOwnProperty(index) ?  def : this.state[index] ;
                    }

                    return ret;
                }
            }
        },

        methods: {

            /**
             *
             * @param key
             * @param data
             */
            updateStore: function (key, data) {

                let model = this.values;

                model[key] = data;

                this.$emit('input', model);
            },

            /**
             *
             * @param entry
             * @returns {boolean}
             */
            isInput: function (entry) {
                return entry.type === 'text';
            },

            /**
             *
             * @param entry
             * @returns {boolean}
             */
            isTextarea: function (entry) {
                return entry.type === 'textarea';
            },

            /**
             *
             * @param entry
             * @returns {boolean}
             */
            isSelect: function (entry) {
                return entry.type === 'select';
            },

            /**
             *
             * @param entry
             * @returns {boolean}
             */
            isCheckbox: function (entry) {
                return entry.type === 'checkbox';
            },
        },

        data() {
            return {}
        }
    }
</script>