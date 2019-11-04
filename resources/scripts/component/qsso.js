import React, { Component } from 'react';
import { inject, observer } from 'mobx-react';
import { Link, withRouter } from 'react-router-dom';
import { Modal, Tooltip, Menu, Form, Icon, Input, Button, message } from 'antd';
import { arrIntersection } from 'UTIL/util';
import cookie from 'js-cookie';
import { globalApi } from 'CONST/api'
@inject(state => ({
    name: state.store.name,
    isAuth: state.store.global.isAuth,
    isLogin: state.store.global.isLogin,
    logout: state.store.global.logout,
    userInfo: state.store.global.userInfo,
    cartCount: state.store.global.cartCount
}))

@withRouter
@observer
export default class Qsso extends Component {

    state = {
        dropdownMenu: false,
        loginVisible: false,
        formData: {
            username: '',
            password: '',
        }
    }

    login = () => {
        $.get(globalApi.loginWay, {action: 'get_login_way'})
            .then(response => {
                if (response.data == 'qsso' && !!document.getElementById('new-qsso-login')) {
                    QSSO.attach('new-qsso-login', '/login.php?user_url=' + encodeURIComponent(window.location.href));
                    QSSO.login();
                }else{
                    this.setState({
                        loginVisible: true
                    })
                }
            })
    }

    logout = () => {
        const { logout } = this.props;
        Modal.confirm({
            title: '确认要退出?',
            onOk() {
                logout();
            },
            onCancel() {
            }
        });
    }

    goUser  = () => {
        if (this.props.location.pathname + this.props.location.search === '/user?uid=' + this.props.userInfo.userName) {
            location.reload();
        } else {
            this.props.history.push('/user?uid=' + this.props.userInfo.userName);
        }
    }

    goMaterial = () => {
        if (this.props.location.pathname + this.props.location.search === '/material/myUpload?uid=' + this.props.userInfo.userName) {

            location.reload();
        } else {
            this.props.history.push('/material/myUpload?uid=' + this.props.userInfo.userName);
        }
    }

    goMessage = () => {
        if (this.props.location.pathname + this.props.location.search === '/material/message?uid=' + this.props.userInfo.userName) {
            location.reload();
        } else {
            this.props.history.push('/material/message?uid=' + this.props.userInfo.userName);
        }
    }

    goMyPoints = () => {
        if (location.hash  === '#/credits/myCredits' ) {
            location.reload();
        } else {
            this.props.history.push('/credits/myCredits');
        }
    }

    goAdmin = () => {
        if (this.props.location.pathname + this.props.location.search === '/material/imgAudit?uid=' + this.props.userInfo.userName) {
            location.reload();
        } else {
            this.props.history.push('/material/imgAudit?uid=' + this.props.userInfo.userName);
        }
    }

    goMyFile = () => {
        if (this.props.location.pathname + this.props.location.search === '/material/myFile') {
            location.reload();
        } else {
            this.props.history.push('/material/myFile');
        }
    }

    goUpload = () => {
        this.props.history.push('/upload');
    }

    goCart = () => {
        this.props.history.push('/cart');
    }

    goHelp = () => {
        this.props.history.push('/help');
    }

    goCredits = () => {
        this.props.history.push('/credits/creditsStore');
    }

    // hasAuthority = (role) => {
    //     return !(role && this.props.userInfo.role.indexOf(role) === -1);
    // }

    onParamsChange = (key, e) => {
        let formData = this.state.formData;

        this.setState({
            formData: {
                ... formData,
                [key]: e.target.value
            }
        })
    }

    handleSubmit = (e) => {
        console.log(this.state.formData.username, this.state.formData.password)
        let formData = this.state.formData
        if (this.state.formData.username.length == 0) {
            message.warning('账号必填');
        }else if (this.state.formData.password.length == 0) {
            message.warning('密码必填');
        }else{
            $.post(globalApi.login, formData, {'Content-Type': 'application/x-www-form-urlencoded'})
                .then(response => {
                    if (response.status == 0) {
                        window.location.reload()
                    }else{
                        message.error(response.message);
                    }
                });
        }
    }

    onCancel = () => {
        this.setState({
            loginVisible: false,
            formData: {
                username: "",
                password: "",
            }
        })
    }

    render() {
        let { isLogin, userInfo, cartCount } = this.props;
        let { dropdownMenu, formData } = this.state;

        return (
            <div className="qsso">
                <div className="qsso-group" style={{display: isLogin ? 'flex' : 'none'}}>
                    <Tooltip placement="bottom" title="积分商城" overlayClassName="explain-tip">
                        <span className="log-span icon-span" onClick={this.goCredits}>&#xf28e;</span>
                    </Tooltip>
                    <Tooltip placement="bottom" title="购物车" overlayClassName="explain-tip">
                    <span className="log-span icon-span relative" onClick={this.goCart}>&#xf50f;
                    {
                        cartCount > 0 && <span className="cart-num">{cartCount}</span>
                    }
                    </span>
                    </Tooltip>
                    <Tooltip placement="bottom" title="上传" overlayClassName="explain-tip">
                        <span className="log-span icon-span" onClick={this.goUpload}>&#xf50a;</span>
                    </Tooltip>
                    <Tooltip placement="bottom" title="帮助" overlayClassName="explain-tip">
                        <span className="log-span icon-span" onClick={this.goHelp}>&#xf0c9;</span>
                    </Tooltip>
                    <div className="dropdown-container" style={{ overflow: dropdownMenu ? 'visible' : 'hidden' }}>
                        <div onMouseLeave={() => {this.setState({ dropdownMenu: false })}} className="container">
                            <span id="dropdown"
                                className="log-span username"
                                onClick={this.goUser}
                                onMouseEnter={() => {this.setState({ dropdownMenu: true })}}>
                                {userInfo.realName ? '你好，' + userInfo.realName : userInfo.userName}
                            </span>
                            <div className="dropdown-card">
                                <span onClick={this.goUser}>我的主页</span>
                                <span onClick={this.goMaterial}>素材管理</span>
                                {
                                    arrIntersection(['1'], [userInfo.auth_state]) &&
                                    <span onClick={this.goMyFile}>我的授权书</span>
                                }
                                <span onClick={this.goMessage}>消息中心</span>
                                <span onClick={this.goMyPoints}>我的积分</span>
                                {
                                    arrIntersection(['admin', 'super_admin'], userInfo.role) &&
                                    <span onClick={this.goAdmin}>管理员中心</span>
                                }
                                <span onClick={this.logout}>退出</span>
                            </div>
                        </div>
                        </div>
                </div>
                <span style={{display: isLogin ? 'none' : 'inline-block'}} id="new-qsso-login" className="log-span" onClick={this.login}>登录</span>
                <Modal
                    visible={this.state.loginVisible}
                    footer={ null }
                    title={ null }
                    onCancel={this.onCancel}
                    >
                    <Form onSubmit={this.handleSubmit} className="qsso-login-form">
                        <Form.Item>
                            <Input
                                prefix={<Icon type="user" style={{ color: 'rgba(0,0,0,.25)' }} />}
                                placeholder="请输入用户名" value={ formData.username }
                                onChange={this.onParamsChange.bind(this, 'username')}
                                />
                        </Form.Item>
                        <Form.Item>
                            <Input
                                prefix={<Icon type="lock" style={{ color: 'rgba(0,0,0,.25)' }} />}
                                type="password"
                                placeholder="请输入密码" value={ formData.password }
                                onChange={this.onParamsChange.bind(this, 'password')}
                                />
                        </Form.Item>
                        <Form.Item>
                            <Button type="primary" htmlType="submit" className="login-form-button">
                                Login
                            </Button>
                        </Form.Item>
                    </Form>
                </Modal>
            </div>
        )
    }
}
