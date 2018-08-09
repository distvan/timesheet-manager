var path = require('path')
var webpack = require('webpack')

module.exports = {
    entry: './public/js/frontend/main.js',
    output: {
        path: path.resolve(__dirname, './public/js/dist'),
        publicPath: '/dist/',
        filename: 'frontend-dist.js'
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue',
                options: {
                    // vue-loader options go here
                }
            },
            {
                test: /\.js$/,
                loader: 'babel',
                exclude: /node_modules/
            },
            {
                test: /\.(png|jpg|gif|svg)$/,
                loader: 'file',
                options: {
                    name: '[name].[ext]?[hash]'
                }
            }
        ]
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue'
        }
    },
    resolveLoader: {
        moduleExtensions: ['-loader']
    },
    devServer: {
        historyApiFallback: true,
        noInfo: true
    },
    devtool: '#eval-source-map'
}

if (process.env.NODE_ENV === 'production') {
    module.exports.devtool = '#source-map'
    module.exports.plugins = (module.exports.plugins || []).concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.UglifyJsPlugin({
            compress: {
                warnings: false
            }
        }),
        new webpack.LoaderOptionsPlugin({
            minimize: true
        })
    ])
}