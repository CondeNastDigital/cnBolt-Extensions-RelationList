<template>
    <div class="settings">
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
</template>

<script>

    import Fields from './Fields.vue'

    export default {

        props: {
            settingsid: String,
            definitions: Object,
            fields: Object,
        },

        components: {
            Fields
        },

        computed: {
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

<style scoped>
    .settings .card{
        background-color: #f5f5f5;
    }
    .settings .form-group {
        margin: 0;
    }
</style>
