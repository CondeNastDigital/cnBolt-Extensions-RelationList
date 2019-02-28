export default () => {
    return {
        // initial state
        state: {
            globals: {},
        },

        // getters
        getters: {
            /**
             * returns the globals
             * @param state
             * @returns {default.state.globals|{}|{state, getters, actions, mutations}|{"@fortawesome/fontawesome-svg-core"}}
             */
            getGlobals(state) {
                return state.globals
            }
        },

        // actions
        actions: {
            /**
             *
             * @param context
             * @param data
             */
            setGlobals(context, data) {
                context.commit('setGlobals', data)
            }
        },

        // mutations
        mutations: {
            /**
             * sets the globals
             * @param state
             * @param globals
             */
            setGlobals(state, globals) {
                state.globals = globals;
            }
        }
    }
}