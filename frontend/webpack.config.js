// webpack.config.js
const Vue = require('vue');
const path = require('path');

const ExtractTextPlugin = require('extract-text-webpack-plugin');
const extractCSS = new ExtractTextPlugin('styles.min.css');

const clientConfig = {
    target: 'web',
    mode: 'production',
    // This is the "main" file which should include all other modules
    entry: './src/app.js',
    // Where should the compiled file go?
    output: {
        //path: path.resolve(__dirname, 'dist'),
        filename: 'bundle.web.js',
        library: "CnRelationList",
        libraryExport: "cnRelationList",
        libraryTarget: "var"
    },
    resolve: {
        alias: {
            /* main components */
            'vue': 'vue/dist/vue.runtime.min.js',
            'vuex': 'vuex/dist/vuex.min.js',
            'axios': 'axios/dist/axios.min.js',
            'VueAxios': 'vue-axios/dist/vue-axios.min.js',

            /* bootstrap */
            'bootstrap-vue/es/components/form-group': 'bootstrap-vue/es/components/form-group',
            'bootstrap-vue/es/components/form-select': 'bootstrap-vue/es/components/form-select',
            'bootstrap-vue/es/components/form-input': 'bootstrap-vue/es/components/form-input',
            'bootstrap-vue/es/components/form-checkbox': 'bootstrap-vue/es/components/form-checkbox',
            'bootstrap-vue/es/components/form-textarea': 'bootstrap-vue/es/components/form-textarea',
            'bootstrap-vue/es/components/card': 'bootstrap-vue/es/components/card',
            'bootstrap-vue/es/components/collapse': 'bootstrap-vue/es/components/collapse',
            'bootstrap-vue/es/components/button': 'bootstrap-vue/es/components/button',
            'bootstrap-vue/es/components/layout': 'bootstrap-vue/es/components/layout',

            /* fontawesome */
            '@fortawesome/fontawesome-svg-core': '@fortawesome/fontawesome-svg-core',
            '@fortawesome/free-solid-svg-icons': '@fortawesome/free-solid-svg-icons',
            '@fortawesome/vue-fontawesome': '@fortawesome/vue-fontawesome',

            /* other vendor */
            'vuedraggable': 'vuedraggable/dist/vuedraggable.common.js'
        }
    },

    module: {
        // Special compilation rules
        rules: [
            {
                // Ask webpack to check: If this file ends with .js, then apply some transforms
                test: /\.js$/,
                // Transform it with babel
                loader: 'babel-loader',
                // don't transform node_modules folder (which don't need to be compiled)
                exclude: /node_modules/
            },
            {
                // Ask webpack to check: If this file ends with .vue, then apply some transforms
                test: /\.vue$/,
                // don't transform node_modules folder (which don't need to be compiled)
                exclude: /(node_modules|bower_components)/,
                // Transform it with vue
                loader: 'vue-loader'

            },
            {
                test: /\.css$/,
                use: extractCSS.extract([
                    'css-loader',
                    'postcss-loader'
                ])
            },
            {
                test: /\.(jpe?g|png|gif|woff|woff2|eot|ttf|svg)(\?[a-z0-9=.]+)?$/,
                loader: 'url-loader?limit=100000'
            }
        ]
    },
    plugins: [
        extractCSS
    ]
};

const serverConfig = {
    target: 'node',
    mode: 'production',
    // This is the "main" file which should include all other modules
    entry: './src/app.js',
    // Where should the compiled file go?
    output: {
        //path: path.resolve(__dirname,'dist'),
        filename: 'bundle.node.js',
        library: "CnRelationList",
        libraryExport: "cnRelationList",
        libraryTarget: "var"
    },
    resolve: {
        alias: {
            /* main components */
            'vue': 'vue/dist/vue.runtime.min.js',
            'vuex': 'vuex/dist/vuex.min.js',
            'axios': 'axios/dist/axios.min.js',
            'VueAxios': 'vue-axios/dist/vue-axios.min.js',

            /* bootstrap */
            'bootstrap-vue/es/components/form-group': 'bootstrap-vue/es/components/form-group',
            'bootstrap-vue/es/components/form-select': 'bootstrap-vue/es/components/form-select',
            'bootstrap-vue/es/components/form-input': 'bootstrap-vue/es/components/form-input',
            'bootstrap-vue/es/components/form-checkbox': 'bootstrap-vue/es/components/form-checkbox',
            'bootstrap-vue/es/components/form-textarea': 'bootstrap-vue/es/components/form-textarea',
            'bootstrap-vue/es/components/button': 'bootstrap-vue/es/components/button',
            'bootstrap-vue/es/components/layout': 'bootstrap-vue/es/components/layout',
            'bootstrap-vue/es/components/card': 'bootstrap-vue/es/components/card',
            'bootstrap-vue/es/components/collapse': 'bootstrap-vue/es/components/collapse',

            /* fontawesome */
            '@fortawesome/fontawesome-svg-core': '@fortawesome/fontawesome-svg-core',
            '@fortawesome/free-solid-svg-icons': '@fortawesome/free-solid-svg-icons',
            '@fortawesome/vue-fontawesome': '@fortawesome/vue-fontawesome',

            /* other vendor */
            'vuedraggable': 'vuedraggable/dist/vuedraggable.common.js'
        }
    },

    module: {
        // Special compilation rules
        rules: [
            {
                // Ask webpack to check: If this file ends with .js, then apply some transforms
                test: /\.js$/,
                // Transform it with babel
                loader: 'babel-loader',
                // don't transform node_modules folder (which don't need to be compiled)
                exclude: /node_modules/
            },
            {
                // Ask webpack to check: If this file ends with .vue, then apply some transforms
                test: /\.vue$/,
                // don't transform node_modules folder (which don't need to be compiled)
                exclude: /(node_modules|bower_components)/,
                // Transform it with vue
                loader: 'vue-loader'

            },
            {
                test: /\.css$/,
                use: extractCSS.extract([
                    'css-loader',
                    'postcss-loader'
                ])
            },
            {
                test: /\.(jpe?g|png|gif|woff|woff2|eot|ttf|svg)(\?[a-z0-9=.]+)?$/,
                loader: 'url-loader?limit=100000'
            }
        ]
    },
    plugins: [
        extractCSS
    ],
    devServer: {
        port: 3000
    }
};

module.exports = [clientConfig, serverConfig];
