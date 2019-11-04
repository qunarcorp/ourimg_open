import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { statisticTabMap } from 'CONST/map';
import UserImg from './userImg';
import Place from './place';
import Points from './points';
import Interaction from './interaction';
import Audit from './audit';
import UserStatistics from './userStatistics';

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    tabKey: state.store.statistics.tabKey,
    onChangeTab: state.store.statistics.onChangeTab
}))
@withRouter
@observer
class Statistics extends Component {

    componentWillUnmount() {
        this.props.onChangeTab('USER_IMG');
    }

    render() {
        const { tabKey, onChangeTab } = this.props;
        return (
            <div className="statistics-page">
                <div className="tab-bar">
                    <div className="store-tab img" onClick={()=>onChangeTab('USER_IMG')}>
                        图片统计
                    </div>
                    <div className="store-tab place" onClick={()=>onChangeTab('PLACE')}>
                        拍摄地点覆盖
                    </div>
                    <div className="store-tab points" onClick={()=>onChangeTab('POINTS')}>
                        积分数据统计
                    </div>
                    <div className="store-tab interaction" onClick={()=>onChangeTab('INTERACTION')}>
                        交互数据统计
                    </div>
                    <div className="store-tab audit" onClick={()=>onChangeTab('AUDIT')}>
                        审核数据统计
                    </div>
                    <div className="store-tab user_statistics" onClick={()=>onChangeTab('USER_STATISTICS')}>
                        用户数据统计
                    </div>
                </div>
                <div className="statistics-content content">
                    <div className="statistics-content-title">{statisticTabMap[tabKey]}</div>
                    {
                        tabKey === 'USER_IMG' && <UserImg />
                    }
                    {
                        tabKey === 'PLACE' && <Place />
                    }
                    {
                        tabKey === 'POINTS' && <Points />
                    }
                    {
                        tabKey === 'INTERACTION' && <Interaction />
                    }
                    {
                        tabKey === 'AUDIT' && <Audit />
                    }
                    {
                        tabKey === 'USER_STATISTICS' && <UserStatistics />
                    }
                </div>
            </div>
        );
    }
}

export default Statistics;
