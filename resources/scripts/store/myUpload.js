import { observable, action, runInAction } from 'mobx';
import { myUploadApi, componentApi, authManageApi } from 'CONST/api';
import { message } from 'antd';

export default class MyUpload {
    @observable uploadsCount = {
        "draft_box": "0",//草稿箱
        "under_review": "0",//审核中
        "passed": "0",//已通过
        "not_pass": "0"//未通过
    };
    @observable totalCount = 0;
    @observable imgDataLoading = false;
    @observable imgDataList = [];
    @observable imgDataCheckList = [];
    @observable bulkEditList = [];
    @observable batchEditParams = {
        setData: false,
        params: {}
    };
    @observable batchDataList = [];
    @observable batchPrepareNum = 0;
    @observable batchOkList = [];
    @observable resetCard = false;
    @observable baseDept = [];
    @observable cityList = [];
    @observable checkAllStatus = 0; //0: 全部未选中，1: 全部选中， 2: 部分选中

    @action.bound
    async getBaseDept() {
        const res = await $.get(authManageApi.getBaseCompanyDept);
        runInAction(() => {
            if(res.status == 0) {
              this.baseDept = res.data;
            }
        })
    }


    @action.bound
    async getUploadCount(callback) {
        const res = await $.get(myUploadApi.myUploadsCount);
        runInAction(() => {
            if(res.ret) {
              this.uploadsCount = res.data;
              callback && callback(res.data);
            } else {
              this.uploadsCount = {
                "draft_box": "0",//草稿箱
                "under_review": "0",//审核中
                "passed": "0",//已通过
                "not_pass": "0"//未通过
              };
            }
        })
    }

    @action.bound
    async getUploadImgs(params) {
        this.imgDataLoading = true;
        const res = await $.get(myUploadApi.myUploadsImgs, params);
        runInAction(() => {
            this.imgDataLoading = false;
            this.resetCard = !this.resetCard;
            if(res.ret) {
                this.imgDataList = res.data;
                this.totalCount = parseInt(res.count);
                this.imgDataCheckList = [];
                this.bulkEditList = [];
                this.checkAllStatus = 0;
                res.data.map(item => {
                    this.imgDataCheckList.push({
                        edit: false,
                        check: false,
                        ready: false
                    });
                    this.bulkEditList.push({});
                });
            } else {
                this.imgDataList = [];
                this.totalCount = 0;
                this.imgDataCheckList = [];
                this.bulkEditList = [];
            }
        })
    }

    @action.bound
    changeCheckStatus(index, value) {
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
    setReadyStatus(index) {
        this.imgDataCheckList[index].ready = true;
    }

    @action.bound
    setEditStatus(index, value, obj) {
        this.imgDataCheckList[index].edit = value;
        this.bulkEditList[index] = obj;
    }

    @action.bound
    async uploadDel(indexArr, callback) {
        let eidsArr = indexArr.map(i => this.imgDataList[i].eid);
        $.post(myUploadApi.delUploadImg, {eid: eidsArr.join(',')}).then(res => {
            if (res.ret || res.status === 0) {
                message.success(res.message || '删除成功');
            } else {
                message.error(res.message || '删除失败');
            }
            callback();
        })
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
    async editImgs(indexArr, callback) {
        // 批量编辑图片
        let params = {
            action: 'edit',
            imgs: []
        }

        let bulkEditList = this.bulkEditList.slice()

        indexArr.map(item => {
            params.imgs.push(bulkEditList[item]);
        })
        $.post(componentApi.edits, params).then(res => {
            if (res.status === 0) {
                message.success(res.message || '操作成功');
            } else {
                message.error(res.message || '操作失败');
            }
            callback();
        })
    }

    @action.bound
    async saveImg(obj, callback) {
        // 图片保存
        let params = {
            action: 'edit',
            imgs: Array.isArray(obj) ? obj : [obj],
            operate_type: 'save'
        }
        $.post(componentApi.edits, params).then(res => {
            if (res.status === 0) {

            } else if (res.status === 108) {
                message.error(res.message || '操作失败');
            }
            callback(res);
        })
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
    modifyImg(params, callback) {
        this.imgData = params;
        callback();
    }

    @action.bound
    resetBatchEditParams() {
        this.batchEditParams = {
            setData: false,
            params: {}
        };
    }

    @action.bound
    batchEditData(arr, params) {
        arr.map(index => {
            this.imgDataCheckList[index].ready = true;
            this.imgDataCheckList[index].edit = false;
            // this.imgDataCheckList[index].check = false;
        });
        this.batchEditParams = {
            setData: true,
            params
        };
    }

    @action.bound
    onEditStatus(arr) {
        arr.map(index => {
            this.imgDataCheckList[index].ready = false;
            this.imgDataCheckList[index].edit = true;
        });
    }

    @action.bound
    async changeImgStatus(params, callback) {
        $.get(myUploadApi.changeImgStatus, params).then(res => {
            if (res.status === 0 || res.ret) {
                callback && callback();
            }
        })
    }

    @action.bound
    prepareBatchData({editObj, checkObj, index}) {
        this.batchPrepareNum ++;
        if (checkObj) {
            let exist = this.batchDataList.filter(obj => {
                return obj.eid == editObj.eid;
            })
            if (! exist.length) {
                this.batchDataList.push(editObj);
            }else{
                this.batchDataList.map(obj => {
                    if (obj.eid == editObj.eid) {
                        return editObj
                    }
                    return obj;
                });
            }

            this.setEditStatus(index, checkObj, editObj);
        }
        let arr = this.getCheckedIndexList();

        if (this.batchPrepareNum === arr.length && this.batchDataList.length > 0) {
            $.post(componentApi.edits, {
                action: 'edit',
                operate_type: 'save',
                imgs: this.batchDataList
            }).then(res => {
                if (res.status !== 0) {
                    message.warning(res.message);
                }

                this.batchOkList = res.data.ok;
                let imgDataList = this.imgDataList.slice()
                for(let currentIndex in imgDataList){
                    if (this.batchOkList.indexOf(imgDataList[currentIndex].eid) !== -1) {
                        this.imgDataCheckList[currentIndex].ready = true;
                        this.imgDataCheckList[currentIndex].edit = false;
                    }
                }
                this.batchPrepareNum = 0;
                this.batchDataList = [];
                
            })
        }
    }
}
