import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { withRouter } from "react-router-dom";
import { Button, Input, Row, Col, Select, Pagination } from "antd";
import QBlank from "COMPONENT/qBlank";

const Option = Select.Option;
const ACTIVITY_TYPES = {
    daily: "日常活动",
    city_sight: "城市景点",
    theme: "主题活动"
};
const STATE = {
    pending: {
        text: "未发布",
        class: "edit",
        buttonText: "发布"
    },
    online: {
        text: "已上线",
        class: "pub",
        buttonText: "下线"
    },
    offline: {
        text: "已下线",
        class: "disabled",
        buttonText: "发布"
    },
    end: {
        text: "已结束",
        class: "disabled",
        buttonText: "下线"
    }
};

@inject(state => {
    const activeManage = state.store.activeManage;
    return {
        history: state.history,
        router: state.router,
        pathname: state.history.location.pathname,
        onChangeTab: activeManage.onChangeTab,
        editActivity: activeManage.editActivity,
        getActivityList: activeManage.getActivityList,
        onSearch: activeManage.onSearch,
        activityList: activeManage.activityList,
        publish: activeManage.publish,
        clearSearchParams: activeManage.clearSearchParams,
        updateSearchParams: activeManage.updateSearchParams,
        serchParams: activeManage.serchParams,
        onPageChange: activeManage.onPageChange,
        page: activeManage.page
    };
})
@withRouter
@observer
class ActiveList extends Component {
    componentDidMount() {
        this.props.getActivityList();
    }

    getActivityTypeStr(arr) {
        const arr_zn = arr.map(type => ACTIVITY_TYPES[type]);
        // return arr_zn.toString();
        return arr_zn.join(", ");
    }

    render() {
        const {
            activityList,
            serchParams,
            onChangeTab,
            updateSearchParams,
            getActivityList,
            onSearch,
            clearSearchParams,
            publish,
            editActivity,
            onPageChange,
            page
        } = this.props;
        const { eid, activity_title, activity_type, state } = serchParams;
        return (
            <div className="page-activeList">
                <div className={"m-t-48"}>
                    <Button
                        type="primary"
                        size="large"
                        onClick={() => {
                            onChangeTab("EDIT");
                        }}
                    >
                        + 发布活动任务
                    </Button>
                </div>
                <div className={"m-t active-card-search__container"}>
                    <Input
                        placeholder="任务ID"
                        size="large"
                        className="active-card-search__width"
                        value={eid}
                        onChange={e =>
                            updateSearchParams("eid", e.target.value)
                        }
                    />

                    <Input
                        placeholder="任务名称"
                        size="large"
                        className="active-card-search__width"
                        value={activity_title}
                        onChange={e =>
                            updateSearchParams("activity_title", e.target.value)
                        }
                    />

                    <Select
                        size="large"
                        defaultValue=""
                        className="active-card-search__width"
                        value={activity_type}
                        onChange={value =>
                            updateSearchParams("activity_type", value)
                        }
                    >
                        <Option value="">全部任务类型</Option>
                        {Object.keys(ACTIVITY_TYPES).map(type => {
                            return (
                                <Option value={type} key={type}>
                                    {ACTIVITY_TYPES[type]}
                                </Option>
                            );
                        })}
                    </Select>
                    <Select
                        size="large"
                        defaultValue=""
                        className="active-card-search__width"
                        value={state}
                        onChange={value => updateSearchParams("state", value)}
                    >
                        <Option value="">全部任务状态</Option>
                        {Object.keys(STATE).map(type => {
                            return (
                                <Option value={type} key={type}>
                                    {STATE[type].text}
                                </Option>
                            );
                        })}
                    </Select>
                    <Button
                        type="primary"
                        size="large"
                        className="active-card-search__ml"
                        onClick={onSearch}
                    >
                        查询
                    </Button>

                    <Button
                        type="default"
                        size="large"
                        onClick={clearSearchParams}
                    >
                        清空查询
                    </Button>
                </div>

                {activityList && activityList.length > 0 ? (
                    <div className={"m-t page-activeList__content--container"}>
                        {activityList.map((activityItem, index) => {
                            const {
                                eid,
                                activity_title,
                                activity_type,
                                release_time,
                                update_time,
                                begin_time,
                                end_time,
                                background_img,
                                state
                            } = activityItem;
                            const b_t_fmt = begin_time
                                ? new Date(begin_time).Format("yyyy.MM.dd")
                                : "";
                            const e_t_fmt = end_time
                                ? new Date(end_time).Format("yyyy.MM.dd")
                                : "";
                            const u_t_fmt = update_time
                                ? new Date(update_time).Format("yyyy.MM.dd")
                                : "";
                            const r_t_fmt = release_time
                                ? new Date(release_time).Format("yyyy.MM.dd")
                                : "";
                            const activity_type_str = this.getActivityTypeStr(
                                activity_type
                            );
                            return (
                                <div className={"active-card"} key={eid}>
                                    <div className={"img-wrap"}>
                                        <img src={background_img} alt="" />
                                    </div>
                                    <div className={"active-info"}>
                                        <div
                                            className={"active-info_title p-l"}
                                        >
                                            <div>
                                                <div
                                                    className={
                                                        "text-light fs-20 font-weight-b"
                                                    }
                                                >
                                                    {activity_title}
                                                </div>
                                                <div className={"fs-14 m-t-5"}>
                                                    <span
                                                        className={"text-gray"}
                                                    >
                                                        任务期限：
                                                    </span>
                                                    <span
                                                        className={"text-black"}
                                                    >
                                                        {b_t_fmt
                                                            ? `${b_t_fmt} - ${e_t_fmt}`
                                                            : "长期"}
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                className={`active-info_label fs-16 ${
                                                    STATE[state].class
                                                }`}
                                            >
                                                {STATE[state].text}
                                            </div>
                                        </div>
                                        <div className="active-info_line" />
                                        <div
                                            className={"active-info_content p"}
                                        >
                                            <div className={"fs-14"}>
                                                <Row>
                                                    <Col span={13}>
                                                        <span
                                                            className={
                                                                "text-gray"
                                                            }
                                                        >
                                                            任务ID：
                                                        </span>
                                                        <span
                                                            className={
                                                                "text-black"
                                                            }
                                                        >
                                                            {eid}
                                                        </span>
                                                    </Col>
                                                    <Col span={10}>
                                                        <span
                                                            className={
                                                                "text-gray"
                                                            }
                                                        >
                                                            发布时间：
                                                        </span>
                                                        <span
                                                            className={
                                                                "text-black"
                                                            }
                                                        >
                                                            {r_t_fmt}
                                                        </span>
                                                    </Col>
                                                    <Col
                                                        span={13}
                                                        className="m-t-5"
                                                    >
                                                        <span
                                                            className={
                                                                "text-gray"
                                                            }
                                                        >
                                                            任务类型：
                                                        </span>
                                                        <span
                                                            className={
                                                                "text-black"
                                                            }
                                                        >
                                                            {activity_type_str}
                                                        </span>
                                                    </Col>
                                                    <Col
                                                        span={10}
                                                        className="m-t-5"
                                                    >
                                                        <span
                                                            className={
                                                                "text-gray"
                                                            }
                                                        >
                                                            更新时间：
                                                        </span>
                                                        <span
                                                            className={
                                                                "text-black"
                                                            }
                                                        >
                                                            {u_t_fmt}
                                                        </span>
                                                    </Col>
                                                </Row>
                                            </div>
                                            <div className={"text-center"}>
                                                <Button
                                                    type="primary"
                                                    size="large"
                                                    className={"m-r-s"}
                                                    onClick={() => {
                                                        publish(
                                                            eid,
                                                            state,
                                                            index
                                                        );
                                                    }}
                                                >
                                                    {STATE[state].buttonText}
                                                </Button>
                                                <Button
                                                    type="primary"
                                                    size="large"
                                                    className={"m-r-s"}
                                                    onClick={() => {
                                                        editActivity(
                                                            "EDIT",
                                                            index,
                                                            activityItem
                                                        );
                                                    }}
                                                >
                                                    编辑
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <QBlank type="empty" />
                )}
                <Pagination
                    className="material-pagination"
                    current={page.current}
                    pageSize={page.pageSize}
                    total={page.total}
                    onChange={onPageChange}
                    onShowSizeChange={(current, size) => onPageChange(1, size)}
                    showSizeChanger
                    showQuickJumper
                />
            </div>
        );
    }
}

export default ActiveList;
