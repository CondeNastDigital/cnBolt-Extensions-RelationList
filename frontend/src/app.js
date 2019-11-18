import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'
import VueAxios from 'vue-axios'

import BsFormGroup from 'bootstrap-vue/es/components/form-group'
import BsFormSelect from 'bootstrap-vue/es/components/form-select'
import BsFormInput from 'bootstrap-vue/es/components/form-input'
import BsFormCheckbox from 'bootstrap-vue/es/components/form-checkbox'
import BsTextarea from 'bootstrap-vue/es/components/form-textarea'
import BsCard from 'bootstrap-vue/es/components/card'
import BsCollapse from 'bootstrap-vue/es/components/collapse'
import BsButton from 'bootstrap-vue/es/components/button'
import BsLayout from 'bootstrap-vue/es/components/layout'
import BsAlert from 'bootstrap-vue/es/components/alert'

//import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-vue/dist/bootstrap-vue.min.css'
import './assets/css/relationlist.css'

import {library} from '@fortawesome/fontawesome-svg-core'
import {faMinus, faUnlink, faSpinner, faCogs} from '@fortawesome/free-solid-svg-icons'
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

import App from './components/App.vue'
import Store from './store'

import Focus from './directives/focus.js';

library.add(
    faMinus, faUnlink, faSpinner, faCogs
);


export class cnRelationList {
    constructor(config) {

        Vue.use(Vuex);
        Vue.use(VueAxios, axios);

        Vue.use(BsFormGroup);
        Vue.use(BsFormSelect);
        Vue.use(BsFormInput);
        Vue.use(BsFormCheckbox);
        Vue.use(BsTextarea);
        Vue.use(BsCard);
        Vue.use(BsCollapse);
        Vue.use(BsButton);
        Vue.use(BsLayout);
        Vue.use(BsAlert);

        Vue.directive('focus', Focus);
        Vue.component('font-awesome-icon', FontAwesomeIcon);

        this.name = 'RelationList';
        this.config = config;

        this.initStore(this.config);
        this.initApp(this.config);
    };


    /**
     * initialization of the vue app
     * @returns {*}
     */
    initApp(config) {
        this.app = new Vue({

            el: config.options.element || '#app',
            store: this.store,
            components: {
                App
            },

            // creates the tag app
            render(createComponent) {
                return createComponent('app', {
                    props: {
                        config: this.config || []
                    }
                });
            }

        });
    };

    /**
     * initialization of the vue globals storage (vuex)
     * set values to the store
     */
    initStore(config) {

        let _self = this;
        this.store = new Vuex.Store(Store());

        let data = JSON.parse(config.value);
        let definitions = JSON.parse(config.definitions);
        let options = config.options || [];

        let globals = data.globals || {};
        let attributes = data.attributes || {};
        let items = data.items || [];

        if (Object.keys(globals).length <= 0)
            globals = this.getDefaultFields(definitions.globals);

        // todo add the defaults for the attributes as well
        //if (Object.keys(attributes).length <= 0)
        //    attributes = this.getDefaultFields(definitions.attributes);

        // build the store
        this.store.dispatch('setDefinitions', definitions);
        this.store.dispatch('setOptions', options);
        this.store.dispatch('setGlobals', globals);

        this.getFullElements(items, attributes);

        this.store.subscribe(function (mutation, state) {
            _self.relationUpdated(mutation, state);
        });
    };

    /**
     * function which will be triggered if the relation had been updated
     * @param mutation
     * @param state
     */
    relationUpdated(mutation, state) {
        let result = {};

        result.status = this.store.getters.getStatus;
        result.globals = this.store.getters.getGlobals;
        result.items = this.store.getters.getSimpleItems;

        if (this.config.hasOwnProperty('onRelationUpdated') && typeof(this.config.onRelationUpdated) === 'function') {
            this.config.onRelationUpdated(result);
        }

    };

    /**
     * converts the default value of a field into a proper value
     * @param fields
     */
    getDefaultFields(fields) {
        let results = {};

        for (let i in fields){
            if(fields.hasOwnProperty(i)){
                let field = fields[i];

                if (field.hasOwnProperty('default')){
                    results[i] = field.default;
                }
            }
        }
        return results;
    }

    /**
     * converts a contenttype slug to a full content element
     * @param items
     * @param attributes
     */
    //todo: make this ajax call without jquery
    getFullElements(items, attributes) {

        let elements = [];
        let _self = this;

        // convert the old fashioned array string to the new relation object
        for (let item in items){
            if (items.hasOwnProperty(item)){

                if (!items[item].hasOwnProperty('service')) {
                    items[item] = {
                        'id': items[item],
                        'service': 'content',
                        'type': (items[item]).split('/')[0],
                        'attributes': (attributes.length > 0) ? attributes[items[item]] : {}
                    }
                }
            }
        }

        $.ajax({
            url: this.store.getters.getOptions.fetchurl,
            method: 'POST',
            data: {
                "items": JSON.stringify(items)
            }
        }).done(function(data){

            if (data.hasOwnProperty('items') && data.items.length > 0)
                elements = data.items;

            _self.store.dispatch('setItems', elements);
            _self.store.dispatch('setStatus', true);
        });
        return elements;
    }

}
