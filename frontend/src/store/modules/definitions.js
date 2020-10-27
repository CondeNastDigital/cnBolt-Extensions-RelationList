export default () => {
    return {
        // initial state
        state: {
            globals: {},
            attributes: {}
        },

        // getters
        getters: {
            /**
             * returns the definitions
             * @param state
             * @returns {default.state.definitions|{}|{state, getters, actions, mutations}|{"@fortawesome/fontawesome-svg-core"}}
             */
            getDefinitionGlobals(state) {
                return state.globals
            },
            /**
             * returns the attributes
             * @param state
             * @returns {default.state.definitions|{}|{state, getters, actions, mutations}|{"@fortawesome/fontawesome-svg-core"}}
             */
            getDefinitionAttributes(state) {
                return state.attributes
            },
        },

        // actions
        actions: {
            /**
             *
             * @param context
             * @param data
             */
            setDefinitionGlobals(context, data) {
                context.commit('setDefinitionGlobals', data)
            },
            /**
             *
             * @param context
             * @param data
             */
            setDefinitionAttributes(context, data) {
                context.commit('setDefinitionAttributes', data)
            },
        },

        // mutations
        mutations: {
            /**
             * sets the definitions
             * @param state
             * @param definitions
             */
            setDefinitionGlobals(state, definitions) {
                state.globals = definitions.globals || {};
            },
            /**
             * sets the definitions
             * @param state
             * @param definitions
             */
            setDefinitionAttributes(state, definitions) {
                state.attributes = definitions.attributes || {} ;
            },
        }

    }
}
