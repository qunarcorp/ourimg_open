import { observable, action, runInAction } from 'mobx';
import { myMessageApi, myUploadApi, componentApi, adminApi } from 'CONST/api';

export default class ImgAudit {
    @observable auditCount = {
        "pending_count": 0,//待审核
        "passed_count": 0,//已通过
        "reject_count": 0,//未通过
        "remove_count": 0,//已下架
        "system_reject_count": 0//系统驳回
    };
    @observable imgDataList = [];
    @observable imgDataCheckList = [];
    @observable totalCount = 0;
    @observable imgDataLoading = false;
    @observable cityList = [];
    @observable checkAllStatus = 0; //0: 全部未选中，1: 全部选中， 2: 部分选中
    @observable timelineLog = [];

    @action.bound
    async getDataList(params) {
        this.imgDataLoading = true;
        let url = params.audit_state == 5 ? adminApi.auditSystemReject : adminApi.getAuditList;
        const res = await $.get(url, params);
        runInAction(() => {
            this.imgDataLoading = false;
            if(res.status == 0 || res.ret) {
                this.imgDataList = res.data;
                this.totalCount = parseInt(res.count);
                this.imgDataCheckList = [];
                this.checkAllStatus = 0;
                res.data.map(item => {
                    this.imgDataCheckList.push({
                        edit: false,
                        check: false,
                        ready: false
                    });
                });
            } else {
                this.imgDataList = [];
                this.dataList = [];
                this.totalCount = 0;
            }
          })
    }

    @action.bound
    async getAuditCount() {
        const res = await $.get(adminApi.getAuditCount);
        runInAction(() => {
            if(res.ret || res.status === 0) {
              this.auditCount = res.data;
            } else {
              this.auditCount = {
                "pending_count": 0,//待审核
                "passed_count": 0,//已通过
                "reject_count": 0,//未通过
                "remove_count": 0//已下架
              };
            }
        })
    }

    @action.bound
    async getTimelineData(params) {
        this.timelineLog = [];
        const res = await $.get(adminApi.getAuditTrace, params);
        runInAction(() => {
            if(res.status == 0 || res.ret) {
                this.timelineLog = res.data;
            }
          })
    }

    @action.bound
    async setAuditPass(params, callback) {
        const res = await $.post(adminApi.auditPass, params);
        runInAction(() => {
            callback && callback(res);
        })
    }

    @action.bound
    async setAuditReject(params, callback) {
        const res = await $.post(adminApi.auditReject, params);
        runInAction(() => {
            callback && callback(res);
        })
    }

    @action.bound
    async setAuditBatchReject(params, callback) {
        const res = await $.post(adminApi.auditBatchReject, params);
        runInAction(() => {
            callback && callback(res);
        })
    }

    @action.bound
    async setAuditDel(params, callback) {
        const res = await $.post(adminApi.auditDel, params);
        runInAction(() => {
            callback && callback(res);
        })
    }

    @action.bound
    async setAuditSuperDel(params, callback) {
        let url = params.type === 'visit' ? adminApi.auditDel : adminApi.auditRealDel;
        let data = params.type === 'visit' ? {eid: params.eids} : {eids: params.eids};
        const res = await $.post(url, data);
        runInAction(() => {
            callback && callback(res);
        })
    }

    @action.bound
    changeCheckStatus(index) {
        let list = this.imgDataCheckList.slice();
        list[index].check = ! list[index].check;
        this.imgDataCheckList = list;
        let defaultValue = this.imgDataCheckList[0].check;
        for (let i = 1; i < this.imgDataCheckList.length; i++) {
            if (this.imgDataCheckList[i].check !== defaultValue) {
                // 未全部勾选状态
                defaultValue = '未全部勾选状态'
                break;
            }
        }
        switch (defaultValue) {
            case true:
                this.checkAllStatus = 1;
                break;
            case false:
                this.checkAllStatus = 0;
                break;
            default:
                this.checkAllStatus = 2;
                break;
        }
    }

    @action.bound
    checkAll() {
        let list = this.imgDataCheckList.slice();
        if (this.checkAllStatus == 1) {
            list = list.map(item => {
                return {
                    ...item,
                    check: false
                }
            })
            this.checkAllStatus = 0
        } else {
            list = list.map(item => {
                return {
                    ...item,
                    check: true
                }
            })
            this.checkAllStatus = 1
        }
        this.imgDataCheckList = list;
    }

    @action.bound
    getCheckedIndexList() {
        let list = [];
        this.imgDataCheckList.map((item, index) => {
            if (item.check) {
                list.push(index);
            }
        })
        return list;
    }

    @action.bound
    async getCitySuggest(params, callback) {
        $.getCross(componentApi.citySuggest + params, false,(err, res) => {
            if (res.ret) {
                this.cityList = res.data;
                callback && callback();
            }
        })
    }

    @action.bound
    async imgStar(eid, callback) {
        const res = await $.post(adminApi.star, {eid});
        runInAction(() => {
            callback && callback(res);
        })
    }

    @action.bound
    async imgUnStar(eid, callback) {
        const res = await $.post(adminApi.unstar, {eid});
        runInAction(() => {
            callback && callback(res);
        })
    }
}
