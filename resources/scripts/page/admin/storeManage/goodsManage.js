import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Button, Pagination, Input, Row, Col, Select, AutoComplete } from 'antd';
import { goodsManageTypeMap } from 'CONST/map';
const Search = Input.Search;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    goodsTotal: state.store.storeManage.goodsTotal,
    goodsDataList: state.store.storeManage.goodsDataList,
    getGoodsList: state.store.storeManage.getGoodsList,
    changeOnSale: state.store.storeManage.changeOnSale,
    changeOffSale: state.store.storeManage.changeOffSale,
    onEditItem: state.store.storeManage.onEditItem,
    goodsSuggestion: state.store.storeManage.goodsSuggestion,
    getGoodsSuggestion: state.store.storeManage.getGoodsSuggestion
}))
@withRouter
@observer
class GoodsManage extends Component {

    state = {
        page: {
            current: 1,
            pageSize: 10
        },
        status: '',
        query: ''
    };

    componentDidMount() {
        this.loadData();
    }

    loadData = () => {
        const { page, status, query } = this.state;
        let params = {
            page: page.current,
            perPage: page.pageSize,
            searchQuery: query,
            saleStatus: status
        };
        this.props.getGoodsList(params);
    }
    selectChange = (value) => {
        this.setState({
            page: {
                current: 1,
                pageSize: this.state.page.pageSize
            },
            status: value
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
    onPageChange = (current, pageSize) => {
        this.setState({
            page: {
                current,
                pageSize
            }
        }, this.loadData);
    }

    changeSaleStatus = (item) => {
        if (item.goods_status === '未上架' || item.goods_status === '已下架') {
            this.props.changeOnSale({eid: item.eid}, this.loadData);
        } else {
            this.props.changeOffSale({eid: item.eid}, this.loadData);
        }
    }
    goToEdit = (eid) => {
        this.props.onEditItem(eid);
    }
    goToDetail = (eid) => {
        this.props.history.push('/credits/goodsDetail?id=' + eid);
    }
    render() {
        let { page, query, status } = this.state;
        let { goodsTotal, goodsDataList, goodsSuggestion, getGoodsSuggestion } = this.props;
        return (
            <div className="goods-manage-panel">
                <div className="toole-bar">
                    商品状态
                    <Select 
                        className="select-search" value={status} 
                        onChange={this.selectChange}>
                        <Option value="">全部</Option>
                        {
                            Object.keys(goodsManageTypeMap).map(key =>
                                <Option key={key} value={key}>{goodsManageTypeMap[key]}</Option>
                            )
                        }
                    </Select>
                    <AutoComplete
                        className="input-search"
                        onChange={this.queryChange}
                        dataSource={goodsSuggestion}
                        onSelect={this.queryChange}
                        onSearch={(value) => getGoodsSuggestion({query: value})}
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
                    goodsDataList.map(item => 
                        <div className="goods-item" key={item.eid}>
                            <Row className="base-info">
                                <Col className="info" span={4}>
                                    商品ID：{item.eid}
                                </Col>
                                <Col className="info" span={5}>
                                {
                                    item.sale_time ? '上架时间：' + item.sale_time : ''
                                }
                                </Col>
                                <Col className="info" span={5}>
                                {
                                    item.update_time ? '更新时间：' + item.update_time : ''
                                }
                                </Col>
                            </Row>
                            <Row className="detail-info">
                                <Col className="info" span={13}>
                                    <div className="item-info">
                                        <img className="item-img" src={item.master_img}/>
                                        <div className="item-detail">
                                            <div className="item-name">{item.title}</div>
                                            <div className="item-desc">库存：{item.remain_stock}/{item.stock}</div>
                                            <div className="item-desc">参考价：￥{item.price}</div>
                                        </div>
                                    </div>
                                </Col>
                                <Col className="info" span={4}>
                                    {item.goods_status}
                                </Col>
                                <Col className="info" span={4}>
                                    {item.points}积分/个
                                </Col>
                                <Col className="info" span={3}>
                                    <div className="primary-btn" onClick={()=>this.changeSaleStatus(item)}>{
                                        item.goods_status === '未上架' || item.goods_status === '已下架'
                                        ? '上架' : '下架'
                                    }</div>
                                    <div 
                                        className="text-btn" 
                                        onClick={()=>this.goToEdit(item.eid)}
                                    >编辑</div>
                                    <div 
                                        className="text-btn" 
                                        onClick={()=>this.goToDetail(item.eid)}
                                    >查看</div>
                                </Col>
                            </Row>
                        </div>
                    )
                }
                </div>
                <Pagination 
                    className="credits-pagination"
                    hideOnSinglePage
                    total={goodsTotal} 
                    current={page.current}
                    pageSize={page.pageSize}
                    onChange={this.onPageChange}
                    onShowSizeChange={(current, size)=>this.onPageChange(1, size)}
                    showSizeChanger showQuickJumper />
            </div>
        );
    }
}

export default GoodsManage;
