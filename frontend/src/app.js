import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'
import VueAxios from 'vue-axios'

import {

    LayoutPlugin,
    CardPlugin,
    FormGroupPlugin,
    FormSelectPlugin,
    FormInputPlugin,
    FormCheckboxPlugin,
    FormTextareaPlugin,
    CollapsePlugin,
    ButtonPlugin,
    AlertPlugin
} from 'bootstrap-vue'

//import 'bootstrap/dist/css/bootstrap.min.css'

import 'bootstrap-vue/dist/bootstrap-vue.min.css'
import 'vue-select/dist/vue-select.css'
import './assets/css/relationlist.css'

import {library} from '@fortawesome/fontawesome-svg-core'
import {faMinus, faUnlink, faSpinner, faCogs, faTimes} from '@fortawesome/free-solid-svg-icons'
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

import App from './components/App.vue'
import Store from './store'

import Focus from './directives/focus.js';

library.add(
    faMinus, faUnlink, faSpinner, faCogs, faTimes
);


export class cnRelationList {
    constructor(config) {

        Vue.use(Vuex);
        Vue.use(VueAxios, axios);

        Vue.use(LayoutPlugin);
        Vue.use(CardPlugin);
        Vue.use(FormGroupPlugin);
        Vue.use(FormSelectPlugin);
        Vue.use(FormInputPlugin);
        Vue.use(FormCheckboxPlugin);
        Vue.use(FormTextareaPlugin);
        Vue.use(CollapsePlugin);
        Vue.use(ButtonPlugin);
        Vue.use(AlertPlugin);

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

        let attributes = data.attributes || {};
        let items = data.items || [];

        let globals = this.setDefaultFields(data.globals || {}, definitions.globals || {});

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
     * @param values
     * @param fields
     */
    setDefaultFields(values = {}, fields = {}) {
        let results = values;

        for (let i in fields){
            if(fields.hasOwnProperty(i)){
                let field = fields[i];

                if (field.hasOwnProperty('default') &&
                    (!values.hasOwnProperty(i) || values[i] === '')) {

                    Object.assign(results, {[i]: field.default})
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

        _self.store.dispatch('setReady', false);
        $.ajax({
            url: _self.store.getters.getOptions.fetchurl,
            method: 'POST',
            data: {
                "items": JSON.stringify(items)
            }
        }).done(function(data){

            if (data.hasOwnProperty('items') && data.items.length > 0)
                elements = data.items;

            _self.store.dispatch('setItems', elements);
            _self.store.dispatch('setStatus', true);

        }).always(function(){
            _self.store.dispatch('setReady', true);
        });
        return elements;
    }

}
