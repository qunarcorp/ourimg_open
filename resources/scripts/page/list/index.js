import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import QGallery from 'COMPONENT/qGallery';
import Google from 'COMPONENT/google'
import QSort from 'COMPONENT/qSort';
import QBlank from "COMPONENT/qBlank";
import { locationMap } from 'CONST/map';
import { get } from 'UTIL/mapMethod';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    globalSearch: state.store.global.globalSearch,
    imgData: state.store.list.imgData,
    imgDataCount: state.store.list.imgDataCount,
    galleryLoading: state.store.list.galleryLoading,
    getImgGallery: state.store.list.getImgGallery,
    updateImgGallery: state.store.list.updateImgGallery,
    updateSearchParams: state.store.global.updateSearchParams,
    getCartCount: state.store.global.getCartCount,
    getLocation: state.store.google.getLocation
}))
@withRouter
@observer
class List extends Component {
    state = {
        sort: '0',
        offset: 0,
        limit: 10,
        loadCompleted: false,
        filter: {}
    }

    componentDidMount() {
        this.loadData();
    }

    componentWillMount() {
        // detail 跳转
        const { location: { state = {} } } = this.props;
        const { city = '' } = state;
        if(city) {
            // const country = get(city, '[0]')[0],
            // province = get(city, '[1]')[0],
            // cityValue = get(city, '[2]')[0];
            const cityArr = city.location.split('/');
            const country = cityArr[0],
            province = cityArr[1],
            cityValue = cityArr[2];
            const location = {
                country: country ? country : null,
                province: province ? province : null,
                city: cityValue ? cityValue : null
            }
            if(country) {
                this.props.getLocation({country});
            }
            if(province) {
                this.props.getLocation({country, province});
            }
            this.setState({
                filter: {...this.state.filter, location, cityChange: true}
            });
            if (city.keyword !== this.props.globalSearch.keyword) {
                this.props.updateSearchParams({keyword:city.keyword});
            } else {
                this.loadData({ offset:0, location });
            }
        }else if(Object.keys(state).length){
            this.setState({
                filter: {...this.state.filter, ...state}
            });
            this.loadData({ offset:0, ...state });
        }
    }
    componentWillReceiveProps(nextprops) {
        if (nextprops.globalSearch.keyword !== this.props.globalSearch.keyword) {
            this.loadData({ offset:0, keyword:nextprops.globalSearch.keyword });
        }
    }

    componentWillUnmount() {
        this.props.updateSearchParams({keyword:''});
    }

    loadData = (obj) => {
        let { keyword, big_type } = this.props.globalSearch;
        let { offset, limit, sort, filter } = this.state;
        let params = {
            keyword,
            big_type,
            offset,
            limit,
            sort_by: sort,
            page_source: 'list',
            ...filter
        };
        if (obj) {
            params = {...params, ...obj};
        }
        offset = params.offset;
        this.props.getImgGallery(params, (count) => {
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
    }

    filterChange = (filter, index) => {
        this.setState({
            offset: 0,
            filter
        }, () => {
            if (index !== undefined && index !== null) {
                this.editKeyword(index);
            } else {
                this.loadData();
            }
        });
    }
    sortChange = (e) => {
        this.setState({
            offset: 0,
            limit: 10,
            loadCompleted: false,
            sort: e.target.value
        }, this.loadData);
    }

    editKeyword = (index) => {
        let arr = this.props.globalSearch.keyword.split(',');
        arr.splice(index, 1);
        this.props.updateSearchParams({
            keyword: arr.join(',')
        });
    }

    reloadGallery = (index, type, callback) => {
        this.props.updateImgGallery(index, type, () => {
            callback && callback();
            if (type === 'addCart') {
                this.props.getCartCount();
            }
        });
    }

    render() {
        let { loadCompleted, filter } = this.state;
        let { imgData, globalSearch, galleryLoading, imgDataCount } = this.props;
        let tagArr = globalSearch.keyword ? globalSearch.keyword.split(/[,，]/) : [];
        return (
            <div className="q-list-page">
                <div className="q-content">
                    <Google
                        ref="imgFilter"
                        width="100%"
                        tagArr={tagArr}
                        totalNum={imgDataCount}
                        closeTag={this.editKeyword}
                        load={this.filterChange}
                        googleFilter={filter}
                    />
                    <QSort value={this.state.sort} onChange={this.sortChange}/>
                    { imgData.length === 0 ? <QBlank type='search'/> : <QGallery
                        loading={galleryLoading}
                        completed={loadCompleted}
                        elements={imgData}
                        load={this.loadData}
                        reload={this.reloadGallery}
                    ></QGallery>}
                </div>
            </div>
        )
    }
}

export default List;
