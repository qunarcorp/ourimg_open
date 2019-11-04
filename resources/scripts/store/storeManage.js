import { observable, action, runInAction } from "mobx";
import { creditsApi, creditsManageApi } from "CONST/api";
import { message } from 'antd';
import moment from 'moment';

export default class storeManage {
    @observable tabKey = 'MANAGE';

    //商品管理
    @observable goodsTotal = 0;
    @observable goodsDataList = [];
    @observable goodsSuggestion = [];

    //兑换订单
    @observable orderTotal = 0;
    @observable orderDataList = [];
    @observable suggestionList = [];

    //商品发布
    @observable editItemEid = '';
    @observable formData = {
        title: '',
        description: '',
        img_url: [],
        small_img_url: [],
        hasLimitTime: false,
        exchange_begin_time: '',
        exchange_end_time: '',
        exchange_description: '',
        price: 0.01,
        points: 1,
        stock: 1,
        detail: '',
        remain_stock: 0,
        detail_title: '',
        detail_img: []
    };

    //规则录入
    @observable rule = '';
    @observable instruction = '';
    @observable qaList = [{question: '', answer: ''},{question: '', answer: ''}];

    @action.bound
    onChangeTab(tab) {
        this.editItemEid = '';
        this.formData = {
            title: '',
            description: '',
            img_url: [],
            small_img_url: [],
            hasLimitTime: false,
            exchange_begin_time: '',
            exchange_end_time: '',
            exchange_description: '',
            price: 0.01,
            points: 1,
            stock: 1,
            remain_stock: 0,
            detail: '',
            detail_title: '',
            detail_img: []
        };
        this.tabKey = tab;
    }

    @action.bound
    onEditItem(eid) {
        this.tabKey = 'PUBLISH';
        this.editItemEid = eid;
    }

    @action.bound
    onChangeFormData(key, value) {
        this.formData[key] = value;
    }

    @action.bound
    async getExchangeDetail(params) {
        const res = await $.get(creditsManageApi.exchangeDetail, params);
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                this.orderDataList = data.order_list;
                this.orderTotal = data.order_count;
                data.order_list.map((item, index) => this.getUserNumber(item.mobile, index))
            } else {
                this.orderDataList = [];
                this.orderTotal = 0;
            }
        });
    }

    @action.bound
    async getUserNumber(mobile, index) {
        const res = await $.post(creditsManageApi.getUserNumber, {mobile}, {handleError: false});
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                this.orderDataList[index].mobile = data.cecrypt_mobile;
            }
        });
    }

    @action.bound
    async getGoodsList(params) {
        const res = await $.get(creditsManageApi.goodsList, params);
        runInAction(() => {
            if (res.status === 0) {
                this.goodsDataList = res.data.data;
                this.goodsTotal = res.data.total;
            } else {
                this.goodsDataList = [];
                this.goodsTotal = 0;
            }
        });
    }

    @action.bound
    async changeOnSale(params, callback) {
        const res = await $.post(creditsManageApi.goodsOnSale, params);
        runInAction(() => {
            if (res.status === 0) {
                message.success(res.message);
                callback && callback();
            }
        });
    }

    @action.bound
    async changeOffSale(params, callback) {
        const res = await $.post(creditsManageApi.goodsOffSale, params);
        runInAction(() => {
            if (res.status === 0) {
                message.success(res.message);
                callback && callback();
            }
        });
    }

    @action.bound
    async shipGoods(params, callback) {
        const res = await $.post(creditsManageApi.shipGoods, params);
        runInAction(() => {
            if (res.status === 0) {
                message.success(res.message);
                callback && callback();
            }
        });
    }

    @action.bound
    async getOrderSuggestion(params) {
        const res = await $.get(creditsManageApi.orderSuggestion, params);
        runInAction(() => {
            if (res.status === 0) {
                this.suggestionList = res.data.query_arr;
            }
        });
    }

    @action.bound
    async getGoodsSuggestion(params) {
        const res = await $.get(creditsManageApi.goodsSuggestion, params);
        runInAction(() => {
            if (res.status === 0) {
                this.goodsSuggestion = res.data;
            }
        });
    }

    @action.bound
    async getCreditsRules() {
        const res = await $.get(creditsApi.creditsRules);
        runInAction(() => {
            if (res.status === 0) {
                const { point_obtain_rule, point_related_instructions, point_questions } = res.data.rules_info;
                this.rule = point_obtain_rule;
                this.instruction = point_related_instructions;
                this.qaList = point_questions;
            } else {
                this.rule = '';
                this.instruction = '';
                this.qaList = [];
            }
        });
    }

    @action.bound
    async saveEditRules(params) {
        const res = await $.post(creditsManageApi.editRules, params);
        runInAction(() => {
            if (res.status === 0) {
                message.success('保存成功！');
            }
        });
    }

    @action.bound
    addNewQuestion = () => {
        this.qaList.push({question: '', answer: ''});
    }
    @action.bound
    delQuestion = (index) => {
        this.qaList.splice(index, 1);
    }
    @action.bound
    changeRule = (key ,value) => {
        this[key] = value;
    }
    @action.bound
    changeQaList = (index, key ,value) => {
        this.qaList[index][key] = value;
    }

    @action.bound
    async getGoodsdetail(params, callback) {
        const res = await $.get(creditsApi.goodsDetail, params);
        runInAction(() => {
            if (res.status === 0) { 
                const { title, description, img_url, exchange_begin_time, exchange_end_time,
                    exchange_description, price, points, stock, detail, detail_title,
                    detail_img, remain_stock  } = res.data;
                this.formData = {
                    title,
                    description,
                    img_url: img_url.map(item => item.original),
                    small_img_url: img_url.map(item => item.small),
                    hasLimitTime: !!(exchange_begin_time && exchange_end_time),
                    exchange_begin_time,
                    exchange_end_time,
                    exchange_description,
                    price,
                    points,
                    stock,
                    remain_stock,
                    detail,
                    detail_title,
                    detail_img
                };
                callback && callback(res.data);
            } else {
                this.formData = {
                    title: '',
                    description: '',
                    img_url: [],
                    small_img_url: [],
                    hasLimitTime: false,
                    exchange_begin_time: '',
                    exchange_end_time: '',
                    exchange_description: '',
                    price: 0.01,
                    points: 1,
                    stock: 1,
                    detail: '',
                    detail_title: '',
                    detail_img: []
                };
            }
        });
    }
    
    @action.bound
    async addNewGoods(params, callback) {
        let url = creditsManageApi.addNewGoods;
        if (this.editItemEid) {
            params = {...params, eid: this.editItemEid};
            url = creditsManageApi.editGoods;
        }
        const res = await $.post(url, params);
        runInAction(() => {
            if (res.status === 0) {
                message.success(res.message);
                callback && callback();
            }
        });
    }

}
