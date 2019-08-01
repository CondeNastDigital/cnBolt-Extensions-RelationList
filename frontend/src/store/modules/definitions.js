export default () => {
    return {
        // initial state
        state: {
            globals: {},
            items: {}
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
                    items: state.items
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
                state.items = definitions.items || {} ;
            },
        }

    }
}
