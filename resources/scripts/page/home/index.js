import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { withRouter, Link } from "react-router-dom";
import { Input, Select } from "antd";
import QGallery from "COMPONENT/qGallery";
const Search = Input.Search;
const InputGroup = Input.Group;
const Option = Select.Option;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    imgData: state.store.home.imgData,
    galleryLoading: state.store.home.galleryLoading,
    getImgGallery: state.store.home.getImgGallery,
    updateImgGallery: state.store.home.updateImgGallery,
    updateSearchParams: state.store.global.updateSearchParams,
    getCartCount: state.store.global.getCartCount
}))
@withRouter
@observer
class Home extends Component {
    state = {
        big_type: "1",
        offset: 0,
        limit: 10,
        // 活动下线
        showBanner: false,
        loadCompleted: false
    };

    componentDidMount() {
        this.loadData();
    }

    loadData = () => {
        let { offset, limit } = this.state;
        let params = {
            offset,
            limit,
            sort_by: 1,
            page_source: "list"
        };
        this.props.getImgGallery(params, count => {
            if (offset + limit < count) {
                this.setState({
                    offset: offset + limit,
                    loadCompleted: false
                });
            } else {
                this.setState({
                    loadCompleted: true
                });
            }
        });
    };

    onSearch = value => {
        this.props.updateSearchParams({
            keyword: value.replace(/^\s+|\s+$/g, ""),
            big_type: this.state.big_type
        });
        this.props.history.push(`/list`);
    };

    onTypeChange = value => {
        this.setState({
            big_type: value
        });
    };

    reloadGallery = (index, type, callback) => {
        this.props.updateImgGallery(index, type, () => {
            callback && callback();
            if (type === "addCart") {
                this.props.getCartCount();
            }
        });
    };

    closeBanner = e => {
        e.stopPropagation();
        this.setState({
            showBanner: false
        });
    };

    goToCredits = () => {
        this.props.history.push("/credits/creditsStore");
    };

    clickSearch = e => {
        e.stopPropagation();
    };

    render() {
        let { loadCompleted, showBanner } = this.state;
        let { imgData, galleryLoading } = this.props;
        return (
            <div className="q-home-page">
                <div className="q-home-header" onClick={this.goToCredits}>
                    <div className="content">
                        <div onClick={this.clickSearch}>
                            <InputGroup compact className="search-tool">
                                <Select
                                    onChange={this.onTypeChange}
                                    value={this.state.big_type}
                                    className="search-select"
                                >
                                    <Option value="1">图片</Option>
                                </Select>
                                <Search
                                    maxLength={20}
                                    className="search-input"
                                    placeholder="请输入20个字符以内的关键词，多个关键词以逗号分隔"
                                    onSearch={this.onSearch}
                                    enterButton
                                />
                            </InputGroup>
                        </div>
                    </div>
                </div>
                <div className="q-content">
                    {/* <div className="q-gallery-title">热门推荐</div> */}
                    <QGallery
                        completed={loadCompleted}
                        elements={imgData}
                        load={this.loadData}
                        loading={galleryLoading}
                        reload={this.reloadGallery}
                    />
                </div>
                {showBanner && (
                    <div className="footer-banner">
                        <div className="q-content" onClick={this.goToCredits}>
                            <i
                                className="icon-font-ourimg close-icon"
                                onClick={this.closeBanner}
                            >
                                &#xe4d6;
                            </i>
                        </div>
                    </div>
                )}
            </div>
        );
    }
}

export default Home;
