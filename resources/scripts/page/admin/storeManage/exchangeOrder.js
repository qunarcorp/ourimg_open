import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Button, Pagination, Input, Row, Col, Select, Modal, AutoComplete } from 'antd';
import { orderStatusMap } from 'CONST/map';
const Search = Input.Search;
const Option = Select.Option;
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    orderTotal: state.store.storeManage.orderTotal,
    orderDataList: state.store.storeManage.orderDataList,
    getExchangeDetail: state.store.storeManage.getExchangeDetail,
    shipGoods: state.store.storeManage.shipGoods,
    suggestionList: state.store.storeManage.suggestionList,
    getOrderSuggestion: state.store.storeManage.getOrderSuggestion
}))
@withRouter
@observer
class ExchangeOrder extends Component {
    state = {
        page: {
            current: 1,
            pageSize: 10
        },
        status: '0',
        exchangeVisible: false,
        exchangeType: '1',
        orderStatus: '',
        query: '',
        itemDetail: {}
    };

    componentDidMount() {
        this.loadData();
    }

    loadData = () => {
        const { query, orderStatus, page } = this.state;
        let params = {
            offset: (page.current - 1) * page.pageSize,
            limit: page.pageSize,
            query,
            state: orderStatus
        };
        this.props.getExchangeDetail(params);
    }

    selectChange = (value) => {
        this.setState({
            page: {
                current: 1,
                pageSize: this.state.page.pageSize
            },
            orderStatus: value
        }, this.loadData);
    }

    queryChange = (value) => {
        this.setState({
            page: {
                current: 1,
                pageSize: this.state.page.pageSize
            },
            query: value
        });
    }

    handleSearch = (value) => {
        this.setState({
          dataSource: value ? searchResult(value) : [],
        });
    }

    onPageChange = (current, pageSize) => {
        this.setState({
            page: {
                current,
                pageSize
            }
        }, this.loadData);
    }

    showConfirmModal = (item) => {
        this.setState({
            exchangeVisible: true,
            itemDetail: item
        });
    }
    onExchangeCancel = () => {
        this.setState({
            exchangeVisible: false
        });
    }

    onConfirm = () => {
        this.props.shipGoods({orderId: this.state.itemDetail.order_id}, () => {
            this.setState({
                exchangeVisible: false
            });
            this.loadData();
        });
    }

    render() {
        let { page, exchangeVisible, orderStatus, query, itemDetail } = this.state;
        let { orderTotal, orderDataList, suggestionList, getOrderSuggestion } = this.props;
        return (
            <div className="exchange-order-panel">
                <div className="toole-bar">
                    兑换状态
                    <Select 
                        className="select-search" 
                        value={orderStatus}
                        onChange={this.selectChange}>
                        <Option value="">全部</Option>
                    {
                        Object.keys(orderStatusMap).map(value => 
                            <Option key={value} value={value}>{orderStatusMap[value]}</Option>
                        )
                    }
                    </Select>
                    <AutoComplete
                        className="input-search"
                        onChange={this.queryChange}
                        dataSource={suggestionList}
                        onSelect={this.queryChange}
                        onSearch={(value) => getOrderSuggestion({query: value})}
                        >
                            <Search 
                                value={query}
                                placeholder="请输入订单号/用户名等"
                                onSearch={(value, e)=>{
                                    e.stopPropagation();
                                    this.loadData();
                                }}
                                enterButton
                                onPressEnter={null}
                            />
                    </AutoComplete>
                </div>
                <div className="goods-table">
                {
                    orderDataList.map(item => 
                        <div className="goods-item" key={item.order_id}>
                            <Row className="base-info">
                                <Col className="info" span={5}>
                                    订单号：{item.order_id}
                                </Col>
                                <Col className="info" span={5}>
                                    兑换时间：{item.create_time}
                                </Col>
                            </Row>
                            <Row className="detail-info">
                                <Col className="info" span={11}>
                                    <div className="item-info">
                                        <img className="item-img" src={item.product_img_url}/>
                                        <div className="item-detail">
                                            <div className="item-name">{item.product_title}</div>
                                            <div className="item-desc">消耗积分：{item.exchange_points}积分</div>
                                        </div>
                                    </div>
                                </Col>
                                <Col className="info" span={2}>
                                    {item.exchange_count}
                                </Col>
                                <Col className="info" span={3}>
                                    {orderStatusMap[item.state]}
                                </Col>
                                <Col className="info" span={5}>
                                    <div className="user-detail">
                                        <div className="item-name">
                                            <i className="icon-font-ourimg">&#xf196;</i>
                                            {item.name}
                                        </div>
                                        <div className="item-desc">{item.address}</div>
                                        <div className="item-desc">{item.mobile}</div>
                                    </div>
                                </Col>
                                <Col className="info" span={3}>
                                {
                                    item.state !== 'exchange_fail' &&
                                    <div 
                                        className="primary-btn" 
                                        onClick={() => this.showConfirmModal(item)}
                                    >{item.state === 'exchange_success' ? '发货' : '发货详情'}</div>
                                }
                                </Col>
                            </Row>
                        </div>
                    )
                }
                </div>
                <Pagination 
                    className="credits-pagination"
                    hideOnSinglePage
                    total={orderTotal} 
                    current={page.current}
                    pageSize={page.pageSize}
                    onChange={this.onPageChange}
                    onShowSizeChange={(current, size)=>this.onPageChange(1, size)}
                    showSizeChanger showQuickJumper />
                <Modal
                    className="q-modal exchange-order-modal"
                    visible={exchangeVisible}
                    footer={null}
                    title={itemDetail.state === 'exchange_success' ? '商品发货' : '发货信息'}
                    onCancel={this.onExchangeCancel}
                    width={510}>
                        <div className="edit-confirm-content">
                            <img className="goods-img" src={itemDetail.product_img_url}/>
                            <div className="goods-info">
                                <div className="info-row">
                                    <span className="label">商品名称：</span>
                                    <span className="text">{itemDetail.product_title}</span>
                                </div>
                                <div className="info-row">
                                    <span className="label">兑换数量：</span>
                                    <span className="text">x{itemDetail.exchange_count}</span>
                                </div>
                                <div className="info-row">
                                    <span className="label">消耗积分：</span>
                                    <span className="text">{itemDetail.exchange_points}积分</span>
                                </div>
                                <div className="info-row">
                                    <span className="label">兑换者：</span>
                                    <span className="text">{itemDetail.username}</span>
                                </div>
                                <div className="info-row">
                                    <div className="label">收货信息：</div>
                                    <div className="text-group">
                                        <div className="text">{itemDetail.address}</div>
                                        <div className="text">{itemDetail.mobile}</div>
                                    </div>
                                </div>
                                {
                                    itemDetail.state !== 'exchange_success' &&
                                    <div className="info-row">
                                        <span className="label">发货时间：</span>
                                        <span className="text">{itemDetail.ship_time}</span>
                                    </div>
                                }
                            </div>
                        </div>
                        <div className="edit-confirm-btngroup">
                        {
                            itemDetail.state === 'exchange_success' ?
                            [<Button 
                                key="confirm" type="primary" 
                                onClick={this.onConfirm} className='left-btn'
                            >确认发货</Button>,
                            <Button key='cancel' onClick={this.onExchangeCancel}>取消</Button>] :
                            <Button type="primary" onClick={this.onExchangeCancel}>确认</Button>
                        }
                        </div>
                </Modal>
            </div>
        );
    }
}

export default ExchangeOrder;
