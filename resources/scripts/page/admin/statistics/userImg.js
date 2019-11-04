import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { withRouter, Link } from "react-router-dom";
import { Table, Input, Row, Col, Button } from "antd";
const Search = Input.Search;
import { statisticExportApi } from "CONST/api";

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    statistics: state.store.statistics,
    getUserImgList: state.store.statistics.getUserImgList,
    getImgStatus: state.store.statistics.getImgStatus,
    setQueryParams: state.store.statistics.setQueryParams,
}))
@withRouter
@observer
class UserImg extends Component {
    state = {
        checkList: {
            all_img: true,
            to_submit: false,
            check_pending: false,
            pass: false,
            reject: false,
            del: false,
        },
        tabType: 'all_img',
        keysword: ''
    }
    columns = [
        {
            title: '用户',
            dataIndex: 'name',
            key: 'name',
            render: (name, item) => {
                return (
                    <Link className="tab-user-and-dept" to={ { pathname: "/user", search: `?uid=${item.username}` } }>
                        <span className="cursor-pointer">{name}</span>
                    </Link>
                )
            },
            width: "10%",
        },
        {
            title: '组织架构',
            dataIndex: 'dept',
            key: 'dept',
            render: (dept, item) => {
                return (
                    <Link className="tab-user-and-dept" to={ { pathname: "/user", search: `?uid=${item.username}` } }>
                        <span className="cursor-pointer">{dept}</span>
                    </Link>
                )
            },
            width: "20%",
        },
        {
            title: '上传总量',
            dataIndex: 'all_img_num',
            key: 'all_img_num',
            sorter: true,
        },
        {
            title: '草稿箱',
            dataIndex: 'to_submit_num',
            key: 'to_submit_num',
            sorter: true,
        },
        {
            title: '待审核',
            dataIndex: 'check_pending_num',
            key: 'check_pending_num',
            sorter: true,
        },
        {
            title: '已通过',
            dataIndex: 'pass_num',
            key: 'pass_num',
            sorter: true,
        },
        {
            title: '未通过',
            dataIndex: 'reject_num',
            key: 'reject_num',
            sorter: true,
        },
        {
            title: '已删除',
            dataIndex: 'del_num',
            key: 'del_num',
            sorter: true,
        }
    ];

    handlePage(pagination, filters, sorter) {
        this.props.statistics.pagination = pagination;
        this.props.statistics.setQueryParams(this.state.tabType, sorter.field, sorter.order)
        this.props.getUserImgList();
    }

    componentDidMount() {
        this.props.getUserImgList();
        this.props.getImgStatus();
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
            this.props.getUserImgList();
        })
    }

    render() {
        let { imgStatusStatistic, userImgDataSource, pagination } = this.props.statistics;
        let checkList = this.state.checkList
        let { query, handleSearch, handleSearchValueChange } = this.props.statistics

        return (
            <div className="statistics-panel">
                <div className="panel-bar">
                    <div className={ `panel ${ checkList.all_img ? 'check' : '' }` } onClick={ () => this.onTabChange('all_img') }>
                        <span className="main-title">总量</span>{imgStatusStatistic['all_img_num']}
                    </div>
                    <div className={ `panel ${ checkList.to_submit ? 'check' : '' }` } onClick={ () => this.onTabChange('to_submit') }>
                        <span className="main-title">草稿箱</span>{imgStatusStatistic['to_submit_num']}
                    </div>
                    <div className={ `panel ${ checkList.check_pending ? 'check' : '' }` } onClick={ () => this.onTabChange('check_pending') }>
                        <span className="main-title">待审核</span>{imgStatusStatistic['check_pending_num']}
                    </div>
                    <div className={ `panel ${ checkList.pass ? 'check' : '' }` } onClick={ () => this.onTabChange('pass') }>
                        <span className="main-title">已通过</span>{imgStatusStatistic['pass_num']}
                    </div>
                    <div className={ `panel ${ checkList.reject ? 'check' : '' }` } onClick={ () => this.onTabChange('reject') }>
                        <span className="main-title">未通过</span>{imgStatusStatistic['reject_num']}
                    </div>
                    <div className={ `panel ${ checkList.del ? 'check' : '' }` } onClick={ () => this.onTabChange('del') }>
                        <span className="main-title">已删除</span>{imgStatusStatistic['del_num']}
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
                        <Button className="export-btn" type="primary" href={ statisticExportApi['imgStatisticsExport'] } target='_blank'>
                            导出全部
                        </Button>
                    </Col>
                </Row>
                <Table
                    dataSource={userImgDataSource.slice()}
                    columns={this.columns}
                    pagination={pagination}
                    rowKey="username"
                    onChange={ (pagination, filters, sorter) => this.handlePage(pagination, filters, sorter) }
                />
            </div>
        );
    }
}

export default UserImg;
