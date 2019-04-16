<template>
    <b-row class="toolbar">

        <div class="col-xs-11" v-if="definitions">
            <search></search>
        </div>
        <div class="col-xs-12" v-else>
            <search></search>
        </div>

        <div class="col-xs-1" v-if="definitions">
            <b-button class="btn-settings" v-b-toggle="settingsid" variant="secondary">
                <font-awesome-icon icon="cogs"/>
            </b-button>
        </div>
    </b-row>
</template>

<script>

    import search from './Search.vue';

    export default {
        props: {
            settingsid: String,
        },
        computed: {
            definitions: function(){
                console.log(this.$store.getters.getDefinitions);
                console.log(Object.keys(this.$store.getters.getDefinitions).length);
                return Object.keys(this.$store.getters.getDefinitions).length > 0;
            }
        },
        components: {
            search
        },
        data() {
            return {}
        }
    }
</script>