import React, { Component } from 'react'
import { Layout, Menu, Input } from 'antd';
import { observer, inject } from 'mobx-react';
import {  Link, withRouter } from 'react-router-dom';
import Qsso from './qsso'
import { isChromeBrowser } from 'UTIL/util';
const { Header } = Layout;
const Search = Input.Search;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    globalSearch: state.store.global.globalSearch,
    updateSearchParams: state.store.global.updateSearchParams
}))
@withRouter
@observer
class QHeader extends Component {

    state = {
        keyword: '',
        menukey: 1,
        isChrome: true
    }

    componentDidMount() {
        this.setState({
            isChrome: isChromeBrowser()
        })
    }

    componentWillReceiveProps(nextprops) {
        if (nextprops.globalSearch.keyword !== this.props.globalSearch.keyword) {
            this.setState({
                keyword: nextprops.globalSearch.keyword
            });
        }
    }

    inputChange = (e) => {
        this.setState({
            keyword: e.target.value
        });
    }

    onSearch = (value) => {
        let pathname = this.props.history.location.pathname;
        this.props.updateSearchParams({
            keyword: value.replace(/^\s+|\s+$/g,"")
        });
        pathname === '/list' ? '' : this.props.history.push(`/list`);
    }

    test = () => {
        this.setState({
            menukey: this.state.menukey + 1
        })
    }

    render() {
        const { isChrome } = this.state;
        return (
            [<div className="q-header" key="header">
            {
                !isChrome &&
                <div className="browser-hint" key="hint">
                    <div className="hint-content">
                        建议使用Chrome浏览器, 获得更好的浏览效果！
                        <i className="icon-font-ourimg close-btn"
                            onClick={()=>this.setState({
                                isChrome: true
                            })}
                        >&#xf3f3;</i>
                    </div>
                </div>
            }
                <Header className="q-navbar">
                <div className="q-content">
                <div className="logo" onClick={this.test}><Link to="/">骆驼素材库</Link></div>
                <Menu
                    key={this.state.menukey}
                    className="q-menu"
                    theme="dark"
                    mode="horizontal"
                    style={{ lineHeight: '64px' }}
                >
                    <Menu.Item key="1">
                        <Link to="/"><span className="menu-span">首页</span></Link>
                    </Menu.Item>
                    <Menu.Item key="2">
                        <Link to="/list"><span className="menu-span">照片</span></Link>
                    </Menu.Item>
                    {/* <Menu.Item key="3">
                        <Link to="/list">矢量图</Link>
                    </Menu.Item>
                    <Menu.Item key="4">
                        <Link to="/list">PSD</Link>
                    </Menu.Item>
                    <Menu.Item key="5">
                        <Link to="/list">PPT模版</Link>
                    </Menu.Item>
                    <Menu.Item key="6">
                        <Link to="/list">VI规范</Link>
                    </Menu.Item> */}
                    <Menu.Item key="7">
                        <Link to="/rank"><span className="menu-span">排行榜</span></Link>
                    </Menu.Item>
                </Menu>
                <div className="search-container" style={{
                    height: this.props.history.location.pathname === '/' ? '0' : '100%'
                }}>
                    <Search
                        style={{
                            borderRadius: 0
                        }}
                        maxLength={20}
                        value={this.state.keyword}
                        onChange={this.inputChange}
                        placeholder="请输入关键字"
                        onSearch={this.onSearch}
                        enterButton
                        />
                </div>
                <Qsso></Qsso></div>
                </Header>
            </div>,<div className={`q-header-document ${isChrome ? '' : 'high'}`} key="document"></div>]
        )
    }
}

export default QHeader;
