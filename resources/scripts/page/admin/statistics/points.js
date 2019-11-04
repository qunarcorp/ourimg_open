import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Table, Input, Row, Col, Button } from "antd";
const Search = Input.Search;
import { statisticExportApi } from "CONST/api";

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    statistics: state.store.statistics,
    onChangeTab: state.store.statistics.onChangeTab,
    getPointsStatistic: state.store.statistics.getPointsStatistic,
    getUserPointsDataSource: state.store.statistics.getUserPointsDataSource,
    setQueryParams: state.store.statistics.setQueryParams,
}))
@withRouter
@observer
class Points extends Component {
    state = {
        checkList: {
            total_points: true,
            pass_points: false,
            praise_points: false,
            favorite_points: false,
            task_points: false,
            star_points: false,
            current_points: false,
            delete_points: false,
            exchange_points: false,
            download_points: false,
        },
        tabType: 'total_points'
    }

    columns = [
        {
            title: '用户',
            dataIndex: 'name',
            key: 'name',
            width: "10%",
            render: (name, item) => {
                return (
                    <Link className="tab-user-and-dept" to={ { pathname: "/user", search: `?uid=${item.username}` } }>
                        <span className="cursor-pointer">{name}</span>
                    </Link>
                )
            },
        },
        {
            title: '组织架构',
            dataIndex: 'dept',
            key: 'dept',
            width: "20%",
            render: (dept, item) => {
                return (
                    <Link className="tab-user-and-dept" to={ { pathname: "/user", search: `?uid=${item.username}` } }>
                        <span className="cursor-pointer">{dept}</span>
                    </Link>
                )
            },
        },
        {
            title: '总积分',
            dataIndex: 'total_points',
            key: 'total_points',
            sorter: true,
        },
        {
            title: '上传积分',
            dataIndex: 'pass_points',
            key: 'pass_points',
            sorter: true,
        },
        {
            title: '点赞积分',
            dataIndex: 'praise_points',
            key: 'praise_points',
            sorter: true,
        },
        {
            title: '收藏积分',
            dataIndex: 'favorite_points',
            key: 'favorite_points',
            sorter: true,
        },
        {
            title: '任务积分',
            dataIndex: 'task_points',
            key: 'task_points',
            sorter: true,
        },
        {
            title: '精选积分',
            dataIndex: 'star_points',
            key: 'star_points',
            sorter: true,
        },
        {
            title: '剩余积分',
            dataIndex: 'current_points',
            key: 'current_points',
            sorter: true,
        },
        {
            title: '删除积分',
            dataIndex: 'delete_points',
            key: 'delete_points',
            sorter: true,
        },
        {
            title: '兑换积分',
            dataIndex: 'exchange_points',
            key: 'exchange_points',
            sorter: true,
        },
        {
            title: '下载积分',
            dataIndex: 'download_points',
            key: 'download_points',
            sorter: true,
        }
    ];

    handlePage(pagination, filters, sorter) {
        this.props.statistics.pagination = pagination;
        this.props.setQueryParams(this.state.tabType, sorter.field, sorter.order)
        this.props.getUserPointsDataSource();
    }

    componentDidMount() {
        this.props.getPointsStatistic();
        this.props.getUserPointsDataSource();
    }

    onTabChange(tabType) {
        this.props.statistics.pagination = {
            ... this.props.statistics.pagination,
            current: 1
        }
        let checkList = this.state.checkList
        for(let key in checkList) {
            checkList[key] = tabType == key
        }

        this.props.statistics.resetSearchQuery()
        this.setState({
            checkList,
            tabType
        }, () => {
            this.props.statistics.smallTabKey = tabType
            this.props.getUserPointsDataSource();
        })
    }

    render() {
        let { pointsStatistic, userPointsDataSource, pagination } = this.props.statistics;
        let checkList = this.state.checkList
        let { query, handleSearch, handleSearchValueChange } = this.props.statistics

        return (
            <div className="statistics-panel">
                <div className="panel-bar">
                    <div className={ `panel ${ checkList.total_points ? 'check' : '' }` } onClick={ () => this.onTabChange('total_points') }>
                        <span className="main-title">总积分</span>{pointsStatistic['total_points']}
                    </div>
                    <div className={ `panel ${ checkList.pass_points ? 'check' : '' }` } onClick={ () => this.onTabChange('pass_points') }>
                        <span className="main-title">上传积分</span>{pointsStatistic['pass_points']}
                    </div>
                    <div className={ `panel ${ checkList.praise_points ? 'check' : '' }` } onClick={ () => this.onTabChange('praise_points') }>
                        <span className="main-title">点赞积分</span>{pointsStatistic['praise_points']}
                    </div>
                    <div className={ `panel ${ checkList.favorite_points ? 'check' : '' }` } onClick={ () => this.onTabChange('favorite_points') }>
                        <span className="main-title">收藏积分</span>{pointsStatistic['favorite_points']}
                    </div>
                    <div className={ `panel ${ checkList.task_points ? 'check' : '' }` } onClick={ () => this.onTabChange('task_points') }>
                        <span className="main-title">任务积分</span>{pointsStatistic['task_points']}
                    </div>
                    <div className={ `panel ${ checkList.star_points ? 'check' : '' }` } onClick={ () => this.onTabChange('star_points') }>
                        <span className="main-title">精选积分</span>{pointsStatistic['star_points']}
                    </div>
                    <div className={ `panel ${ checkList.current_points ? 'check' : '' }` } onClick={ () => this.onTabChange('current_points') }>
                        <span className="main-title">剩余积分</span>{pointsStatistic['current_points']}
                    </div>
                    <div className={ `panel ${ checkList.delete_points ? 'check' : '' }` } onClick={ () => this.onTabChange('delete_points') }>
                        <span className="main-title">删除积分</span>{pointsStatistic['delete_points']}
                    </div>
                    <div className={ `panel ${ checkList.exchange_points ? 'check' : '' }` } onClick={ () => this.onTabChange('exchange_points') }>
                        <span className="main-title">兑换积分</span>{pointsStatistic['exchange_points']}
                    </div>
                    <div className={ `panel ${ checkList.download_points ? 'check' : '' }` } onClick={ () => this.onTabChange('download_points') }>
                        <span className="main-title">下载积分</span>{pointsStatistic['download_points']}
                    </div>
                </div>
                <Row>
                    <Col span={8} offset={13}>
                        <Search
                            enterButton
                            className="search"
                            placeholder="请输入..."
                            value={query}
                            enterButton
                            onSearch={handleSearch}
                            onChange={handleSearchValueChange}
                        />
                    </Col>
                    <Col span={3}>
                        <Button className="export-btn" type="primary" href={ statisticExportApi['userPointStatisticsExport'] } target='_blank'>
                            导出全部
                        </Button>
                    </Col>
                </Row>
                <Table
                    dataSource={userPointsDataSource.slice()}
                    columns={this.columns}
                    pagination={pagination}
                    rowKey="username"
                    onChange={(pagination, filters, sorter) => this.handlePage(pagination, filters, sorter)}
                />
            </div>
        );
    }
}

export default Points;
