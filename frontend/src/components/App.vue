<template>
    <div class="cnrelationlist">
        <div class="loading" v-if="loading">
            <font-awesome-icon class="loading-spinner" icon="spinner" spin/>
        </div>

        <div>

            <b-alert
                    variant="warning"
                    :show="confirm_show"
                    @dismissed="confirm_show=false"
            >
                <b-row>
                    <div class="col-xs-12 col-md-8">
                        {{ confirm_message }}
                    </div>

                    <div class="col-md-4 col-xs-12 text-right" >
                        <b-button @click="confirmOk()" variant="warning" >
                            Ok
                        </b-button>

                        <b-button @click="confirmCancel()" variant="secondary" v-focus="true">
                            Cancel
                        </b-button>
                    </div>
                </b-row>
            </b-alert>

            <b-alert
                    variant="danger"
                    dismissible
                    v-model="dismissCountDown"
                    @dismissed="dismissCountDown=0"
            >{{ error_message }}</b-alert>

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
            getErrors: function(error){
                this.error_status = error.status;
                this.error_message = error.message;

                if(this.error_status)
                    this.dismissCountDown = this.dismissSecs;
            },

            setConfirmation: function(confirmation){

                this.confirm_message = confirmation.message || 'Please confirm this operation';
                this.confirm_ok = confirmation.ok || null;
                this.confirm_cancel = confirmation.cancel || null;
                this.confirm_show = true;

            },

            confirmOk: function() {
                this.confirm_show=false;

                if(typeof(this.confirm_ok) === 'function')
                    this.confirm_ok();

            },

            confirmCancel: function() {
                this.confirm_show=false;

                if(typeof(this.confirm_cancel) === 'function')
                    this.confirm_cancel();
            }

        },

        directives: {
            focus: {
                // directive definition
                inserted: function (el, binding) {
                    if(binding.value)
                        el.focus()
                }
            }
        },

        computed: {
            loading: function () {
                return !this.$store.getters.isReady;
            },
            definitions: function(){
                return Object.keys(this.$store.getters.getDefinitions.globals).length > 0;
            }

        },

        created: function(){
            this.$root.$on('cnrl-relation-error', this.getErrors);
            this.$root.$on('cnrl-relation-confirm', this.setConfirmation);
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
                settingsId: 'settings' + Math.floor(Math.random()*9999),
                confirm_message: '',
                confirm_ok: null,
                confirm_cancel: null,
                confirm_show: false
            }
        }

    }
</script>