import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { Tabs, Pagination, Select, Checkbox, Table } from "antd";
import QBlank from "COMPONENT/qBlank";
import { orderStatusMap, creditsTimeMap } from 'CONST/map';
const TabPane = Tabs.TabPane;
const Option = Select.Option;

const creditsColumns = [{
    title: '日期',
    dataIndex: 'point_date',
    key: 'point_date'
  }, {
    title: '收入/支出',
    dataIndex: 'change_points',
    key: 'change_points'
  }, {
    title: '当前余额',
    dataIndex: 'old_points',
    key: 'old_points'
  }, {
    title: '详细说明',
    dataIndex: 'point_desc',
    key: 'point_desc',
    render: (text, record) => (
        record.operate_source === 'exchange' ?
        <div>兑换
            <Link
                to={{ pathname: "/credits/goodsDetail", search: `?id=${record.product_eid}` }}
            >{record.product_title}</Link>
            x{record.product_num}
        </div>
        : <div>{text}</div>
    )
}];
const exchangeColumns = [{
    title: '商品',
    dataIndex: 'product_title',
    key: 'product_title',
    width: 400,
    render: (text, record) => (
        <Link className="item-info" to={{ pathname: "/credits/goodsDetail", search: `?id=${record.product_eid}` }}>
            <img src={record.product_img_url} className="item-img"/>
            <div className="item-desc" >
                <p>{text} x{record.exchange_count}</p>
                <p>{record.product_points}积分/个</p>
            </div>
        </Link>
    )
  }, {
    title: '消耗积分',
    dataIndex: 'exchange_points',
    key: 'exchange_points'
  }, {
    title: '兑换日期',
    dataIndex: 'create_date',
    key: 'create_date'
  }, {
    title: '状态',
    dataIndex: 'state',
    key: 'state',
    render: text => orderStatusMap[text]
  }, {
    title: '发货时间',
    dataIndex: 'ship_date',
    key: 'ship_date'
  }, {
    title: '备注',
    dataIndex: 'address',
    key: 'address',
    width: 200,
    render: (text, record) => (
        [<div>{text}</div>, <div>{record.mobile} 收</div>]
    )
}];
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    myCreditsRecord: state.store.credits.myCreditsRecord,
    myCreditsTotal: state.store.credits.myCreditsTotal,
    myExchangeRecord: state.store.credits.myExchangeRecord,
    myExchangeTotal: state.store.credits.myExchangeTotal,
    getCreditsRecord: state.store.credits.getCreditsRecord,
    getExchangeRecord: state.store.credits.getExchangeRecord
}))

@withRouter
@observer
class MyCredits extends Component {
    state = {
        tabKey: 'CREDITS',
        creditsPage: {
            current: 1,
            pageSize: 10
        },
        exchangePage: {
            current: 1,
            pageSize: 10
        },
        timeRange: 'all',
        creditsType: ''
    };

    componentDidMount() {
        let nextparams = new URLSearchParams(this.props.history.location.search);
        let tabKey = nextparams.get("tab");
        if (tabKey) {
            this.switchTab(tabKey);
        } else {
            this.loadCredits();
        }
    }

    loadCredits = () => {
        const { creditsPage, timeRange, creditsType } = this.state;
        let params = {
            offset: (creditsPage.current - 1) * creditsPage.pageSize,
            limit: creditsPage.pageSize,
            time_select: timeRange,
            point_type: creditsType
        };
        this.props.getCreditsRecord(params);
    }

    switchTab = (tab) => {
        this.setState({
            tabKey: tab
        });
        if (tab === 'CREDITS') {
            this.loadCredits();
        } else if (tab === 'EXCHANGE') {
            const { current, pageSize } = this.state.exchangePage;
            this.props.getExchangeRecord({
                offset: (current - 1) * pageSize,
                limit: pageSize
            });
        }
    }

    onTimeChange = (value) => {
        this.setState({
            creditsPage: {
                current: 1,
                pageSize: this.state.creditsPage.pageSize
            },
            timeRange: value
        }, this.loadCredits);
    }

    onTypeChange = (type, value) => {
        let finalType = this.state.creditsType === type && !value ? '' : type;
        this.setState({
            creditsPage: {
                current: 1,
                pageSize: this.state.creditsPage.pageSize
            },
            creditsType: finalType
        }, this.loadCredits);
    }

    onCreditsPageChange = (current, pageSize) => {
        this.setState({
            creditsPage: {
                current,
                pageSize
            }
        }, this.loadCredits);
    }

    onExchangePageChange = (current, pageSize) => {
        this.props.getExchangeRecord({
            offset: (current - 1) * pageSize,
            limit: pageSize
        });
        this.setState({
            exchangePage: {
                current,
                pageSize
            }
        });
    }

    render() {
        let { creditsPage, exchangePage, timeRange, creditsType, tabKey } = this.state;
        let { myCreditsTotal, myExchangeTotal, myCreditsRecord, myExchangeRecord } = this.props;
        return (
            <div className="credits-content">
                <Tabs className="credits-tab" activeKey={tabKey} onChange={this.switchTab}>
                    <TabPane tab='积分明细' key='CREDITS'>
                        <div className="my-credits-content">
                            <div className="tool-bar">
                                <Select className="time-filter" value={timeRange} onChange={this.onTimeChange}>
                                {
                                    Object.keys(creditsTimeMap).map(value =>
                                        <Option value={value}>{creditsTimeMap[value]}</Option>
                                    )
                                }
                                </Select>
                                <Checkbox
                                    className="type-filter"
                                    checked={creditsType === 'income'}
                                    onChange={(e)=>this.onTypeChange('income', e.target.checked)}
                                >仅看收入</Checkbox>
                                <Checkbox
                                    className="type-filter"
                                    checked={creditsType === 'expenses'}
                                    onChange={(e)=>this.onTypeChange('expenses', e.target.checked)}
                                >仅看支出</Checkbox>
                            </div>
                            { myCreditsRecord.length === 0 ? <QBlank type='empty' /> :
                                [<Table
                                    key="credits-table"
                                    pagination={false}
                                    dataSource={myCreditsRecord}
                                    columns={creditsColumns} />,
                                <Pagination
                                    key="credits-pagination"
                                    className="credits-pagination"
                                    hideOnSinglePage
                                    total={myCreditsTotal}
                                    current={creditsPage.current}
                                    pageSize={creditsPage.pageSize}
                                    onChange={this.onCreditsPageChange}
                                    onShowSizeChange={(current, size)=>this.onCreditsPageChange(1, size)}
                                    showSizeChanger showQuickJumper />]
                            }
                        </div>
                    </TabPane>
                    <TabPane tab='兑换记录' key='EXCHANGE'>
                        { myExchangeRecord.length === 0 ? <QBlank type='empty' /> :
                            <div className="my-exchange-content">
                                <Table pagination={false} dataSource={myExchangeRecord} columns={exchangeColumns} />
                                <Pagination
                                    className="credits-pagination"
                                    hideOnSinglePage
                                    total={myExchangeTotal}
                                    current={exchangePage.current}
                                    pageSize={exchangePage.pageSize}
                                    onChange={this.onExchangePageChange}
                                    onShowSizeChange={(current, size)=>this.onExchangePageChange(1, size)}
                                    showSizeChanger showQuickJumper />
                        </div>}
                    </TabPane>
                </Tabs>
            </div>
        );
    }
}

export default MyCredits;
