import React, { Component } from 'react'
import ReactDOM from 'react-dom'
import { Layout, Modal } from 'antd';
import { observer, inject } from 'mobx-react';
import { Router, Switch, Route } from 'react-router-dom';
import QHeader from 'COMPONENT/qHeader';
import QFooter from 'COMPONENT/qFooter';
import QRouter from 'COMPONENT/qRoute';
import { ROUTER_MAP } from 'CONST/router';
import 'UTIL/dateUtil';
const { Header, Content, Footer } = Layout;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    name: state.store.name,
    isAuth: state.store.global.isAuth,
    isLogin: state.store.global.isLogin,
    checkLogin: state.store.global.checkLogin,
    locationPath: state.store.global.locationPath
}))

@observer
class App extends Component {
    componentDidMount() {
        this.props.checkLogin();
        //阻止拖拽图片浏览器自动打开
        document.addEventListener('drop', function (e) {
        e.preventDefault()
        }, false)
        document.addEventListener('dragover', function (e) {
        e.preventDefault()
        }, false)
    }
    render() {
        const { isLogin, pathname, history, locationPath, isAuth } = this.props;
        return (
            <Router history={history}>
                <Layout className="layout">
                    <QHeader></QHeader>
                    <Content>
                    <Switch>
                        {
                            Object.keys(ROUTER_MAP).map((key) => {        
                                const item = ROUTER_MAP[key];
                                    return (
                                        <QRouter 
                                            history={history}
                                            key={item.path} 
                                            logicData={item}
                                            isLogin={isLogin}
                                            needLogin={item.needLogin}
                                            needAuth={item.needAuth}
                                            isAuth={isAuth}
                                            pathname={pathname}
                                            exact={item.exact} 
                                            path={item.path} 
                                            component={item.component}
                                            routes={item.routes} 
                                            locationPath={locationPath}
                                        />
                                    );
                            })
                        }
                        </Switch>
                    </Content>
                    <QFooter></QFooter>
                </Layout>
            </Router>
        )
    }
}

export default App;