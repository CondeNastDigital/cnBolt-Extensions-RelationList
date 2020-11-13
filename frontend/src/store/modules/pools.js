export default () => {
  return {
    // initial state
    state: {
      pools: {},
    },

    // getters
    getters: {

      getAllPools(state, getters) {
        return getters.getConfig.pools || {}
      },

      getPoolFor: (state, getters) => (pool) => {
        let pools = getters.getOptions.pools
        let ret = []

        try {
          ret = JSON.parse(pools);
        } catch(e) {

          if (typeof pools !== 'object') {
            //We fake a pool object here, if the pools config is not an array (for backwards compatibility)
            ret = {
              [pool]: getters.getOptions.pools || false
            }
          }
        }
        return getters.getAllPools[ret[pool]] || {};
      },

      getPoolSourcesFor: (state, getters) => (pool) => {
        return getters.getPoolFor(pool).sources || false
      },

      getDefaultSourcesFor: (state, getters) => (pool) => {
        let defaults = getters.getPoolFor(pool).sources_default || Object.keys(getters.getPoolFor(pool).sources || {});
        return defaults || [];
      }
    },

    // actions
    actions: {},

    // mutations
    mutations: {}
  }
}
