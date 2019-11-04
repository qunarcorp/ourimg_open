import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Tabs, Modal, Table, Spin, Icon } from "antd";
import autobind from "autobind-decorator";
import { arrIntersection } from 'UTIL/util';
import { rankMap } from 'CONST/map';

const TabPane = Tabs.TabPane;
const antIcon = <Icon type="loading" style={{ fontSize: 24 }} spin />;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    getList: state.store.rank.getList,
    getRankTab: state.store.rank.getRankTab,
    dataSource: state.store.rank.dataSource,
    rank: state.store.rank,
    // userInfo: state.store.global.userInfo,
    imgDataLoading: state.store.rank.imgDataLoading,
}))
@withRouter
@observer
class RankComponent extends Component {
    state = {
        rankTab: "browse",
        pagination: {
            current: 1,
            pageSize: 10
        },
        query: ""
    };

    columns = [
        {
            title: "rank",
            dataIndex: "rank",
            key: "rank",
            render: rank => {
                return rank == 1
                    ? <img className="q-rank-index-icon" src="/img/big_gold.png"/>
                    : (
                        rank == 2
                            ? <img className="q-rank-index-icon" src="/img/big_silver.png"/>
                            : (
                                rank == 3
                                    ? <img className="q-rank-index-icon" src="/img/big_copper.png"/>
                                    : <span className="q-rank-board-index">{rank}</span>
                            )
                    );
            }
        },
        {
            title: "姓名",
            dataIndex: "real_name",
            key: "real_name"
        },
        {
            title: "头像",
            dataIndex: "avatar",
            key: "avatar",
            render: avatar => (<img className="q-rank-user-avatar" src={avatar}/>)
        },
        {
            title: "分数",
            dataIndex: "aggregate",
            key: "aggregate",
            render: (aggregate, item) => {
                let rankTab = this.props.getRankTab()

                if (rankTab == "popularity") {
                    let {aggregate, total_favorite, total_praise} = item
                    return (
                        <div>
                            <span className="q-rank-score">人气值 {aggregate}</span>
                            <span className="q-rank-score">被点赞 {total_praise}</span>
                            <span className="q-rank-score">被收藏 {total_favorite}</span>
                        </div>
                    )
                }else if (rankTab == "browse") {
                    return (
                        <div>
                            <span className="q-rank-score">被浏览 {aggregate}</span>
                        </div>
                    )
                }else if (rankTab == "upload") {
                    return (
                        <div>
                            <span className="q-rank-score">图片贡献量 {aggregate}</span>
                        </div>
                    )
                }else if (rankTab == "points") {
                    return (
                        <div>
                            <span className="q-rank-score">总积分 {aggregate}</span>
                        </div>
                    )
                }else if (rankTab == "download") {
                    return (
                        <div>
                            <span className="q-rank-score">被下载量 {aggregate}</span>
                        </div>
                    )
                }
                return aggregate
            }
        },
        {
            title: "用户图片",
            dataIndex: "img_list",
            key: "img_list",
            render: this.userImgList
        }
    ];

    @autobind
    userImgList(imgList, item) {
        return (
            <div className="img-list">
                {
                    imgList.map((img_url, index) => {
                        return <div className="img-item" key={index}><img className="q-rank-user-img" src={img_url}/></div>
                    } )
                }
            </div>
        );
    }


    componentDidMount() {
        this.props.getList();
    }

    jumpUserIndex(record, index) {
      return {
          onClick:(e) => {
              location.href = `#/user?uid=${record.username}`
          }
      }
    }

    render() {
        const {dataSource, handleSwitchTab, handleSearch, handlePage, handleSearchValueChange, pagination} = this.props.rank;
        let {imgDataLoading} = this.props;

        return (
            <div className="q-content content">
                <Tabs className="material-tab" onChange={handleSwitchTab}
                    defaultActiveKey={this.props.getRankTab()}>
                    {Object.keys(rankMap).map(key => {
                        return <TabPane tab={rankMap[key]} key={key}/>;
                    })}
                </Tabs>
                <Table
                    dataSource={dataSource.slice()}
                    columns={this.columns}
                    className="table"
                    pagination={pagination}
                    rowKey="username"
                    onChange={pagination => handlePage(pagination)}
                    showHeader={false}
                    onRow={this.jumpUserIndex}
                />
            </div>
        )
    }
}

export default RankComponent;
