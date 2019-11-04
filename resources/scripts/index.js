// import 'babel-polyfill';
import 'whatwg-fetch';
import React from 'react';
import ReactDOM from 'react-dom';
// import createHashHistory from 'history/createHashHistory';
const createHashHistory = require("history").createHashHistory;
import { Provider } from 'mobx-react';
import { RouterStore, syncHistoryWithStore } from 'mobx-react-router';
import { http } from 'UTIL/util.js';
import App from 'PAGE/app';
import ViewStore from './store/index';
import { LocaleProvider, message } from 'antd';
import zhCN from 'antd/lib/locale-provider/zh_CN';
import utils from 'UTIL/params'
// import QMessage from 'COMPONENT/qMessage';

const hashHistory = createHashHistory();
const routingStore = new RouterStore();
const history = syncHistoryWithStore(hashHistory, routingStore);

const notify = (option) => {
    if (typeof option === 'string') {
        // QMessage('error',option);
    } else {
        const { type, text } = option;
        // QMessage(type,text);
        message[type](text)
    }
};
message.config({
    top: 100,
    maxCount: 1
});
window.$ = http(notify);
window.utils = utils;

const stores = {
    history,
    router: routingStore,
    store: new ViewStore()
};

ReactDOM.render(
    <Provider {...stores}>
        <LocaleProvider locale={zhCN}>
            <App />
        </LocaleProvider>
    </Provider>,
    document.getElementById('app')
);

// hot-reload
if (module.hot) {
    module.hot.accept();
}
