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
    getAuditUserListDataSource: state.store.statistics.getAuditUserListDataSource,
    getAuditStatistics: state.store.statistics.getAuditStatistics,
}))
@withRouter
@observer
class Audit extends Component {
    state = {
        checkList: {
            audit_num: false,
            pass_num: false,
            reject_num: false,
            week_audit_num: false,
            week_pass_num: false,
            week_reject_num: false,
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
            width: '15%'
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
            width: '25%'
        },
        {
            title: '审核总量',
            dataIndex: 'audit_num',
            key: 'audit_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '审核通过量',
            dataIndex: 'pass_num',
            key: 'pass_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '驳回量',
            dataIndex: 'reject_num',
            key: 'reject_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '本周审核总量',
            dataIndex: 'week_audit_num',
            key: 'week_audit_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '本周审核通过量',
            dataIndex: 'week_pass_num',
            key: 'week_pass_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '本周驳回量',
            dataIndex: 'week_reject_num',
            key: 'week_reject_num',
            sorter: true,
            width: '10%'
        },
    ];

    handlePage(pagination, filters, sorter) {
        this.props.statistics.pagination = pagination;
        this.props.statistics.setQueryParams(this.state.tabType, sorter.field, sorter.order)
        this.props.getAuditUserListDataSource();
    }

    componentDidMount() {
        this.props.getAuditUserListDataSource();
        this.props.getAuditStatistics();
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
            this.props.getAuditUserListDataSource();
        })
    }

    render() {
        let { auditStatistics, auditUserListDataSource, pagination } = this.props.statistics;
        let checkList = this.state.checkList
        let { query, handleSearch, handleSearchValueChange } = this.props.statistics

        return (
          <div className="statistics-panel">
              <div className="panel-bar">
                  <div className={ `panel ${ checkList.audit_num ? 'check' : '' }` } onClick={ () => this.onTabChange('audit_num') }>
                      <span className="main-title">审核总量</span>{auditStatistics['total_audit_num']}
                  </div>
                  <div className={ `panel ${ checkList.pass_num ? 'check' : '' }` } onClick={ () => this.onTabChange('pass_num') }>
                      <span className="main-title">审核通过量</span>{auditStatistics['total_pass_num']}
                  </div>
                  <div className={ `panel ${ checkList.reject_num ? 'check' : '' }` } onClick={ () => this.onTabChange('reject_num') }>
                      <span className="main-title">驳回量</span>{auditStatistics['total_reject_num']}
                  </div>
                  <div className={ `panel ${ checkList.week_audit_num ? 'check' : '' }` } onClick={ () => this.onTabChange('week_audit_num') }>
                      <span className="main-title">本周审核总量</span>{auditStatistics['total_week_audit_num']}
                  </div>
                  <div className={ `panel ${ checkList.week_pass_num ? 'check' : '' }` } onClick={ () => this.onTabChange('week_pass_num') }>
                      <span className="main-title">本周审核通过量</span>{auditStatistics['total_week_pass_num']}
                  </div>
                  <div className={ `panel ${ checkList.week_reject_num ? 'check' : '' }` } onClick={ () => this.onTabChange('week_reject_num') }>
                      <span className="main-title">本周驳回量</span>{auditStatistics['total_week_reject_num']}
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
                      <Button className="export-btn" type="primary" href={ statisticExportApi['auditStatisticsExport'] } target='_blank'>
                          导出全部
                      </Button>
                  </Col>
              </Row>
              <Table
                  dataSource={auditUserListDataSource.slice()}
                  columns={this.columns}
                  pagination={pagination}
                  rowKey="username"
                  onChange={ (pagination, filters, sorter) => this.handlePage(pagination, filters, sorter) }
              />
          </div>
        );
    }
}

export default Audit;
