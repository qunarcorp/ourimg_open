import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { storeTabMap } from 'CONST/map';
import GoodsManage from './goodsManage';
import ExchangeOrder from './exchangeOrder';
import EditGoods from './editGoods';
import EditRule from './editRule';

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    tabKey: state.store.storeManage.tabKey,
    onChangeTab: state.store.storeManage.onChangeTab
}))
@withRouter
@observer
class StoreManage extends Component {

    componentWillUnmount() {
        this.props.onChangeTab('MANAGE');
    }

    render() {
        const { tabKey, onChangeTab } = this.props;
        return (
            <div className="auth-store-page">
                <div className="tab-bar">
                    <div className="store-tab publish" onClick={()=>onChangeTab('PUBLISH')}>
                        <i className="icon-font-ourimg">&#xf298;</i>商品发布
                    </div>
                    <div className="store-tab manage" onClick={()=>onChangeTab('MANAGE')}>
                        <i className="icon-font-ourimg">&#xf08e;</i>商品管理
                    </div>
                    <div className="store-tab exchange" onClick={()=>onChangeTab('EXCHANGE')}>
                        <i className="icon-font-ourimg">&#xe477;</i>兑换明细
                    </div>
                    <div className="store-tab rule" onClick={()=>onChangeTab('RULE')}>
                        <i className="icon-font-ourimg">&#xf04d;</i>规则录入
                    </div>
                </div>
                <div className="store-content content">
                    <div className="store-content-title">{storeTabMap[tabKey]}</div>
                    {
                        tabKey === 'PUBLISH' && <EditGoods />
                    }
                    {
                        tabKey === 'MANAGE' && <GoodsManage />
                    }
                    {
                        tabKey === 'EXCHANGE' && <ExchangeOrder />
                    }
                    {
                        tabKey === 'RULE' && <EditRule />
                    }
                </div>
            </div>
        );
    }
}

export default StoreManage;
