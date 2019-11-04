import { observable, action, runInAction } from "mobx";
import { statisticApi } from "CONST/api";

export default class Statistics {
    defaultPageSize = 10;
    @observable tabKey = 'USER_IMG';
    @observable smallTabKey = '';
    @observable sortField = '';
    @observable sortOrder = '';
    @observable userImgDataSource = [];
    @observable placeImgNumData = [];
    @observable userPointsDataSource = [];
    @observable userInteractionDataSource = [];
    @observable auditUserListDataSource = [];
    @observable userStatisticsDataSource = [];
    @observable pointsStatistic = {
        total_points: 0,
        pass_points: 0,
        praise_points: 0,
        favorite_points: 0,
        task_points: 0,
        star_points: 0,
        current_points: 0,
        delete_points: 0,
        exchange_points: 0,
    }

    @observable imgStatusStatistic = {
        all_img_num: 0,
        del_num: 0,
        to_submit_num: 0,
        check_pending_num: 0,
        pass_num: 0,
        reject_num: 0
    };

    @observable placeImgStatistic = {
        city: 0,
        custom: 0,
        poi: 0,
        province: 0,
        country: 0,
        county: 0
    };

    @observable interactionNumStatistic = {
        total_browse_num: 0,
        total_download_num: 0,
        total_praise_num: 0,
        total_favorite_num: 0,
    };

    @observable auditStatistics = {
        total_audit_num: 0,
        total_pass_num: 0,
        total_reject_num: 0,
        total_week_audit_num: 0,
        total_week_pass_num: 0,
        total_week_reject_num: 0,
    };

    @observable userStatisticsNum = {
        upload_user_num: 0,
        visit_user_num: 0,
    };

    @observable pagination = {
        showSizeChanger: true,
        showQuickJumper: true,
        current: 1,
        pageSize: this.defaultPageSize,
        total: 0
    };

    @observable query = '';
    @observable searchQuery = '';

    @action.bound
    onChangeTab(tab) {
        this.tabKey = tab;
        this.pagination = {
            ... this.pagination,
            current: 1,
            pageSize: this.defaultPageSize,
            total: 0
        }
        this.smallTabKey = '';
        this.sortField = '';
        this.sortOrder = '';
        this.resetSearchQuery()
    }

    @action.bound
    async setQueryParams(tabType, sortField, sortOrder) {
        this.smallTabKey = tabType;
        this.sortField = sortField;
        this.sortOrder = sortOrder;
    }

    @action.bound
    async getPlaceImgStatistic() {
        let api = statisticApi['placeImgStatistic']
        const res = await $.get(api);
        this.placeImgStatistic = res.data
    }

    @action.bound
    async getImgStatus() {
        let api = statisticApi['imgStatus']
        const res = await $.get(api);
        this.imgStatusStatistic = res.data
    }

    @action.bound
    async getInteractionNumStatistic() {
        let api = statisticApi['interactionNum']
        const res = await $.get(api);
        this.interactionNumStatistic = res.data
    }

    @action.bound
    async getAuditStatistics() {
        let api = statisticApi['auditStatistics']
        const res = await $.get(api);
        this.auditStatistics = res.data
    }

    @action.bound
    async getUserStatisticsNum() {
        let api = statisticApi['userStatisticsNum']
        const res = await $.get(api);
        this.userStatisticsNum = res.data
    }

    @action.bound
    async getUserImgList() {
        let api = statisticApi['userList']
        const { current, pageSize } = this.pagination;
        let params = {page: current, pageSize: pageSize}

        if (this.smallTabKey) {
            params['tabType'] = this.smallTabKey;
        }

        if (this.searchQuery) {
            params['query'] = this.searchQuery;
        }

        if (this.sortField && this.sortOrder) {
            params['sortField'] = this.sortField;
            params['sortOrder'] = this.sortOrder;
        }
        const res = await $.get(api, params);
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.userImgDataSource = list;
                this.pagination = {
                    ...this.pagination,
                    total: parseInt(total)
                }
            }
        });
    }

    @action.bound
    async getPlaceImgNum(placeTab, pid) {
        let api = statisticApi['placeImgNum']
        const { current, pageSize } = this.pagination;
        const res = await $.get(api, {
            pid: pid,
            level: placeTab,
            page: current,
            pageSize: pageSize
        });

        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.placeImgNumData = list;
                this.pagination = {
                    ...this.pagination,
                    total: parseInt(total)
                }
            }
        });
    }

    @action.bound
    async getPointsStatistic() {
        let api = statisticApi['points']
        const res = await $.get(api);
        this.pointsStatistic = res.data
    }

    @action.bound
    async getUserPointsDataSource() {
        let api = statisticApi['userPointList']
        const { current, pageSize } = this.pagination;
        let params = {page: current, pageSize: pageSize}
        if (this.smallTabKey) {
            params['tabType'] = this.smallTabKey;
        }

        if (this.searchQuery) {
            params['query'] = this.searchQuery;
        }

        if (this.sortField && this.sortOrder) {
            params['sortField'] = this.sortField;
            params['sortOrder'] = this.sortOrder;
        }
        const res = await $.get(api, params);
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.userPointsDataSource = list;
                this.pagination = {
                    ...this.pagination,
                    total: parseInt(total)
                }
            }
        });
    }

    @action.bound
    async getUserInteractionDataSource(tabType, sortField, sortOrder) {
        let api = statisticApi['userInteraction']
        const { current, pageSize } = this.pagination;
        let params = {page: current, pageSize: pageSize}
        if (this.smallTabKey) {
            params['tabType'] = this.smallTabKey;
        }

        if (this.searchQuery) {
            params['query'] = this.searchQuery;
        }

        if (this.sortField && this.sortOrder) {
            params['sortField'] = this.sortField;
            params['sortOrder'] = this.sortOrder;
        }

        const res = await $.get(api, params);
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.userInteractionDataSource = list;
                this.pagination = {
                    ...this.pagination,
                    total: parseInt(total)
                }
            }
        });
    }

    @action.bound
    async getUserStatisticsDataSource() {
      let api = statisticApi['userStatistics']
      const { current, pageSize } = this.pagination;
      let params = {page: current, pageSize: pageSize}

      if (this.smallTabKey) {
          params['tabType'] = this.smallTabKey;
      }

      if (this.searchQuery) {
          params['query'] = this.searchQuery;
      }

      if (this.sortField && this.sortOrder) {
          params['sortField'] = this.sortField;
          params['sortOrder'] = this.sortOrder;
      }

      const res = await $.get(api, params);
      runInAction(() => {
          const { status = 1, data = {} } = res;
          if (status === 0) {
              const { list, total } = data;
              this.userStatisticsDataSource = list;
              this.pagination = {
                  ...this.pagination,
                  total: parseInt(total)
              }
          }
      });
    }

    @action.bound
    async getAuditUserListDataSource() {
        let api = statisticApi['auditUserList']
        const { current, pageSize } = this.pagination;
        let params = {page: current, pageSize: pageSize}
        if (this.smallTabKey) {
            params['tabType'] = this.smallTabKey;
        }

        if (this.searchQuery) {
            params['query'] = this.searchQuery;
        }

        if (this.sortField && this.sortOrder) {
            params['sortField'] = this.sortField;
            params['sortOrder'] = this.sortOrder;
        }

        const res = await $.get(api, params);
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.auditUserListDataSource = list;
                this.pagination = {
                    ...this.pagination,
                    total: parseInt(total)
                }
            }
        });
    }

    @action.bound
    handleSearch(query) {
        this.searchQuery = query;
        this.pagination = {
            ...this.pagination,
            current: 1
        }

        if (this.tabKey == 'USER_IMG') {
            this.getUserImgList()
        }else if (this.tabKey == 'POINTS') {
            this.getUserPointsDataSource()
        }else if (this.tabKey == 'INTERACTION') {
            this.getUserInteractionDataSource()
        }else if (this.tabKey == 'AUDIT') {
            this.getAuditUserListDataSource()
        }else if (this.tabKey == 'USER_STATISTICS') {
            this.getUserStatisticsDataSource()
        }
    }

    @action.bound
    handleSearchValueChange(e) {
        this.query = e.target.value;
    }

    @action.bound
    resetSearchQuery() {
        this.query = '';
        this.searchQuery = '';
    }
}
