import { observable, action, runInAction } from "mobx";
import { authManageApi } from "CONST/api";
import { message } from 'antd';

export default class authManage {
    @observable dataSource = [];
    @observable role = "admin";
    @observable pagination = {
        showSizeChanger: true,
        showQuickJumper: true,
        // hideOnSinglePage: true,
        current: 1,
        pageSize: 12
    };
    @observable query = "";
    //添加管理员
    @observable userQuery = "";
    @observable treeData = [
        {
            "id": "0",
            "dept_name": "Qunarstaff",
            "dept": "Qunarstaff",
            "employee_num": "0",
            "manager_num": "0"
        }
    ];
    @observable searchPage = 1;
    @observable searchPageSize = 20;
    @observable searchLastPage = -1;
    @observable searchDataLoading = false;
    @observable searchDataDone = false;
    @observable searchDataList = [];
    @observable selectedUser = {};

    @action.bound
    async getList() {
        const { current, pageSize } = this.pagination;
        const params = {
            pageSize,
            role: this.role,
            query: this.query,
            current: current
        };
        const res = await $.get(authManageApi.getManageList, params);
        runInAction(() => {
            const { status = 1, data = {} } = res;
            if (status === 0) {
                const { list, total } = data;
                this.dataSource = list.map((item) => {
                    return {
                        ... item,
                        checked: false
                    }
                });
                this.pagination = {
                    ...this.pagination,
                    total
                }
            }
        });
    }

    @action.bound
    async handleDelete(params, callback) {
        params = {
            ...params,
            role: this.role
        }
        const res = await $.post(authManageApi.remove_power, params);
        runInAction(() => {
            if (res.status === 0) {
                message.success(res.message || '删除成功');
                this.getList();
                callback && callback();
            }
        });
    }

    @action.bound
    handleSwitchTab(role) {
        this.role = role;
        this.query = '';
        this.getList();
    }

    @action.bound
    handleSearch(query) {
        this.query = query;
        this.getList();
    }

    @action.bound
    handleSearchValueChange(e) {
        this.query = e.target.value;
    }

    @action.bound
    handlePage(pagination) {
        this.pagination = pagination;
        this.getList();
    }

    @action.bound
    changeValue(key, value) {
        this[key] = value;
    }

    @action.bound
    handleSelectUser(item, type) {
        if (type === 'add') {
          this.selectedUser = {
            ...this.selectedUser,
            [item.userid]: item
          };
        } else if (type === 'del') {
          var obj = {...this.selectedUser};
          delete obj[item.userid];
          this.selectedUser = obj;
        }
    }

    @action.bound
    async loadTreeNodeData(treeNode) {
      let vm = this;
        if (treeNode.props.children) {
          return;
        }
        const res = await $.get(authManageApi.getCompanyDept, {
            dept_id: treeNode.props.eventKey,
            role: this.role
        });
        runInAction(() => {
            if (res.status === 0) {
              let { dept_list, employee_num, manager_num } = res.data;
              if (treeNode.props.eventKey == 0) {
                vm.treeData[0].employee_num = employee_num;
                vm.treeData[0].manager_num = manager_num;
              }
              let leafArr = dept_list.employee.map(item => ({...item, isLeaf: true}));
              treeNode.props.dataRef.children = leafArr.concat(dept_list.dept);
              vm.treeData = [...vm.treeData];
            }
        });
    }

    @action.bound
    async getSearchList(params) {
        this.searchDataLoading = true;
        const res = await $.get(authManageApi.searchUser, params);
        runInAction(() => {
            if (res.status === 0) {
              let { list, last_page } = res.data;
              this.searchDataList = params.page === 1 ? list : this.searchDataList.concat(list);
              this.searchLastPage = last_page;
              this.searchDataDone = params.page >= last_page;
            } else {
                this.searchDataList = [];
                this.searchDataDone = false;
            }
            this.searchDataLoading = false;
        });
    }

    @action.bound
    async addAdminUser(params, callback) {
        const res = await $.post(authManageApi.addManage, params);
        runInAction(() => {
          if (res.status === 0) {
            message.success(res.message || '添加成功');
            callback && callback(res);
          }
        });
    }

    @action.bound
    onCheck = (item, index, e) => {
        this.dataSource[index].checked = e;
    }

    @action.bound
    checkAll = () => {
        let checkedNum = this.dataSource.filter((item) => {
            return !! item.checked;
        }).length;

        let checked = true;
        if (checkedNum == this.dataSource.length) {
            checked = false;
        }

        this.dataSource = this.dataSource.map((item) => {
            return {
                ... item,
                checked: checked,
            }
        })
    }

    @action.bound
    resetCheckStatus = () => {
        this.dataSource = this.dataSource.map((item) => {
            return {
                ... item,
                checked: false,
            }
        })
    }
}
