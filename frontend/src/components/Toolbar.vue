<template>
    <b-row class="toolbar">

        <!-- TODO: consider to make an own component for meta -->
        <div class="col-xs-12 meta"
             v-if="hasValues"
        >
            <div class="inner">
                <span v-for="(field, key, index) in definitions"
                      v-if="hasValue(key)"
                      :key="key"
                >
                    <label>{{field.label}}</label>: {{ Array.isArray(values[key]) ? values[key].join(', ') : values[key] }}
                </span>
            </div>
        </div>
        <!-- end meta -->

        <div class="col-xs-11" v-if="hasDefinitions">
            <search></search>
        </div>
        <div class="col-xs-12" v-else>
            <search></search>
        </div>

        <div class="col-xs-1" v-if="hasDefinitions">
            <b-button class="btn-settings" v-b-toggle="settingsid" variant="secondary">
                <font-awesome-icon icon="cogs"/>
            </b-button>
        </div>

        <div class="col-xs-12" v-if="hasDefinitions">
            <settings
                    v-if="definitions"
                    :settingsid="settingsid"
                    :definitions="definitions"
                    :fields="fields"
            />
        </div>
    </b-row>
</template>

<script>

    import search from './Search.vue';
    import settings     from './Settings.vue';

    export default {
        props: {
            settingsid: String,
        },
        computed: {

           /**
           *
           * @return {*}
           */
            definitions: function(){
                return this.$store.getters.getDefinitions.globals;
            },

          /**
           *
           * @return {boolean}
           */
            hasDefinitions: function(){
                return Object.keys(this.$store.getters.getDefinitions.globals).length > 0;
            },

          /**
           *
           * @return {boolean}
           */
            hasValues: function(){
              let res = false;

              for (let index in this.values) {
                if (this.values.hasOwnProperty(index)) {

                  if (this.values[index] === null || typeof this.values[index] === 'undefined')
                    continue;

                  if (
                    //this.values[index] !== null ||
                    this.values[index] === !0 || //checkbox
                    //this.values[index].length > 0  //string
                    Object.keys(this.values[index]).length > 0 //array
                  )
                    res = true;
                }
              }
              return res;
            },

          /**
           *
           */
            values: {
                get: function () {

                    let ret = {};

                    for(let index in this.definitions) {

                        let el = this.definitions[index];
                        let def = el.default || null;

                        ret[index] = !this.fields || !this.fields.hasOwnProperty(index) ?  def : this.fields[index];
                    }
                    return ret;
                }
            },

          /**
           *
           */
          fields: {
              get: function () {
                return this.$store.getters.getGlobals;
              }
            },
        },

        methods: {
            /**
             *
             * @return {boolean}
             */
            hasValue: function(index) {
                let res = false;

                if (this.values[index] === null || typeof this.values[index] === 'undefined')
                  return res;

                if(this.values[index] === !0 || Object.keys(this.values[index]).length > 0)
                    res = true;

                return res;
            },
        },

        components: {
            search,
            settings,
        },

        data() {
            return {
            }
        }
    }
</script>

<style scoped>
    .toolbar, .meta {
        margin-bottom: .8rem;
    }
    .toolbar .btn-settings{
        float: right;
    }
    .meta {
        font-size: 11px;
        line-height: 14px;
    }
    .meta .inner {
        padding-left: 15px;
        padding-right: 15px;
        border-bottom: 1px solid #ccc;
    }
    .meta label {
        font-weight: 700;
        font-size: 11px;
        display: inline-block;
    }

    .meta span{
        color: #888;
        padding-right: 10px;
        margin-right: 8px;
        position: relative;
    }
    .meta span:after{
        display: inline-block;
        content: '//';
        position: absolute;
        right: 0;
    }
    .meta span:last-child:after{
        display: none;
    }
</style>
