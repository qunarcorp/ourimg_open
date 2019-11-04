import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { Button, Progress } from 'antd';
import { medalIconMap } from 'CONST/map';
import { CREDITS_MAP } from 'CONST/router';
import QRoute from 'COMPONENT/qRoute';
import { POINTS } from 'CONST/imgUrl';
const { MISSION_ONE, MISSION_TWO } = POINTS;
import THEME_COLOR from 'CONST/style';
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    hash: state.router.location.hash,
    isAuth: state.store.global.isAuth,
    isLogin: state.store.global.isLogin,
    checkLogin: state.store.global.checkLogin,
    locationPath: state.store.global.locationPath,
    userInfo: state.store.global.userInfo,
    rankingBorad: state.store.credits.rankingBorad,
    getDailyMission: state.store.credits.getDailyMission,
    dailyMission: state.store.credits.dailyMission,
    getCreditsBoard: state.store.credits.getCreditsBoard
}))

@withRouter
@observer
class Credits extends Component {

    componentDidMount() {
        this.props.checkLogin();
        this.props.getDailyMission();
        this.props.getCreditsBoard();

    }

    goUpload = () => {
        this.props.history.push('/upload');
    }

    render() {
        const { isLogin, pathname, history, locationPath, isAuth, rankingBorad, userInfo,
            dailyMission } = this.props;
        const { total_points, current_points, last_date_points } = userInfo.points_info;
        return (
            <div className="q-credits-page">
                <div className={`q-credits-header
                    ${pathname === '/credits/creditsStore' || pathname === '/credits/goodsDetail'
                    ? '' : 'simple'}`}>
                    <div className="user-panel q-content">
                        <div className="credits-user">
                            <div className="user-info">
                                <img
                                    src={userInfo.userImg}
                                    className="avatar"
                                />
                                <div className="name">
                                    {userInfo.userName}
                                </div>
                            </div>
                            <div className="credits-info">
                                <div className="label">总积分</div>
                                <div className="credits-value">{total_points}</div>
                            </div>
                            <div className="credits-info">
                                <div className="label">积分余额</div>
                                <div className="credits-value">{current_points}</div>
                            </div>
                            <div className="credits-info">
                                <div className="label">今日积分</div>
                                <div className="credits-value">{last_date_points}</div>
                            </div>
                        </div>
                        <div className="credits-route">
                            <div className="route-link">
                                <Link to={{ pathname: "/credits/creditsStore"}}>
                                    <div className={`user-btn ${pathname === '/credits/creditsStore' ? 'active' : ''}`}>
                                        <i className="icon-font-ourimg">&#xe386;</i>
                                    </div>
                                </Link>
                                <div className="route-label">积分首页</div>
                            </div>
                            <div className="route-link">
                                <Link to={{ pathname: "/credits/myCredits"}}>
                                    <div className={`user-btn ${pathname === '/credits/myCredits' ? 'active' : ''}`}>
                                        <i className="icon-font-ourimg">&#xe4fc;</i>
                                    </div>
                                </Link>
                                <div className="route-label">积分明细</div>
                            </div>
                            <div className="route-link">
                                <Link to={{ pathname: "/credits/creditsRule"}}>
                                    <div className={`user-btn ${pathname === '/credits/creditsRule' ? 'active' : ''}`}>
                                        <i className="icon-font-ourimg">&#xf49b;</i>
                                    </div>
                                </Link>
                                <div className="route-label">如何获取</div>
                            </div>
                        </div>
                    </div>
                    {
                        pathname === '/credits/creditsStore' &&
                        <div className="mission-panel q-content">
                            <div className="daily-mission">
                                <div className="mission-content">
                                    <div className="mission-item">
                                        <img className="index-icon" src={MISSION_ONE}/>
                                        <div className="mission-info">
                                            <div className="mission-title">上传10张图片
                                                <span className="extra-credits">奖励5积分
                                                    <i className="icon-font-ourimg">&#xe159;</i>
                                                </span>
                                            </div>
                                            <div className="mission-desc">每日完成10张图片上传时，除基础奖励积分外，将额外获得5分为任务奖励积分</div>
                                            <div className="mission-desc">
                                                <Progress
                                                    className="mission-progress"
                                                    strokeColor={THEME_COLOR}
                                                    percent={dailyMission.upload.complete_num*10}
                                                    showInfo={false}/>
                                                完成{dailyMission.upload.complete_num}/10
                                            </div>
                                        </div>
                                        <Button
                                            className="link-btn" type="primary"
                                            disabled={dailyMission.upload.complete_state === 'done'}
                                            onClick={this.goUpload}>
                                            {dailyMission.upload.complete_state === 'done' ? '已完成' : '去上传'}
                                        </Button>
                                    </div>
                                    <div className="mission-item">
                                        <img className="index-icon" src={MISSION_TWO}/>
                                        <div className="mission-info">
                                            <div className="mission-title">晒旅行打卡照&带着小驼去旅行
                                                {/* <span className="extra-credits">奖励50积分
                                                    <i className="icon-font-ourimg">&#xe159;</i>
                                                </span> */}
                                            </div>
                                            <div className="mission-desc">【6.21-8.30】1、悬赏旅游风景照，奖励<span className="highlight">10积分</span>/图；2、关键词中需标注 #带着小驼去旅行#，奖励<span className="highlight">20积分</span>/图【积分可叠加】</div>
                                            {/* <div className="mission-desc">
                                                本期任务城市：{dailyMission.city.city_arr.join('，')}；
                                                悬赏截止时间：{dailyMission.city.city_end_time}
                                            </div> */}
                                        </div>
                                        <Button
                                            className="link-btn" type="primary"
                                            disabled={dailyMission.city.complete_state === 'done'}
                                            onClick={this.goUpload}>
                                            {dailyMission.city.complete_state === 'done' ? '已完成' : '去上传'}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <div className="score-board">
                                <div className="board-title">
                                    <i className="icon-font-ourimg">&#xf3e6;</i>
                                    <img className="title-icon" src="/img/board-title.png"/>
                                </div>
                                {
                                    rankingBorad.map((item, index) =>
                                        <div className="board-list" key={item.name}>
                                        {
                                            index < 3 ? <img className="index-icon" src={medalIconMap[index]}/> :
                                            <span className="board-index">{index + 1}</span>
                                        }
                                            <Link className="board-name" to={`/user?uid=${item.username}`}>{item.name}</Link>
                                            <span className="board-score"><i className="icon-font-ourimg">&#xe4fc;</i>{item.total_points}分</span>
                                        </div>
                                    )
                                }
                            </div>
                        </div>
                    }
                </div>
                <div className="q-credits-content q-content">
                    {Object.keys(CREDITS_MAP).map((key, index) => {
                        const item = CREDITS_MAP[key];
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
            </div>
        );
    }
}

export default Credits;
