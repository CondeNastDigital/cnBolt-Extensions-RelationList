<template>
    <div>
        <b-alert
                variant="danger"
                dismissible
                :show="true"
                v-model="error.dismissCountDown"
                @dismissed="dismissError(error)"
                v-for="error in allErrors" :key="error.error_message"
        >{{ error.error_message }}</b-alert>

        <b-alert
                variant="warning"
                :show="true"
                @dismissed="confirmCancel(confirmation)"
                v-for="confirmation in allConfirmations" :key="confirmation.message"
        >

            <b-row>
                <div class="col-xs-12 col-md-8">
                    {{ confirmation.confirm_message }}
                </div>

                <div class="col-md-4 col-xs-12 text-right" >

                    <b-button @click="confirmOk(confirmation)" variant="warning" >
                        Ok
                    </b-button>

                    <b-button @click="confirmCancel(confirmation)" variant="secondary" v-focus="true">
                        Cancel
                    </b-button>

                </div>
            </b-row>
        </b-alert>

    </div>

</template>

<script>

    export default {
        name: "ErrorHandler",

        props: {
            confirmEvent: {
                type: String,
                required: false,
                default: 'cn-error-confirm'
            },
            errorEvent: {
                type: String,
                required: false,
                default: 'cn-error-error'
            }
        },

        data: function() {
            return {
                errors: [],
                confirmations: [],
                dismissSecs: 5
            }
        },

        created: function(){
            this.$root.$on(this.errorEvent, this.addError);
            this.$root.$on(this.confirmEvent, this.addConfirmation);
        },

        computed: {
            allErrors: function() {
                return this.errors;
            },

            allConfirmations: function() {
                return this.confirmations;
            }
        },

        methods: {
            addError: function(error){

                let err = {};

                err.error_status = error.status;
                err.error_message = error.message;

                if(err.error_status)
                    err.dismissCountDown = this.dismissSecs;

                this.errors.push(err);
            },

            dismissError: function(instance) {
                let key = this.errors.indexOf(instance);
                if(key >= 0)
                    this.errors.splice(key,1);
            },

            addConfirmation: function(confirmation){

                let confirm = {};

                confirm.confirm_message = confirmation.message || 'Please confirm this operation';
                confirm.confirm_ok = confirmation.ok || null;
                confirm.confirm_cancel = confirmation.cancel || null;

                this.confirmations.push(confirm);

            },

            confirmOk: function(instance) {

                if(typeof(instance.confirm_ok) === 'function')
                    instance.confirm_ok();

                let key = this.confirmations.indexOf(instance);
                if(key >= 0)
                    this.confirmations.splice(key,1);

            },

            confirmCancel: function(instance) {

                if(typeof(instance.confirm_cancel) === 'function')
                    instance.confirm_cancel();

                let key = this.confirmations.indexOf(instance);
                if(key >= 0)
                    this.confirmations.splice(key,1);

            }

        },

    }
</script>