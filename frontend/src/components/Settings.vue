<template>
    <b-row class="settings">
        <div class="col-xs-12">
            <b-collapse :id="settingsid">
                <b-card>
                    <b-form-group
                            v-for="(field, key) in definitions"
                            :key="key"
                            :id="settingsid+'-group-'+key"
                            :label="field.label"
                            :label-for="settingsid+'key'"
                    >
                        <!-- todo dynamic compoents! -->
                        <b-form-input v-if="isInput(field)" v-model="fields[key]" @input="updateGlobals(key, $event)" :id="settingsid+'key'" :type="field.type"/>
                        <b-form-textarea v-if="isTextarea(field)" v-model="fields[key]" @input="updateGlobals(key, $event)" :id="settingsid+'key'" rows="2" max-rows="3"/>
                        <b-form-select v-if="isSelect(field)" v-model="fields[key]" @change="updateGlobals(key, $event)" :id="settingsid+'key'" :type="field.type" :options="field.options"></b-form-select>
                        <b-form-checkbox v-if="isCheckbox(field)" v-model="fields[key]" @change="updateGlobals(key, $event)" :id="settingsid+'key'" :type="field.type"></b-form-checkbox>

                    </b-form-group>
                </b-card>
            </b-collapse>
        </div>
    </b-row>
</template>

<script>
    export default {

        props: {
            settingsid: String
        },

        computed: {

            fields: {
                get: function () {
                    return this.$store.getters.getGlobals;
                }
            },

            definitions: function () {
                return this.$store.getters.getDefinitions;
            }
        },

        methods: {

            /**
             *
             * @param key
             * @param data
             */
            updateGlobals: function (key, data) {

                let globals = this.$store.getters.getGlobals;

                globals[key] = data;

                this.$store.dispatch('setGlobals', globals);
                this.$root.$emit('cnrl-relation-updated');
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