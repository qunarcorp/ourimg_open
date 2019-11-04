import { observable, action, runInAction } from 'mobx';
import { myMessageApi } from 'CONST/api';

export default class MyMessage {
    @observable dataList = [];
    @observable totalCount = 0;

    @action.bound
    async getDataList(params) {
        const res = await $.get(myMessageApi.getMessageList, params);
        runInAction(() => {
            if(res.status == 0) {
              this.dataList = res.data.list;
              this.totalCount = res.data.count;
            } else {
              this.dataList = [];
              this.totalCount = 0;
            }
          })
    }

    @action.bound
    async setMsgRead(params, callback) {
        const res = await $.get(myMessageApi.setRead, params);
        runInAction(() => {
            if(res.status == 0) {
                callback && callback();
            }
          })
    }
}