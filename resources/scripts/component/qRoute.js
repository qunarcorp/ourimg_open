import React from 'react'
import { Route, Redirect } from 'react-router-dom';
import { message } from 'antd';

const QRoute = ({ component: Component, 
    routes, logicData, isLogin, 
    needLogin, needAuth, isAuth, 
    ...rest }) => (
    <Route
        {...rest} 
        render={(props) => {
            needLogin && !isLogin && message.error('请先进行QSSO登录');
            return needLogin && !isLogin 
            ? <Redirect
                to={{
                pathname: "/",
                state: { from: props.location }
                }}/>
            : <Component {...props} routes={routes}/>;
        }}
    />
);

export default QRoute;