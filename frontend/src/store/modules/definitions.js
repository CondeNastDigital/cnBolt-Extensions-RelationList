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
            getDefinitions(state) {
                return {
                    globals: state.globals,
                    attributes: state.attributes
                };
            }
        },

        // actions
        actions: {
            /**
             *
             * @param context
             * @param data
             */
            setDefinitions(context, data) {
                context.commit('setDefinitions', data)
            },
        },

        // mutations
        mutations: {
            /**
             * sets the definitions
             * @param state
             * @param definitions
             */
            setDefinitions(state, definitions) {
                state.globals = definitions.globals || {};
                state.attributes = definitions.attributes || {} ;
            },
        }

    }
}
