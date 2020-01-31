<template>
    <b-row class="settings">
        <div class="col-xs-12">
            <b-collapse :id="settingsid">
                <b-card>
                    <Fields
                        :settingsid="settingsid"
                        :definitions="definitions"
                        :state="fields"
                        @input="updateGlobals($event)"
                    ></Fields>
                </b-card>
            </b-collapse>
        </div>
    </b-row>
</template>

<script>

    import Fields from './Fields.vue'

    export default {

        props: {
            settingsid: String
        },

        components: {
            Fields
        },

        computed: {

            fields: {
                get: function () {
                    return this.$store.getters.getGlobals;
                }
            },

            definitions: function () {
                return this.$store.getters.getDefinitions.globals;
            }
        },

        methods: {

            /**
             *
             * @param key
             * @param data
             */
            updateGlobals: function (data) {
                this.$store.dispatch('setGlobals', data);
                this.$root.$emit('cnrl-relation-updated');
            },

        },

        data() {
            return {}
        }
    }
</script>
