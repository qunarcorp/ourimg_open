const webpack = require('webpack');
const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const ParallelUglifyPlugin = require('webpack-parallel-uglify-plugin');
//用于在构建前清除dist目录中的内容
// const { CleanWebpackPlugin } = require("clean-webpack-plugin");
const CopyWebpackPlugin = require('copy-webpack-plugin');

const config = {
    entry: './resources/app.js',
    output: {
        path: path.resolve(__dirname, 'htdocs'),
        filename: 'js/app.js'
    },
    module: {
        rules: [
            { test: /\.(js|jsx)$/, exclude: /node_modules/, loader: 'babel-loader' },
            {
                test: /\.css$/,
                loader: ExtractTextPlugin.extract({
                    fallback: "style-loader",
                    use: "css-loader"
                })
            },
            {
                test: /\.less$/,
                loader: ExtractTextPlugin.extract({
                    fallback: "style-loader",
                    use: [
                        {
                            loader: "css-loader"
                        },
                        {
                            loader: "less-loader",
                            options: { javascriptEnabled: true }
                        }
                    ]
                })
            },
            {
                test: /\.html$/,
                loader: 'html-loader'
            },
            {
                test: /\.(jpg|png|gif|jpeg)$/,
                loader: 'url-loader'
            },
            {
                test: /\.(ttf|eot|woff|woff2|svg)$/,
                loader: 'file-loader'
            },
        ]
    },
    resolve: {
        extensions: ['.js'],
        alias: {
            COMPONENT: path.resolve(__dirname, 'resources/scripts/component'),
            CONST: path.resolve(__dirname, 'resources/scripts/const'),
            PAGE: path.resolve(__dirname, 'resources/scripts/page'),
            STORE: path.resolve(__dirname, 'resources/scripts/store'),
            UTIL: path.resolve(__dirname, 'resources/scripts/util')
        }
    },
    plugins: [
        new HtmlWebpackPlugin({
            filename: "index.html",
            template: "resources/webapp/index.html",
        }),
        new ExtractTextPlugin('css/app.css'),
        new CopyWebpackPlugin([
            { from: 'resources/asset' },
            // { from: 'from/file.txt', to: 'to/file.txt' },
        ])
    ],
};

module.exports = config;