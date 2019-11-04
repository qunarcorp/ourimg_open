import { observable, action, runInAction } from "mobx";

import { adminApi } from "CONST/api";
const {
    getActivityList,
    activityOffline,
    activityOnline,
    activityDetail,
    searchSuggest
} = adminApi;

const ON_OFF_API = {
    pending: activityOnline,
    online: activityOffline,
    offline: activityOnline,
    end: activityOffline
};

const INIT_SEARCH_PARAMS = {
    eid: "",
    activity_title: "",
    activity_type: "",
    state: ""
};
const INIT_EDIT_DATA = {
    img_upload_points: 2,
    task_points: 0,
    need_img_count: 0,
    // TODO mobx 无法追踪
    theme_keywords: [],
    city_sights: {}
}
export default class storeManage {
    // "MANAGE" "EDIT"
    @observable tabKey = "MANAGE";
    @observable editData = INIT_EDIT_DATA;
    // 活动列表,与editIndex配合可以显示详情
    @observable activityList = [];
    @observable serchParams = INIT_SEARCH_PARAMS;
    @observable page = {
        current: 1,
        pageSize: 10,
        total: 0
    };
    // 编辑的index, -1时为初始化或者新建
    @observable editIndex = -1;

    @action.bound
    onPageChange(current, pageSize) {
        this.page = {
            ...this.page,
            current,
            pageSize
        };
        this.getActivityList();
    }

    @action.bound
    changeFormDataCitySight(values) {
        const { key, label } = values;
        if(this.editData.city_sights.hasOwnProperty(key)) return;
        this.editData.city_sights = {
            ...this.editData.city_sights,
            [key]: label[1] ? `${label[0]} ${label[1]}` : label[0]
        }
        // this.editData.city_sights[key] = label[1] ? `${label[0]} (${label[1]})` : label[0];
    }

    @action.bound
    deleteCitySights(key) {
         if(key in this.editData.city_sights) {
            delete this.editData.city_sights[key];
            this.editData.city_sights = {
                ...this.editData.city_sights
            }
         }
    }

    @action.bound
    deleteFormDataArr(prop, val) {
        const propVal = this.editData[prop];
        const index = propVal.indexOf(val);
        if(index === -1) return;
        propVal.splice(index, 1);
    }

    @action.bound
    changeFormDataArr(prop, val, delBool) {
        let propVal = this.editData[prop];
        if (propVal.includes(val)) return;
        propVal = propVal.push(val);
    }

    @action.bound
    changeFormData(prop, val) {
        if (this.editData[prop] === val) return;
        this.editData = {
            ...this.editData,
            [prop]: val
        }
        console.log(this.editData);
    }

    @action.bound
    onChangeTab(tab) {
        this.editIndex = -1;
        // this.formData = {};
        this.tabKey = tab;
        this.editData = INIT_EDIT_DATA;
    }

    @action.bound
    editActivity(tab, index, activityItem) {
        this.onChangeTab(tab);
        this.editData = {...activityItem};
        this.editIndex = index;
    }

    @action.bound
    async getActivityList() {
        const { current, pageSize } = this.page;
        const res = await $.get(getActivityList, {
            ...this.serchParams,
            limit: pageSize,
            offset: (current - 1) * pageSize
        });
        runInAction(() => {
            const {
                status = 1,
                data: { total, list }
            } = res;
            if (status === 0) {
                this.activityList = list;
                this.page.total = total;
            }
        });
    }

    @action.bound
    async publish(eid, state, index) {
        const params = { eid };
        const res = await $.post(ON_OFF_API[state], params);
        runInAction(async () => {
            const { status = 1 } = res;
            if (status === 0) {
                const resDatil = await $.get(activityDetail, params);
                runInAction(() => {
                    if (resDatil.status === 0) {
                        this.activityList[index] = resDatil.data;
                    }
                });
            }
        });
    }

    @action.bound
    updateSearchParams(key, value) {
        this.serchParams[key] = value;
    }

    @action.bound
    clearSearchParams() {
        this.serchParams = INIT_SEARCH_PARAMS;
        this.getActivityList();
    }

    @action.bound
    onSearch() {
        this.page = {
            ...this.page,
            current: 1
        };
        this.getActivityList();
    }
}
