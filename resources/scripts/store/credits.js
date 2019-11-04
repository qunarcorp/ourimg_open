import { observable, action, runInAction } from "mobx";
import { creditsApi } from "CONST/api";
import { message } from 'antd';

export default class credits {
    //积分明细
    @observable myCreditsRecord = [];
    @observable myCreditsTotal = 0;
    @observable myExchangeTotal = 0;
    @observable myExchangeRecord = [];
    //积分首页
    @observable dailyMission = {
        upload: {
            complete_state: 'undo',
            complete_num: 0
        },
        city: {
            complete_state: 'undo',
            city_arr: [],
            city_end_time: ''
        }
    };
    @observable storeDataList = [];
    @observable storeTotal = 0;
    @observable rankingBorad = [];

    //积分细则
    @observable ruleHint = '';
    @observable ruleInstruction = '';
    @observable ruleQAList =[];

    //商品详情
    @observable goodsInfo = {
        img_url: [],
        detail_img: []
    };

    @action.bound
    async getDailyMission() {
        const res = await $.get(creditsApi.dailyMission);
        runInAction(() => {
            if (res.status === 0) {
                let obj = {};
                res.task_info.map(item => obj[item.task_name] = item);
                this.dailyMission = obj;
            }
        });
    }

    @action.bound
    async getCreditsBoard() {
        const res = await $.get(creditsApi.creditsBorad);
        runInAction(() => {
            if (res.status === 0) {
                this.rankingBorad = res.points_board;
            } else {
                this.rankingBorad = [];
            }
        });
    }

    @action.bound
    async getCreditsRecord(params) {
        const res = await $.get(creditsApi.creditsRecord, params);
        runInAction(() => {
            if (res.status === 0) {
                this.myCreditsRecord = res.point_arr;
                this.myCreditsTotal = res.points_count;
            } else {
                this.myCreditsRecord = [];
                this.myCreditsTotal = 0;
            }
        });
    }

    @action.bound
    async getExchangeRecord(params) {
        const res = await $.get(creditsApi.exchangeRecord, params);
        runInAction(() => {
            if (res.status === 0) {
                this.myExchangeRecord = res.data.order_list;
                this.myExchangeTotal = res.data.order_count;
            } else {
                this.myExchangeRecord = [];
                this.myExchangeTotal = 0;
            }
        });
    }

    @action.bound
    async getGoodsList(params) {
        const res = await $.get(creditsApi.goodsList, params);
        runInAction(() => {
            if (res.status === 0) {
                this.storeDataList = res.data.data;
                this.storeTotal = res.data.total;
            } else {
                this.storeDataList = [];
                this.storeTotal = 0;
            }
        });
    }

    @action.bound
    async getCreditsRules() {
        const res = await $.get(creditsApi.creditsRules);
        runInAction(() => {
            if (res.status === 0) {
                const { point_obtain_rule, point_related_instructions, point_questions } = res.data.rules_info;
                this.ruleHint = point_obtain_rule;
                this.ruleInstruction = point_related_instructions;
                this.ruleQAList = point_questions;
            } else {
                this.ruleHint = '';
                this.ruleInstruction = '';
                this.ruleQAList = [];
            }
        });
    }

    @action.bound
    async getGoodsdetail(params, callback) {
        const res = await $.get(creditsApi.goodsDetail, params);
        runInAction(() => {
            if (res.status === 0) { 
                this.goodsInfo = res.data;
                callback && callback(res.data);
            } else {
                this.goodsInfo = {
                    img_url: [],
                    detail_img: []
                };
            }
        });
    }

    @action.bound
    async exchangeGoods(params, callback) {
        const res = await $.post(creditsApi.exchangeGoods, params, {handleError: false});
        runInAction(() => {
            callback && callback(res);
        });
    }
}
