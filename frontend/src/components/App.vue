<template>
    <div class="cnrelationlist">
        <div class="loading" v-if="loading">
            <font-awesome-icon class="loading-spinner" icon="spinner" spin/>
        </div>
        <div>
            <toolbar :settingsid="settingsId" />
            <settings v-if="definitions" :settingsid="settingsId" />
            <list />
        </div>
    </div>
</template>

<script>
    import toolbar from './Toolbar.vue';
    import settings from './Settings.vue';
    import list from './ItemList.vue';

    export default {

        props: {
            config: Object
        },

        methods: {
            relationUpdated: function () {
                let result = {};
                result.globals = this.$store.getters.getGlobals;
                result.items = this.$store.getters.getItems;

                if (this.config.hasOwnProperty('onRelationUpdated') && typeof(this.config.onRelationUpdated) === 'function') {
                    this.config.onRelationUpdated(result);
                }
            }
        },

        created: function(){
            this.$root.$on('cnrl-relation-updated', this.relationUpdated);
        },

        computed: {
            loading: function () {
                return !this.$store.getters.isReady;
            },
            definitions: function(){
                return Object.keys(this.$store.getters.getDefinitions).length > 0;
            }
        },

        components: {
            toolbar,
            settings,
            list
        },

        data(){
            return {
                settingsId: 'settings' + Math.floor(Math.random()*9999)
            }
        }

    }
</script>