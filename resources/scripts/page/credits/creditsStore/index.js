import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { Button, Checkbox, Pagination, Radio } from 'antd';
import { goodsTypeMap } from 'CONST/map';
const RadioButton = Radio.Button;
const RadioGroup = Radio.Group;
const CheckboxGroup = Checkbox.Group;
const filterOptions = [
    { label: '仅看有货', value: 'only_in_stock' },
    { label: '我能兑换的', value: 'can_exchange' }
  ];
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    userInfo: state.store.global.userInfo,
    storeDataList: state.store.credits.storeDataList,
    storeTotal: state.store.credits.storeTotal,
    getGoodsList: state.store.credits.getGoodsList
}))

@withRouter
@observer
class CreditsStore extends Component {

    state = {
        creditsPage: {
            current: 1,
            pageSize: 12
        },
        sort: '',
        asc: true,
        filterArr: []
    };

    componentDidMount() {
        this.loadData();
    }

    loadData = () => {
        const { creditsPage, sort, asc, filterArr } = this.state;
        let params = {
            page: creditsPage.current,
            perPage: creditsPage.pageSize,
            orderBy: sort === 'sale_time_' || sort === 'points_' ? 
                sort + (asc ? 'asc' : 'desc') : sort,
            filterCondition: filterArr.join(',')
        };
        this.props.getGoodsList(params);
    }

    onSortChange = (e) => {
        this.setState({
            sort: e.target.value,
            asc: true
        }, this.loadData);
    }

    changeSort = (sort) => {
        if (sort === this.state.sort) {
            this.setState({
                asc: !this.state.asc
            }, this.loadData);
        }
    }

    onFilterChange = (values) => {
        this.setState({
            creditsPage: {
                current: 1,
                pageSize: this.state.creditsPage.pageSize
            },
            filterArr: values
        }, this.loadData);
    }

    onPageChange = (current, pageSize) => {
        this.setState({
            creditsPage: {
                current,
                pageSize
            }
        }, this.loadData);
    }

    getGoods = (id) => {
        window.open('/#/credits/goodsDetail?id=' + id);
    }

    getGoodsStatus = (credits, num, sale) => {
        if (!sale) {
            return 'SOLD_OUT';
        } else if (num <= 0) {
            return 'LACK_STOCK';
        } else if (parseInt(this.props.userInfo.points_info.current_points) < parseInt(credits)) {
            return 'LACK_CREDITS';
        } else {
            return 'AVALIABLE';
        }
    }

    render() {
        let { creditsPage, sort, filterArr, asc } = this.state;
        let { storeTotal, storeDataList } = this.props;
        return (
            <div className="credits-store-content">
                <div className="store-title">兑换专区</div>
                <div className="store-toolbar">
                    <div className="q-sort-tool">
                        <span>排序：</span>
                        <RadioGroup 
                            value={sort} size="small" 
                            onChange={this.onSortChange}>
                            <RadioButton value="">默认</RadioButton>
                            <RadioButton value="hot">热门</RadioButton>
                            <RadioButton value="sale_time_" onClick={() => this.changeSort('sale_time_')}>
                                上架时间
                                <i className={`icon-font-ourimg ${sort==='sale_time_' && asc ? 'select' : ''}`}>&#xe447;</i>
                                <i className={`icon-font-ourimg ${sort==='sale_time_' && !asc ? 'select' : ''}`}>&#xe443;</i>
                            </RadioButton>
                            <RadioButton value="points_" onClick={() => this.changeSort('points_')}>
                                积分
                                <i className={`icon-font-ourimg ${sort==='points_' && asc ? 'select' : ''}`}>&#xe447;</i>
                                <i className={`icon-font-ourimg ${sort==='points_' && !asc ? 'select' : ''}`}>&#xe443;</i>
                            </RadioButton>
                        </RadioGroup>
                    </div>
                    <div className="filter-tool">
                        <CheckboxGroup 
                            options={filterOptions} 
                            value={filterArr} 
                            onChange={this.onFilterChange} />
                    </div>
                </div>
                <div className="store-list">
                {
                    storeDataList.map(item => 
                        <div 
                            className="goods-card" key={item.eid} 
                            onClick={()=>this.getGoods(item.eid)}>
                            <img className="goods-img" src={item.master_img}/>
                            <div className="goods-name">{item.title}</div>
                            <div className="goods-info">
                                <div className="goods-credits">
                                    <span className="credits">{item.points}</span>积分
                                </div>
                                <div className="goods-price">
                                    参考售价：￥<span>{item.price}</span>
                                </div>
                            </div>
                            <Button 
                                className="link-btn" type="primary" 
                                disabled={
                                    this.getGoodsStatus(item.points, item.remain_stock, item.on_sale) !== 'AVALIABLE'}
                                onClick={()=>this.getGoods(item.eid)}>
                                {goodsTypeMap[this.getGoodsStatus(item.points, item.remain_stock, item.on_sale)]}
                            </Button>
                        </div>
                    )
                }
                </div>
                <Pagination 
                    className="credits-pagination"
                    hideOnSinglePage
                    total={storeTotal} 
                    current={creditsPage.current}
                    pageSize={creditsPage.pageSize}
                    onChange={this.onPageChange}
                    onShowSizeChange={(current, size)=>this.onPageChange(1, size)}
                    showQuickJumper />
            </div>
        );
    }
}

export default CreditsStore;
