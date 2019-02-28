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

//import 'bootstrap/dist/css/bootstrap.min.css'
//import 'bootstrap-vue/dist/bootstrap-vue.min.css'
import './assets/css/relationlist.css'

import {library} from '@fortawesome/fontawesome-svg-core'
import {faMinus, faTrashAlt, faSpinner, faCogs} from '@fortawesome/free-solid-svg-icons'
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

import App from './components/App.vue'
import Store from './store'

library.add(
    faMinus, faTrashAlt, faSpinner, faCogs
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
    initApp(config){
        this.app = new Vue({

            el: config.options.element || '#app',
            store: this.store,
            components: {
                App
            },

            // creates the tag app
            render (createComponent) {
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
    initStore(config){
        let _self = this;
        this.store = new Vuex.Store(Store());

        let data = JSON.parse(config.value);
        let definitions = JSON.parse(config.definitions);
        let globals = data.globals || {};
        let items = data.items || [];

        // build the definitions
        this.store.dispatch('setDefinitions', definitions);
        this.store.dispatch('setOptions', config.options);
        this.store.dispatch('setGlobals', globals);
        this.store.dispatch('setItems', items);

        this.store.subscribe(function(mutation, state){
            _self.relationUpdated(mutation, state);
        });
    };

    relationUpdated(mutation, state){
        let result = {};
        result.globals = this.store.getters.getGlobals;
        result.items = this.store.getters.getItems;

        if (this.config.hasOwnProperty('onRelationUpdated') && typeof(this.config.onRelationUpdated) === 'function') {
            this.config.onRelationUpdated(result);
        }

    };
}
