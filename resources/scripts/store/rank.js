import { observable, action, runInAction } from "mobx";
import { rankApi } from "CONST/api";

export default class Rank {
    @observable dataSource = [];
    @observable rankTab = "upload";
    @observable pagination = {
        showSizeChanger: true,
        showQuickJumper: true,
        current: 1,
        pageSize: 10,
        total: 0
    };
    @observable searchPage = 1;
    @observable searchPageSize = 20;
    @observable searchLastPage = -1;
    @observable searchDataLoading = false;
    @observable searchDataDone = false;
    @observable searchDataList = [];
    @observable imgDataLoading = false;

    @action.bound
    async getList() {
        let api = rankApi[this.rankTab + "Rank"]
        const { current, pageSize } = this.pagination;
        const res = await $.get(api, {page: current, pageSize: pageSize});
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.dataSource = list;
                this.pagination = {
                    ...this.pagination,
                    total: parseInt(total)
                }
            }
        });
    }

    @action.bound
    handleSwitchTab(rankTab) {
        this.rankTab = rankTab;
        this.query = '';
        this.pagination.current = 1
        this.getList();
    }


    @action.bound
    handlePage(pagination) {
        this.pagination = pagination;
        this.getList();
    }

    @action.bound
    getRankTab() {
        return this.rankTab;
    }

}
