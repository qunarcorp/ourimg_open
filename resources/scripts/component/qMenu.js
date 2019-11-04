import React, { Component } from 'react'
import { Menu } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter, Link } from 'react-router-dom';
import { get } from 'UTIL/mapMethod';
import { arrIntersection } from 'UTIL/util';
import QRoute from 'COMPONENT/qRoute';
const SubMenu = Menu.SubMenu;
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    hash: state.router.location.hash,
    isAuth: state.store.global.isAuth,
    isLogin: state.store.global.isLogin,
    checkLogin: state.store.global.checkLogin,
    locationPath: state.store.global.locationPath,
    userInfo: state.store.global.userInfo
}))
@withRouter
@observer
class QMenu extends Component {

    constructor(props) {
        super(props);
        this.state = {
            bread: '',
            selectedKey: [],
            openKeys: []
        }
    }
    componentDidMount() {
        this.getBread();
    }
    componentWillReceiveProps(nextprops) {
        this.getBread(nextprops);
    }

    getBread(props) {
        const { pathname = '', userInfo = {}, menuMap } = props || this.props,
        pathArr = pathname.split('/'),
        pathKey = get(pathArr, '[2]');
        let openKeys = [...this.state.openKeys];
        if (!pathKey[0] && menuMap[0].key.indexOf(pathArr[1]) !== -1) {
            let path = menuMap[0].children ? menuMap[0].children[0].path : menuMap[0].path;
            this.props.history.replace(path);
        }
        for (var menu of menuMap) {
            if (menu.children) {
                for (var sub of menu.children) {
                    if (!arrIntersection(menu.role, userInfo.role) && sub.path === pathname) {
                        this.props.history.replace('/');
                    }
                    if (sub.path === pathname) {
                        openKeys.push(menu.key);
                        break;
                    }
                }
            }
        }
        this.setState({
            selectedKey: get(this.props.routerMap[pathKey[0]], 'path'),
            openKeys
        })
    }

    onOpenChange = (openKeys) => {
        this.setState({
            openKeys
        })
    }

    render() {
        const { isLogin, pathname, history, locationPath, isAuth, userInfo, routerMap, menuMap } = this.props;
        const { selectedKey, openKeys } = this.state;
        return (
            <div className="q-menu-page">
                <Menu
                    mode="inline"
                    className="nav-container"
                    theme="light"
                    selectedKeys={selectedKey}
                    openKeys={openKeys}
                    onOpenChange={this.onOpenChange}
                >
                    {menuMap.map(item => {
                        if (item.children) {
                            return (
                                !arrIntersection(item.role, [...userInfo.role, userInfo.auth_state]) ? '' :
                                <SubMenu
                                    key={item.key}
                                    className='submenu-font'
                                    title={
                                        <span>
                                            <span className="submenu-font">{item.name}</span>
                                        </span>
                                    }
                                >
                                    {item.children.map(child => {
                                        return (
                                            !arrIntersection(child.role, [...userInfo.role, userInfo.auth_state]) ? '' :
                                            <Menu.Item key={child.key}>
                                                <Link to={`${child.path}?uid=${this.props.userInfo.userName}`} className='menu-item-font'>
                                                    {child.name}
                                                </Link>
                                            </Menu.Item>
                                        );
                                    })}
                                </SubMenu>
                            );
                        } else {
                            return (
                                !arrIntersection(item.role, [...userInfo.role, userInfo.auth_state]) ? '' :
                                <Menu.Item
                                    key={item.key}
                                >
                                    <Link to={`${item.path}?uid=${this.props.userInfo.userName}`} className="submenu-font">{item.name}</Link>
                                </Menu.Item>
                            );
                        }
                    })}
                </Menu>
                {Object.keys(routerMap).map((key, index) => {
                    const item = routerMap[key];
                    return (
                        <QRoute
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
                })}
            </div>
        );
    }
}

export default QMenu;
