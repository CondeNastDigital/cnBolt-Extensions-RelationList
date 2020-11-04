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
            <list />
        </div>
    </div>
</template>

<script>
    import toolbar      from './Toolbar.vue';
    import list         from './ItemList.vue';
    import errorHandler from './ErrorHandler.vue';

    export default {

        props: {
            config: Object
        },

        computed: {
            loading: function () {
                return !this.$store.getters.isReady;
            },

        },

        components: {
            toolbar,
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

<style scoped>
    .loading{
        position: absolute;
        z-index: 1000;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background-color: rgba(50,50,50,.3);
    }
    .loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        font-size: 36px;
        color: #555;
    }
</style>
<style>
/* globals */
.relationlist {
  position: relative;
  min-height: 350px;
}
.relationlist.scontent{
  min-height: auto;
  -webkit-border-radius: 3px;
  -moz-border-radius: 3px;
  padding: 5px 0;
  border-radius: 3px;
  border: 1px solid #ccc;
}

/* card */
.card {
  position: relative;
  display: -ms-flexbox;
  display: flex;
  -ms-flex-direction: column;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: border-box;
  border: 1px solid rgba(0,0,0,.125);
  border-radius: .25rem;
}

.card.item:nth-child(even) {
  background-color: #eeeeee;
}

.card-body {
  -ms-flex: 1 1 auto;
  flex: 1 1 auto;
  padding: 1.25rem;
}

.card-title {
  margin-bottom: .75rem;
}

</style>
