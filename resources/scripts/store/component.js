import { observable, action, runInAction } from 'mobx';
import { componentApi } from 'CONST/api';
import { message } from 'antd';

export default class Component {
    @observable imgLink = '';
    @observable imgData = {};
    @observable imgDataList = [];
    @observable imgDataCheckList = [];
    @observable bulkEditList = [];
    @observable batchEditParams = {
        setData: false,
        params: {}
    };
    @observable batchDataList = [];
    @observable batchPrepareNum = 0;
    @observable duplicateImg = [];
    @observable cityList = [];
    @observable checkAllStatus = 0; //0: 全部未选中，1: 全部选中， 2: 部分选中

    @action.bound
    initDuplicateImg() {
        this.duplicateImg = [];
    }

    @action.bound
    resetBatchPrepareNum() {
        this.batchPrepareNum = 0;
    }

    @action.bound
    async uploadImg(params, flag, callback) {
        $.postFormData(componentApi.upload, params).then(res => {
            let responseData = res.data;

            if (res.status === 0) {
                responseData = {
                    upload_source: '',
                    purchase_source: '',
                    original_author: '',
                    is_signature: '',
                    upload_source_type: 'personal',
                    ... responseData
                }

                if (!flag) { //非批量上传
                    this.imgData = responseData;
                } else {
                    this.imgDataCheckList.push({
                        edit: false,
                        check: false,
                        ready: false
                    });
                    this.bulkEditList.push({})
                    this.imgDataList.push(responseData);
                }
            } else if(res.status === 108 || res.status === 103){
                responseData = responseData.map(item => {
                    return {
                        upload_source: '',
                        purchase_source: '',
                        original_author: '',
                        is_signature: '',
                        upload_source_type: 'personal',
                        ... item
                    };
                })

                this.duplicateImg.push(...responseData);
            }
            callback(res.status)
        })
    }

    @action.bound
    resetImgData() {
        this.imgData = {};
        this.imgDataList = [];
        this.imgDataCheckList = [];
        this.bulkEditList = [];
        this.checkAllStatus = 0;
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
        $.get(componentApi.uploadDel, {eids: eidsArr.join(',')}).then(res => {
            if (res.status === 0) {
                message.success(res.message || '操作成功');
                let list1 = this.imgDataList.slice();
                let list2 = this.imgDataCheckList.slice();
                let list3 = this.bulkEditList.slice();
                indexArr.reverse()
                indexArr.map(item => {
                    list1.splice(item, 1);
                    list2.splice(item, 1);
                    list3.splice(item, 1);
                })
                this.imgDataList = list1;
                this.imgDataCheckList = list2;
                this.bulkEditList = list3;
            } else {
                message.error(res.message || '操作失败');
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

        let imgDataList = this.imgDataList
        let batchEditParams = this.batchEditParams

        indexArr.map(item => {
            params.imgs.push({
                eid: imgDataList[item].eid,
                ... this.bulkEditList[item]
            });
        })

        $.post(componentApi.edits, params).then(res => {
            if (res.status === 0) {
                message.success(`${indexArr.length}个素材上传完成，棒棒哒！`);
                setTimeout(()=>callback(res), 200);
            } else if (res.status === 108) {
                // let failItem = this.bulkEditList.filter(item => item.eid === res.data.fail[0]);
                message.warning(res.message);
                let delArr = [];
                this.imgDataList.map((item, index) => {
                    res.data.ok.indexOf(item.eid) !== -1 && (delArr.push(index));
                });
                let list1 = this.imgDataList.slice();
                let list2 = this.imgDataCheckList.slice();
                let list3 = this.bulkEditList.slice();
                delArr.reverse()
                delArr.map(item => {
                    list1.splice(item, 1);
                    list2.splice(item, 1);
                    list3.splice(item, 1);
                })
                this.imgDataList = list1;
                this.imgDataCheckList = list2;
                this.bulkEditList = list3;
                callback(res);
            } else {
                message.error(res.message || '操作失败')
            }
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
    async editImg(params, callback) {
        // 编辑单张图片
        $.post(componentApi.edit, params).then(res => {
            if (res.status === 0) {
                callback();
            }
        })
    }

    @action.bound
    async delImg(params, callback) {
        $.post(componentApi.del, params).then(res => {
            if (res.status === 0 || res.ret) {
                message.success('删除成功');
                setTimeout(callback, 1000)
            }
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
    setImgDataList(imgDataList) {
        this.imgDataList = imgDataList;
    }

    @action.bound
    batchEditData(arr, params) {
        arr.map(index => {
            this.imgDataCheckList[index].ready = true;
            this.imgDataCheckList[index].edit = false;
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
    moveBatchImg(index) {
        let list1 = this.imgDataList.slice();
        let list2 = this.imgDataCheckList.slice();
        let list3 = this.bulkEditList.slice();
        list1.splice(index, 1);
        list2.splice(index, 1);
        list3.splice(index, 1);
        this.imgDataList = list1;
        this.imgDataCheckList = list2;
        this.bulkEditList = list3;
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
