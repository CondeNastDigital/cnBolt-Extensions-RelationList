<template>
    <div class="cnrelationlist">
        <div class="loading" v-if="loading">
            <font-awesome-icon class="loading-spinner" icon="spinner" spin/>
        </div>
        <b-alert
                variant="danger"
                dismissible
                fade
                :show="error"
                v-model="dismissCountDown"
                @dismissed="dismissCountDown=0"
        >{{ error_message }}</b-alert>
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
            getErrors: function(error){
                this.error_status = error.status;
                this.error_message = error.message;

                if(this.error_status)
                    this.dismissCountDown = this.dismissSecs;
            }
        },

        computed: {
            loading: function () {
                return !this.$store.getters.isReady;
            },
            definitions: function(){
                return Object.keys(this.$store.getters.getDefinitions).length > 0;
            }
        },

        created: function(){
            this.$root.$on('cnrl-relation-error', this.getErrors);
        },

        components: {
            toolbar,
            settings,
            list
        },

        data(){
            return {
                dismissSecs: 5,
                dismissCountDown: 0,
                error_status: false,
                error_message: '',
                settingsId: 'settings' + Math.floor(Math.random()*9999)
            }
        }

    }
</script>