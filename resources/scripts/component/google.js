import React, { Component } from 'react';
import { isObservableArray } from 'mobx'
import { inject, observer } from 'mobx-react';
import { withRouter, Link } from 'react-router-dom';
import { Tag, Checkbox, Select, Dropdown, Menu, Icon } from 'antd';
import { get } from 'UTIL/mapMethod';
const CheckboxGroup = Checkbox.Group;
const Option = Select.Option;

@inject(state => ({
    filterObj: state.store.google.filterObj,
    location: state.store.google.location,
    globalSearch: state.store.global.globalSearch,
    getFilterOption: state.store.google.getFilterOption,
    getLocation: state.store.google.getLocation,
    setLocation: state.store.google.setLocation,
    updateSearchParams: state.store.global.updateSearchParams
}))

// @withRouter
@observer
export default class Google extends Component {
    state = {
        filter: {
            big_type: [],
            ext: [],
            size_type: [],
            small_type: [],
            country: '全部国家',
            province: '全部省份',
            city: '全部城市'
        }
        // keyWord: []
    }

    componentWillMount() {
        const { googleFilter = {} } = this.props;
        const { cityChange = '' } = googleFilter;
        if(Object.values(googleFilter).length && !cityChange) {
            const filterState = this.state.filter;
            Object.keys(googleFilter).forEach(filterKey => {
                const filterValue = googleFilter[filterKey];
                filterState[filterKey] = filterValue.split(',');
            })
            this.setState({
                filter: filterState
            })
        }else if(cityChange) {
            let {country,province,city} = googleFilter.location;
            this.setState({
                filter: {
                    ...this.state.filter,
                    country: country || '全部国家',
                    province: province || '全部省份',
                    city: city || '全部城市'
                }
            })
        }
    }

    componentWillReceiveProps(nextprops) {
        if (nextprops.globalSearch.keyword !== this.props.globalSearch.keyword) {
            this.reset();
        }
        // if(Object.values(nextprops.googleFilter).length) {
        //     this.setState({
        //         filter: {...this.state.filter, ...nextprops.googleFilter}
        //     })
        // }
    }

    getFilterData = (index) => {
        let filter = this.state.filter;
        let params = {
            big_type: filter.big_type.join(','),
            ext: filter.ext.join(','),
            size_type: filter.size_type.join(','),
            small_type: filter.small_type.join(','),
            location: {
                country: filter.country === '全部国家' ? null : filter.country,
                province: filter.province === '全部省份' ? null : filter.province,
                city: filter.city === '全部城市' ? null : filter.city
            }
            // keyWord: this.state.keyWord
        };
        this.props.load(params, index);
    }

    clearOptions = () => {
        if (this.props.globalSearch.keyword) {
            this.props.updateSearchParams({
                keyword: ''
            });
            this.reset();
        } else {
            this.reset();
            this.props.load();
        }
    }

    reset = (callback) => {
        // this.props.load();
        this.setState({
            filter: {
                big_type: [],
                ext: [],
                size_type: [],
                small_type: [],
                country: '全部国家',
                province: '全部省份',
                city: '全部城市'
            }
        }, callback)
    }

    componentDidMount() {
        // this.setState({
        //     keyWord: this.props.tagArr
        // })
        this.props.getFilterOption();
    }

    closeTag = (index) => {
        // const tags = this.state.keyWord.filter(tag => tag !== removedTag);
        // this.setState({
        //     keyWord: tags
        // }, this.getFilterData);
        // this.props.closeTag(index);
        this.reset(()=>{
            this.getFilterData(index);
            // this.props.closeTag(index);
        });
        // this.reset(this.getFilterData);

    }

    onChange = (key, e) => {
        let filter = Object.assign({}, this.state.filter, {
            [key]: e
        })
        if (key === 'country') {
            if (e === '全部国家') {
                filter.province = '全部省份';
                filter.city = '全部城市';
                this.props.setLocation('country');
            } else {
                this.props.getLocation({
                    country: e
                })
                filter.province = '全部省份';
                filter.city = '全部城市';
            }
        }
        if (key === 'province') {
            if (e === '全部省份') {
                filter.city = '全部城市';
                this.props.setLocation('province');
            } else {
                this.props.getLocation({
                    country: this.state.filter.country,
                    province: e
                })
                filter.city = '全部城市';
            }
        }
        this.setState({
            filter
        }, this.getFilterData)
    }

    onMenuClick = (key, item) => {
        this.onChange(key, item.key)
    }

    getCheckboxList = () => {
        let { filterObj, location } = this.props;
        let arr = [], arr2 = [];
        let titleObj = {
            // 'big_type': '类型',
            'ext': '格式',
            'size_type': '尺寸',
            'small_type': '分类',
            'country': '拍摄地点'
        }
        for(let key in titleObj) {
            let target = filterObj[key];
            if (!target) {
                continue ;
            }
            if (key === 'country') {
                let arrCountry = [];
                // if (Array.isArray(target)) {
                    target.map(item => {
                        arrCountry.push(<Menu.Item key={item}>
                            {item}
                        </Menu.Item>)
                    })
                // } else {
                //     for (let item in target) {
                //         arrCountry.push(<Menu.Item key={target[item]}>
                //             {target[item]}
                //         </Menu.Item>)
                //     }
                // }
                let menuCountry = (
                    <Menu onClick={this.onMenuClick.bind(this, key)}>
                        <Menu.Item key="全部国家">
                            全部国家
                        </Menu.Item>
                       {
                           arrCountry
                       }
                    </Menu>
                );
                let menuProvince = (
                    <Menu onClick={this.onMenuClick.bind(this, 'province')}>
                        <Menu.Item key="全部省份">
                            全部省份
                        </Menu.Item>
                    {location.province.slice().map(item => {
                        return <Menu.Item key={item}>
                            {item}
                        </Menu.Item>
                    })}
                    </Menu>
                );
                let menuCity = (
                    <Menu onClick={this.onMenuClick.bind(this, 'city')}>
                        <Menu.Item key="全部城市">
                            全部城市
                        </Menu.Item>
                    {location.city.slice().map(item => {
                        return <Menu.Item key={item}>
                            {item}
                        </Menu.Item>
                    })}
                    </Menu>
                );

                arr2.push(<div key={key} className="filter-item">
                    <span className="filter-label">{titleObj[key]}</span>
                    <Dropdown overlay={menuCountry} key={key} className="filter-dropdown">
                        <span>{this.state.filter.country}<Icon type="down" /></span>
                    </Dropdown>
                    <Dropdown overlay={menuProvince} key={"全部省份"} className="filter-dropdown">
                        <span>{this.state.filter.province}<Icon type="down" /></span>
                    </Dropdown>
                    <Dropdown overlay={menuCity} key={"全部城市"} className="filter-dropdown">
                        <span>{this.state.filter.city}<Icon type="down" /></span>
                    </Dropdown>
                </div>)
                continue;
            } else {
                let plainOptions = [];
                for (let targetKey in target) {
                    plainOptions.push({
                        label: target[targetKey],
                        value: targetKey
                    })
                }
                arr.push(<div key={key} className="filter-item">
                    <span className="filter-label">{titleObj[key]}</span>
                    <div className="filter-checkbox-container">
                        <CheckboxGroup value={this.state.filter[key]} options={plainOptions} onChange={this.onChange.bind(this, key)}></CheckboxGroup>
                    </div>
                </div>)
            }
        }
        arr = arr.concat(arr2)
        return arr;
    }
    render() {
        let { width, tagArr, totalNum } = this.props;
        return (
            <div className="googleSearch">
                <div className="container" style={{
                    width: !!width ? width : '80%'
                }}>
                    <div className="header">
                        <div className="tag-group">
                            {
                                tagArr && tagArr.length > 0 ? tagArr.map((item, index) => {
                                    return <Tag closable key={`${item}-${index}`} afterClose={() => this.closeTag(index)}>{item}</Tag>
                                }) : null
                            }
                            <span>当前有{totalNum}个结果</span>
                        </div>
                        <span className="span-btn" onClick={this.clearOptions}>清空筛选</span>
                    </div>
                    <div className="content">
                        {this.getCheckboxList()}
                    </div>
                </div>
            </div>
        )
    }
}
