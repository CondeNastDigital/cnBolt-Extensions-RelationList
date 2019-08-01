export default () => {
    return {
        // initial state
        state: {
            attributes: [],
        },

        // getters
        getters: {
            /**
             * returns the attributes from the store
             * @param state
             * @returns {*|Array|items|Function|null|default.computed.items|DataTransferItemList}
             */
            getAttributes(state) {
                return state.attributes;
            },
            getSimpleAttributes(state) {
                let attributes = [];

                 state.attributes.forEach(function(value){
                     attributes.push(value.id)
                 });
                return attributes;
            }
        },

        // actions
        actions: {
            /**
             *
             * @param context
             * @param item
             */
            addAttribute(context, attribute) {
                context.commit('addAttribute', attribute);
            },

            /**
             *
             * @param context
             * @param items
             */
            setAttributes(context, attributes) {
                context.commit('setAttributes', attributes)
            },

            /**
             *
             * @param context
             * @param item
             */
            removeAttribute(context, attribute) {
                context.commit('removeItem', attribute);
            }
        },

        // mutations
        mutations: {
            /**
             * adds a new item to the store
             * @param state
             * @param item
             */
            addAttribute(state, attribute) {
                state.attributes.push(attribute);
            },

            /**
             *
             * @param state
             * @param items
             */
            setAttributes(state, attributes) {
                state.attributes = attributes;
            },

            /**
             * removes an item from the store
             * @param state
             * @param item
             */
            removeAttribute(state, attribute) {
                state.attributes.splice(state.attributes.indexOf(attribute), 1);
            }
        }
    }
}
