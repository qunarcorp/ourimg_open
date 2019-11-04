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
    getUserStatisticsDataSource: state.store.statistics.getUserStatisticsDataSource,
    userStatisticsDataSource: state.store.statistics.userStatisticsDataSource,
    getUserStatisticsNum: state.store.statistics.getUserStatisticsNum,
}))
@withRouter
@observer
class UserStatistics extends Component {
    state = {
        checkList: {
            upload_user_num: false,
            visit_user_num: true,
        },
        tabType: ''
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
            width: '10%'
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
            width: '20%'
        },
        {
            title: '首次访问时间',
            dataIndex: 'first_visit_time',
            key: 'first_visit_time',
            sorter: true,
            width: '20%'
        },
        {
            title: '上传图片总数',
            dataIndex: 'img_num',
            key: 'img_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '授权时间',
            dataIndex: 'auth_date',
            key: 'auth_date',
            sorter: true,
            width: '20%'
        },
        {
            title: '首次上传时间',
            dataIndex: 'earliest_upload_time',
            key: 'earliest_upload_time',
            sorter: true,
            width: '20%'
        },
    ];

    handlePage(pagination, filters, sorter) {
        this.props.statistics.pagination = pagination;
        this.props.statistics.setQueryParams(this.state.tabType, sorter.field, sorter.order)
        this.props.getUserStatisticsDataSource();
    }

    componentDidMount() {
        this.props.getUserStatisticsDataSource();
        this.props.getUserStatisticsNum();
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
            this.props.getUserStatisticsDataSource();
        })
    }

    render() {
        let { userStatisticsNum, userStatisticsDataSource, pagination } = this.props.statistics;
        let checkList = this.state.checkList
        let { query, handleSearch, handleSearchValueChange } = this.props.statistics

        return (
          <div className="statistics-panel">
              <div className="panel-bar">
                  <div className={ `panel ${ checkList.visit_user_num ? 'check' : '' }` } onClick={ () => this.onTabChange('visit_user_num') }>
                      <span className="main-title">访问用户数</span>{userStatisticsNum['visit_user_num']}
                  </div>
                  <div className={ `panel ${ checkList.upload_user_num ? 'check' : '' }` } onClick={ () => this.onTabChange('upload_user_num') }>
                      <span className="main-title">上传用户数</span>{userStatisticsNum['upload_user_num']}
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
                      <Button className="export-btn" type="primary" href={ statisticExportApi['userStatisticsExport'] } target='_blank'>
                          导出全部
                      </Button>
                  </Col>
              </Row>
              <Table
                  dataSource={userStatisticsDataSource.slice()}
                  columns={this.columns}
                  pagination={pagination}
                  rowKey="username"
                  onChange={ (pagination, filters, sorter) => this.handlePage(pagination, filters, sorter) }
              />
          </div>
        );
    }
}

export default UserStatistics;
