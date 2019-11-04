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
    getUserInteractionDataSource: state.store.statistics.getUserInteractionDataSource,
    getInteractionNumStatistic: state.store.statistics.getInteractionNumStatistic,
}))
@withRouter
@observer
class Interaction extends Component {
    state = {
        checkList: {
            browse_num: false,
            download_num: false,
            praise_num: false,
            favorite_num: false,
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
            width: '20%'
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
            width: '30%'
        },
        {
            title: '交互用户数',
            dataIndex: 'interaction_people',
            key: 'interaction_people',
            sorter: true,
            width: '10%'
        },
        {
            title: '浏览图片量',
            dataIndex: 'browse_num',
            key: 'browse_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '下载图片量',
            dataIndex: 'download_num',
            key: 'download_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '点赞图片量',
            dataIndex: 'praise_num',
            key: 'praise_num',
            sorter: true,
            width: '10%'
        },
        {
            title: '收藏图片量',
            dataIndex: 'favorite_num',
            key: 'favorite_num',
            sorter: true,
            width: '10%'
        },
    ];

    handlePage(pagination, filters, sorter) {
        this.props.statistics.pagination = pagination;
        this.props.statistics.setQueryParams(this.state.tabType, sorter.field, sorter.order)
        this.props.getUserInteractionDataSource();
    }

    componentDidMount() {
        this.props.getUserInteractionDataSource();
        this.props.getInteractionNumStatistic();
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
            this.props.getUserInteractionDataSource();
        })
    }

    render() {
        let { interactionNumStatistic, userInteractionDataSource, pagination } = this.props.statistics;
        let checkList = this.state.checkList
        let { query, handleSearch, handleSearchValueChange } = this.props.statistics

        return (
          <div className="statistics-panel">
              <div className="panel-bar">
                  <div className={ `panel ${ checkList.browse_num ? 'check' : '' }` } onClick={ () => this.onTabChange('browse_num') }>
                      <span className="main-title">图片浏览量</span>{interactionNumStatistic['total_browse_num']}
                  </div>
                  <div className={ `panel ${ checkList.download_num ? 'check' : '' }` } onClick={ () => this.onTabChange('download_num') }>
                      <span className="main-title">图片下载量</span>{interactionNumStatistic['total_download_num']}
                  </div>
                  <div className={ `panel ${ checkList.praise_num ? 'check' : '' }` } onClick={ () => this.onTabChange('praise_num') }>
                      <span className="main-title">图片点赞量</span>{interactionNumStatistic['total_praise_num']}
                  </div>
                  <div className={ `panel ${ checkList.favorite_num ? 'check' : '' }` } onClick={ () => this.onTabChange('favorite_num') }>
                      <span className="main-title">图片收藏量</span>{interactionNumStatistic['total_favorite_num']}
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
                      <Button className="export-btn" type="primary" href={ statisticExportApi['userInteractionStatisticsExport'] } target='_blank'>
                          导出全部
                      </Button>
                  </Col>
              </Row>
              <Table
                  dataSource={userInteractionDataSource.slice()}
                  columns={this.columns}
                  pagination={pagination}
                  rowKey="username"
                  onChange={(pagination, filters, sorter) => this.handlePage(pagination, filters, sorter)}
              />
          </div>
        );
    }
}

export default Interaction;
