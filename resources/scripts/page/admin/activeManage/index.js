import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { withRouter } from "react-router-dom";
import { storeTabMap } from "CONST/map";
// import {} from "antd";
import ActiveList from "./activeList";
import EditActive from "./editActive";
import moment from "moment";

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    tabKey: state.store.activeManage.tabKey,
    onChangeTab: state.store.activeManage.onChangeTab,
    editData: state.store.activeManage.editData,
    changeFormData: state.store.activeManage.changeFormData
}))
@withRouter
@observer
class ActiveManage extends Component {
    changeFormData = (values) => {
        if (Object.keys(values).length !== 1) return;
        const prop = Object.keys(values)[0];
        const val = values[prop].value;
        this.props.changeFormData(prop, val);
    }
    render() {
        const { tabKey, editData } = this.props;
        return (
            <div className="content">
                <div className="header-manage__container">
                    <div className="header-manage__text--b">TASK</div>
                    <div className="header-manage__text--f">活动任务管理</div>
                </div>
                {tabKey === "MANAGE" && <ActiveList />}
                {tabKey === "EDIT" && (
                    <EditActive
                        {...editData}
                        onFormChange={this.changeFormData}
                    />
                )}
            </div>
        );
    }
}

export default ActiveManage;
