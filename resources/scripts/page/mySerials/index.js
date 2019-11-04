import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    HashRouter as Router,
    Route,
    withRouter,
    Link
} from "react-router-dom";
import autobind from "autobind-decorator";
import { Tabs, Select, Input, Pagination, Checkbox, message } from "antd";
import { URL_TEXT_MAP } from "CONST/mySerials";
import { MATERIAL_MAP } from "CONST/router";
import { get } from "UTIL/mapMethod";
import QBlank from "COMPONENT/qBlank";

const TabPane = Tabs.TabPane;
const Option = Select.Option;
const Search = Input.Search;

@inject(state => ({
    pathname: state.history.location.pathname,
    tabsList: state.store.user.tabsList,
    getTabsList: state.store.user.getTabsList,
    dataList: state.store.mySerial.dataList,
    getDataList: state.store.mySerial.getDataList,
    downloadImg: state.store.detail.downloadImg,
    deleteItem: state.store.mySerial.deleteItem
}))
@withRouter
@observer
class mySerials extends Component {
    constructor(props) {
        super(props);
        this.pathKey = this.getPathKey();
        this.eidArray = [];
        this.state = {
            bread: "",
            time_id: "1", // 存放select选择值
            keyword: "", // 存放搜索值
            total: 0,
            pagination: {
                pageSize: 12, // 对应接口limit
                current: 1 //页码
            },
            indeterminate: true, // antd 实现全选操作
            eid: [], // 存放checkbox选中值
            checkAll: false,
            tabKey: 1
        };
    }

    getPathKey(props) {
        const { pathname = "" } = props || this.props,
            pathArr = pathname.split("/");
        return get(pathArr, "[2]")[0];
    }

    componentWillReceiveProps(nextprops) {
        this.getBread(nextprops.history.location);
    }

    componentDidMount() {
        this.props.getTabsList();
        this.getBread();
        this.getList();
    }

    getBread(props) {
        const { pathname = "" } = props || this.props,
            pathArr = pathname.split("/"),
            pathKey = get(pathArr, "[2]");
        this.setState({
            bread: get(MATERIAL_MAP[pathKey[0]], "label")
        });
    }

    @autobind
    setPageTotalAndEidArray(total, eidArray) {
        this.setState({
            total: Number(total, 10)
        });
        this.eidArray = eidArray;
    }

    @autobind
    handleSelect(time_id) {
        this.setState(
            {
                time_id,
                pagination: { pageSize: 12, current: 1 }
            },
            this.getList
        );
    }

    @autobind
    getList() {
        const {
            tabKey,
            time_id,
            keyword,
            pagination: { pageSize, current }
        } = this.state;
        var params = {
            time_id,
            keyword,
            limit: pageSize,
            offset: pageSize * (current - 1),
            sort_by: 0
        };
        if (this.pathKey != "myFootprint") {
            params.big_type = tabKey;
        }
        this.props.getDataList(
            params,
            this.pathKey,
            this.setPageTotalAndEidArray
        );
    }

    @autobind
    handleSearch(keyword) {
        this.setState(
            {
                keyword: keyword.replace(/，/g, ","),
                pagination: { pageSize: 12, current: 1 }
            },
            this.getList
        );
    }

    downloadImg(eid) {
        this.props.downloadImg({ eid });
    }

    @autobind
    handlePageChange(page, pageSize) {
        this.setState(
            {
                pagination: {
                    pageSize,
                    current: page
                }
            },
            this.getList
        );
    }

    @autobind
    handleCheckAllChange(e) {
        const checked = e.target.checked;
        this.setState({
            eid: checked ? this.eidArray : [],
            indeterminate: false,
            checkAll: e.target.checked
        });
    }

    @autobind
    handleCheckChange(eid) {
        this.setState({
            eid,
            indeterminate: !!eid.length && eid.length < this.eidArray.length,
            checkAll: eid.length === this.eidArray.length
        });
    }

    deleteItem(del_type) {
        const { eid = [] } = this.state;
        if (del_type !== "all" && eid.length === 0) {
            message.warning("请至少选择一张图片");
            return;
        }
        this.props.deleteItem(
            {
                eid: eid.join(),
                del_type
            },
            this.pathKey,
            this.getList
        );
    }

    @autobind
    switchTab(key) {
        this.setState(
            {
                tabKey: key,
                pagination: {
                    pageSize: 12, // 对应接口limit
                    current: 1 //页码
                },
                time_id: "1", // 存放select选择值
                keyword: "" // 存放搜索值
            },
            this.getList
        );
    }

    render() {
        const { big_type = {}, time_list = {} } = this.props.tabsList;
        const {
            pagination,
            bread,
            total,
            time_id,
            checkAll,
            eid,
            tabKey
        } = this.state;
        const { dataList } = this.props;
        return (
            <div className="my-serial-page content">
                <div className="bread">{bread}</div>
                {/* myFootprint 没有tabs选择*/}
                {this.pathKey != "myFootprint" ? (
                    <Tabs
                        className="tab material-tab"
                        onChange={this.switchTab}
                    >
                        {Object.keys(big_type).map(key => {
                            return <TabPane tab={big_type[key]} key={key} />;
                        })}
                    </Tabs>
                ) : (
                    <div className="line" />
                )}
                {dataList.length > 0 && (
                    <div className="search-param">
                        <Select
                            value={time_id}
                            // defaultValue="请选择"
                            className="select"
                            onChange={this.handleSelect}
                        >
                            <Option value="" key="please_select">
                                请选择
                            </Option>
                            {Object.keys(time_list).map(key => {
                                const item = time_list[key];
                                return (
                                    <Option value={key} key={key}>{`${item}${
                                        URL_TEXT_MAP[this.pathKey]
                                    }`}</Option>
                                );
                            })}
                        </Select>
                        <Search
                            enterButton
                            // value={keyword}
                            className="search"
                            placeholder="请输入关键字"
                            onSearch={this.handleSearch}
                            // onChange={this.handleChange}
                        />
                    </div>
                )}
                {dataList.length === 0 ? (
                    tabKey == 1 || this.pathKey === "myFootprint" ? (
                        <QBlank type='empty' />
                    ) : (
                        <QBlank type='loading' />
                    )
                ) : (
                    <div className="serial-content">
                        <Checkbox.Group
                            onChange={this.handleCheckChange}
                            value={eid}
                        >
                            {dataList.map(item => {
                                const {
                                    small_img,
                                    eid,
                                    title,
                                    operate_date,
                                    is_del,
                                    download_permission
                                } = item;
                                return (
                                    <div className="item" key={eid}>
                                        <div className="img-box">
                                            <Link
                                                to={{
                                                    pathname: "/detail",
                                                    search: `?eid=${eid}`
                                                }}
                                            >
                                                <img
                                                    src={small_img}
                                                    className="img"
                                                />
                                            </Link>
                                            {this.pathKey != "myDownloads" && (
                                                <Checkbox value={eid} />
                                            )}
                                        </div>
                                        <div className="title-line">
                                            <span className="title">
                                                {title}
                                            </span>
                                            {this.pathKey != "myDownloads" ? (
                                                !is_del && download_permission && (
                                                    <span
                                                        onClick={() =>
                                                            this.downloadImg(
                                                                eid
                                                            )
                                                        }
                                                        className="download-image-operation"
                                                    >
                                                        <span
                                                            className="icon-font-ourimg icon-downloads"
                                                            download
                                                        >
                                                            &#xf0aa;
                                                        </span>
                                                        <span
                                                            className="text-downloads"
                                                            download
                                                        >
                                                            下载
                                                        </span>
                                                    </span>
                                                )
                                            ) : (
                                                <span
                                                    className="text-downloads"
                                                    download
                                                >
                                                    {operate_date}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </Checkbox.Group>
                    </div>
                )}
                {dataList.length > 0 && this.pathKey != "myDownloads" ? (
                    <div className="operation-box">
                        <Checkbox
                            className="checkbox"
                            // indeterminate={indeterminate}
                            onChange={this.handleCheckAllChange}
                            checked={checkAll}
                        >
                            全选
                        </Checkbox>
                        <span
                            className="delete"
                            onClick={() => this.deleteItem("")}
                        >
                            删除
                        </span>
                        {this.pathKey == "myFootprint" && (
                            <span
                                className="delete-all"
                                onClick={() => this.deleteItem("all")}
                            >
                                清空全部
                            </span>
                        )}
                    </div>
                ) : (
                    ""
                )}
                <Pagination
                    total={total}
                    {...pagination}
                    className="pagination"
                    showQuickJumper
                    hideOnSinglePage
                    onChange={this.handlePageChange}
                />
            </div>
        );
    }
}

export default mySerials;
