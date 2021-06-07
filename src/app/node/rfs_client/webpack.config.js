var webpack = require('webpack');
var path = require('path');
const current = process.cwd();
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

module.exports = [{
  entry: "./app/entry.js",
  // ファイルの出力設定
  output: {
    //  出力ファイルのディレクトリ名
    path: `${__dirname}/www/`,
    // 出力ファイル名
    filename: 'bundle.js'
  },
  // ソースマップを有効にする
  devtool: 'source-map',
  watch: true,
  // 追加
  watchOptions: {
    // 最初の変更からここで設定した期間に行われた変更は1度の変更の中で処理が行われる
    aggregateTimeout: 200,
    // ポーリングの間隔
    poll: 1000
  },
  plugins: [
    new webpack.ProvidePlugin({
      $: "jquery",
      jQuery: "jquery",
      moment: "moment",
      _: "lodash"
    }),
    new UglifyJSPlugin({
      compress: true,
      sourceMap: true,
      mangle: false // modified
    })
  ],
  module: {
    // Loaderの設定
    rules: [

      {
        test: /\.js$/,
        exclude: [/node_modules/, /lib/],
        loader: 'babel-loader',
        query: {
          compact: true,
          presets: ['es2015'],
          plugins: [
            'transform-runtime',
            'transform-regenerator',
            'transform-async-to-generator'
          ]
        }
      }, {
        test: require.resolve('jquery'),
        use: [{
          loader: 'expose-loader',
          options: 'jQuery'
        }, {
          loader: 'expose-loader',
          options: '$'
        }]
      }
    ]
  }
}, {
  entry: "./app/entry_css.js",
  // ファイルの出力設定
  output: {
    //  出力ファイルのディレクトリ名
    path: `${__dirname}/www/`,
    // 出力ファイル名
    filename: 'bundle_css.js'
  },
  // ソースマップを有効にする
  devtool: 'source-map',
  module: {
    // Loaderの設定
    rules: [

      // CSSファイルの読み込み
      {
        // 対象となるファイルの拡張子
        test: /\.css/,
        loader: ['style-loader', 'css-loader']
      }, {
        test: /\.(otf|eot|svg|ttf|woff|woff2|png|jpg|gif)$/,
        loaders: 'file-loader?name=asset/[name].[ext]'
      }, {
        test: /\.less$/,
        loader: ['style-loader', 'css-loader', 'less-loader']
      }
    ]
  }
}];
