<template>
    <div class="cnrelationlist">
        <div class="loading" v-if="loading">
            <font-awesome-icon class="loading-spinner" icon="spinner" spin/>
        </div>

        <div>
            <error-handler confirm-event="cnrl-relation-confirm" error-event="cnrl-relation-error" />
        </div>

        <div class="container-fluid">
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
    import errorHandler from './ErrorHandler.vue';

    export default {

        props: {
            config: Object
        },

        computed: {
            loading: function () {
                return !this.$store.getters.isReady;
            },
            definitions: function(){
                return Object.keys(this.$store.getters.getDefinitions.globals).length > 0;
            }

        },

        components: {
            toolbar,
            settings,
            list,
            errorHandler
        },

        data(){
            return {
                settingsId: 'settings' + Math.floor(Math.random()*9999),
            }
        }

    }
</script>
