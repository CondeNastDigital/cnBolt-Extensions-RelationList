<template>
  <div class="settings">
    <b-collapse :id="settingsid">
      <b-card>
        <Fields
            :settingsid="settingsid"
            :definitions="getDefinitions()"
            :state="getFields()"
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

  computed: {},

  methods: {

    /**
     *
     *
     */
    addAdditionalField(field) {
      this.additionalFields.push(field)

      this.$store.dispatch('setDefinitionGlobals', {globals: Object.assign({}, this.$store.getters.getDefinitionGlobals, field)})
    },

    getFields() {
      return this.setAllToInitialField(this.fields, 'pool_sources')
    },

    setAllToInitialField(fields, field){

      if (!fields.hasOwnProperty(field)){

        let value = []

        switch (field){
          case 'pool_sources':
            value = this.$store.getters.getDefaultSourcesFor('fill');
            break

          default:
            break
        }

        fields = Object.assign({}, fields, {
          [field]: value
        })
      }

      this.$store.dispatch('setGlobals', fields)

      return fields
    },

    getDefinitions() {
      const additionalFields = this.additionalFields
      let definitions = this.definitions

      for (let field in additionalFields) {
        definitions = Object.assign({}, definitions, additionalFields[field])
      }

      return definitions
    },

    /**
     *
     *
     *
     */
    getPoolOptions() {
      let options = []
      let fillPool = this.$store.getters.getPoolSourcesFor('fill')

      if (fillPool) {
        for (let source in fillPool) {
          if (fillPool.hasOwnProperty(source)) {
            options.push({
              text: fillPool[source].label || source,
              value: source,
            })
          }
        }
      }

      return options
    },

    /**
     *
     * @param key
     * @param data
     */
    updateGlobals: function (data) {
      this.$store.dispatch('setGlobals', data)
      this.$root.$emit('cnrl-relation-updated')
    },

  },

  created() {

    if (this.getPoolOptions().length > 0) {
      this.addAdditionalField({
        pool_sources: {
          label: 'Quellen',
          type: 'checkboxgroup',
          options: this.getPoolOptions()
        }
      })
    }

  },

  data() {
    return {
      additionalFields: [],
    }
  }
}
</script>

<style scoped>
.settings .card {
  background-color: #f5f5f5;
}

.settings .form-group {
  margin: 0;
}
</style>
