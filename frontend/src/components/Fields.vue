<template>
    <div>
        <b-row  v-for="(field, key, index) in definitions"
                :key="key"
                :id="uid = Math.floor(Math.random()*9999) +'-group-'+key"
                class="item-field"
        >

                <b-col cols="4" sm="4" lg="2">
                    <label :for="uid+'key'">{{field.label}}</label>
                </b-col>

                <b-col cols="8" sm="8" lg="10">
                    <!-- todo dynamic compoents! -->
                    <b-form-input          size="sm" v-focus="index===0" v-if="isInput(field)"          v-model="values[key]" @input="updateStore(key, $event)" :id="uid+'key'" :type="field.type"/>
                    <b-form-textarea       size="sm" v-focus="index===0" v-if="isTextarea(field)"       v-model="values[key]" @input="updateStore(key, $event)" :id="uid+'key'" rows="2" max-rows="3"/>
                    <b-form-select         size="sm" v-focus="index===0" v-if="isSelect(field)"         v-model="values[key]" @change="updateStore(key, $event)" :id="uid+'key'" :type="field.type" :options="field.options"></b-form-select>
                    <b-form-checkbox       size="sm" v-focus="index===0" v-if="isCheckbox(field)"       v-model="values[key]" @change="updateStore(key, $event)" :id="uid+'key'" :type="field.type"></b-form-checkbox>
                    <autocomplete          size="sm" v-focus="index===0" v-if="isAutocomplete(field)"   v-model="values[key]" @change="updateStore(key, $event)" :id="uid+'key'" :type="field.type" :value="values[key]" :endpoints="getEndpoints(field)" :multiple="isMultiple(field)" :taggable="isTaggable(field)"></autocomplete>
                    <b-form-checkbox-group size="sm" v-focus="index===0" v-if="isCheckboxGroup(field)"  v-model="values[key]" @change="updateStore(key, $event)" :id="uid+'key'" :type="field.type" :options="field.options" class="checkboxgroup"></b-form-checkbox-group>
                </b-col>

        </b-row>
    </div>
</template>

<script>
import vAutocomplete from './fields/Autocomplete.vue'

export default {

  components: {
    'autocomplete': vAutocomplete,
  },

  props: {
    settingsid: String,
    definitions: Object,
    state: Object
  },

  computed: {

    values: {
      get: function () {

        let ret = {}

        for (let index in this.definitions) {

          let el = this.definitions[index]
          let def = el.default || null

          ret[index] = !this.state || !this.state.hasOwnProperty(index) ? def : this.state[index]
        }
        return ret
      }
    }
  },

  methods: {

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    getEndpoints(entry) {
      return entry.endpoints || []
    },

    /**
     *
     * @param key
     * @param data
     */
    updateStore: function (key, data) {
      let model = this.values
      model[key] = data

      this.$emit('input', model)
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isAutocomplete(entry) {
      return entry.type === 'autocomplete'
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isCheckboxGroup(entry) {
      return entry.type === 'checkboxgroup'
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isTaggable(entry) {
      return entry.taggable || false
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isMultiple(entry) {
      return entry.multiple || false
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isInput: function (entry) {
      return entry.type === 'text'
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isTextarea: function (entry) {
      return entry.type === 'textarea'
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isSelect: function (entry) {
      return entry.type === 'select'
    },

    /**
     *
     * @param entry
     * @returns {boolean}
     */
    isCheckbox: function (entry) {
      return entry.type === 'checkbox'
    },
  },

  data() {
    return {}
  }
}
</script>

<style scoped>

    body .item-field * {
        font-size: 11px;
    }

    .item-field {
        margin-bottom: 5px;
    }

    .item-field input.form-control, .item-field select.custom-select  {
        max-height: 25px;
        border-width: 1px;
    }

    .item-field textarea.form-control {
        max-height: 200px;
    }

</style>

<style>
  .checkboxgroup label {
    display: inline-block;
    font-size: 11px !important;
  }

  .checkboxgroup .custom-checkbox {
    display: inline-block;
    padding-left: 0;
    padding-right: 1.3125rem;
  }
  .checkboxgroup .custom-checkbox:first-child {
    padding-left: 0;
  }
</style>
