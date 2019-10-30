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

            /**
             *
             * @param state
             * @param getters
             * @return {function(*): *}
             */
            getItem: (state, getters) => (id) => {
                return getters.getItems.find(item => item.id === id);
            },

            /**
             *
             * @param state
             * @return {[]}
             */
            getSimpleItems(state) {
                let items = [];

                 state.items.forEach(function(value){
                     items.push({
                         'id': value.id || false,
                         'service': value.service || false,
                         'type': value.type || false,
                         'attributes': value.attributes || []
                     })
                 });
                return items;
            },

            /**
             *
             * @param state
             * @param getters
             * @return {function(*=): (*|{})}
             */
            getAttributes: (state, getters) => (id) => {
                let item = getters.getItem(id);
                return item.attributes || {};
            },
        },

        // actions
        actions: {
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
            addItem(context, item) {
                context.commit('addItem', item);
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
                context.commit('setItems', list);
            },

            /**
             *
             * @param context
             * @param payload (item, attributes)
             */
            setAttributes(context, payload){

                let items = context.getters.getItems;
                let item = payload.item;

                item.attributes = payload.attributes;

                items[items.indexOf(item)] = item;

                context.commit('setItems', items);

            }
        },

        // mutations
        mutations: {
            /**
             *
             * @param state
             * @param items
             */
            setItems(state, items) {
                state.items = items;
            },

            /**
             * adds a new item to the store
             * @param state
             * @param item
             */
            addItem(state, item) {
                state.items.push(item);
            },

            /**
             * removes an item from the store
             * @param state
             * @param item
             */
            removeItem(state, item) {
                state.items.splice(state.items.indexOf(item), 1);
            }
        }
    }
}
