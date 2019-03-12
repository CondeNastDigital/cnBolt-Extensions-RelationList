export default () => {
    return {
        // initial state
        state: {
            items: [],
        },

        // getters
        getters: {
            /**
             * returns the items from the store
             * @param state
             * @returns {*|Array|items|Function|null|default.computed.items|DataTransferItemList}
             */
            getItems(state) {
                return state.items;
            },
            getSimpleItems(state) {
                let items = [];

                 state.items.forEach(function(value){
                     items.push(value.id)
                 });
                return items;
            }
        },

        // actions
        actions: {
            /**
             *
             * @param context
             * @param item
             */
            addItem(context, item) {
                context.commit('addItem', item);
            },

            /**
             *
             * @param context
             * @param items
             */
            setItems(context, items) {
                context.commit('setItems', items)
            },

            /**
             *
             * @param context
             * @param item
             */
            removeItem(context, item) {
                context.commit('removeItem', item);
            },

            /**
             *
             * @param context
             * @param list
             */
            updateItemList(context, list) {
                context.commit('updateItemList', list);
            }
        },

        // mutations
        mutations: {
            /**
             * adds a new item to the store
             * @param state
             * @param item
             */
            addItem(state, item) {
                state.items.push(item);
            },

            /**
             *
             * @param state
             * @param items
             */
            setItems(state, items) {
                state.items = items;
            },

            /**
             * removes an item from the store
             * @param state
             * @param item
             */
            removeItem(state, item) {
                state.items.splice(state.items.indexOf(item), 1);
            },

            /**
             * updates the itemlist in the store
             * @param state
             * @param list
             */
            updateItemList(state, list) {
                state.items = list
            }
        }
    }
}
