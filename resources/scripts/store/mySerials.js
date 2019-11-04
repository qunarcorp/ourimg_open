import { observable, action, runInAction } from 'mobx';
import { mySerialsApi } from 'CONST/api';

export default class MySerial {

    @observable dataList = [];

    @action.bound
    async getDataList(params, pathKey, callback) {
        const res = await $.get(mySerialsApi[pathKey], params);
        runInAction(() => {
            if(res.ret) {
              this.dataList = res.data;
              const eidArray = res.data.map(item => item.eid);
              callback(res.count, eidArray)
            } else {
              this.dataList = [];
            }
          })
    }

    @action.bound
    async deleteItem(params, pathKey, callback) {
      const res = await $.post(mySerialsApi[`${pathKey}Delete`], params);
      runInAction(() => {
        if(res.ret) {
          callback()
        }
      })

    }

}
