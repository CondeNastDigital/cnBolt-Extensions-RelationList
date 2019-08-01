import definitions from './modules/definitions'
import globals  from './modules/globals'
import attributes  from './modules/attributes'
import items    from './modules/items'

export default () =>  {
    return {
        // initial state
        state: {
            ready: true
        },

        // modules
        modules: {
            definitions: definitions(),
            globals: globals(),
            attributes: attributes(),
            items: items()
        },

        // getters
        getters: {
            /**
             * checks if the site has been loaded completely and if its ready for userinteractions
             * @param state
             * @returns {boolean|*|jQuery.ready|ready|Promise<ServiceWorkerRegistration>|Promise<void>|Promise<Animation>}
             */
            isReady(state) {
                return state.ready;
            },

            /**
             *
             * @param state
             * @returns {*}
             */
            getOptions(state) {
                return state.options
            }
        },

        // actions
        actions: {
            /**
             * @param context
             * @param status
             */
            setReady(context, status) {
                context.commit('setReady', status);
            },

            /**
             *
             * @param context
             * @param options
             */
            //
            setOptions(context, options) {
                context.commit('setOptions', options);
            }
        },

        // mutations
        mutations: {
            /**
             * sets the ready state, either true or false
             * @param state
             * @param status
             */
            setReady(state, status) {
                state.ready = status;
            },

            /**
             *
             * @param state
             * @param options
             */
            setOptions(state, options) {
                state.options = options;
            }
        }
    }
}