import React, { Component } from "react";
import { observable } from "mobx";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Table, Breadcrumb, Row, Col, Button } from "antd";
import { statisticExportApi } from "CONST/api";

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    onChangeTab: state.store.statistics.onChangeTab,
    statistics: state.store.statistics,
    getPlaceImgStatistic: state.store.statistics.getPlaceImgStatistic,
    getPlaceImgNum: state.store.statistics.getPlaceImgNum,
    updateSearchParams: state.store.global.updateSearchParams,
}))
@withRouter
@observer
class Place extends Component {

    state = {
        checkList: {
            country: true,
            province: false,
            city: false,
            county: false,
            poi: false,
            custom: false,
        },
        placeTab: 'country'
    }

    pid = 0

    columns = [
        {
            title: '地区',
            dataIndex: 'location_name',
            width: "40%",
        },
        {
            title: '图片数量',
            dataIndex: 'img_num',
            width: "40%",
        },
        {
          title: '图片数量',
          dataIndex: 'img_btn',
          render: (value, item) => {
              return (
                  <i className="icon-font-ourimg fz18 cursor-pointer" onClick={ () => {
                      if (item.location_level == 'country' || item.location_level == 'province' || item.location_level == 'city') {
                          let locationArr = [item.location.country, item.location.province, item.location.city].filter(function (item) {
                              return item;
                          });
                          this.props.history.push({
                              pathname: '/list',
                              state: {
                                  city: {
                                      location: locationArr.join('/'),
                                      keyword: locationArr[locationArr.length - 1]
                                  }
                              }
                          });
                      }else{
                          this.props.updateSearchParams({
                              keyword: item.location_name
                          });
                          this.props.history.push(`/list`)
                      }

                  } }>
                      &#xf251;
                  </i>
              )
          },
          width: "20%",
        }
    ];

    @observable breadcrumbRoutes = []
    itemRender(that, route, params, routes, paths) {
        return (
            <span className="breadcrumb" onClick={() => that.selectBreadcrumb(route.index)}>{route.breadcrumbName}</span>
        );
    }

    selectBreadcrumb(index) {
        this.breadcrumbRoutes = this.breadcrumbRoutes.slice(0, index + 1)
        let currentRoute = this.breadcrumbRoutes[this.breadcrumbRoutes.length - 1]
        this.props.getPlaceImgNum(currentRoute.child_level, currentRoute.id);
    }

    changeTab(placeTab) {
        this.state.placeTab = placeTab
        let breadcrumbName = ''
        switch (placeTab) {
            case 'country':
                breadcrumbName = '国家';
                break;
            case 'province':
                breadcrumbName = '省份';
                break;
            case 'city':
                breadcrumbName = '城市';
                break;
            case 'county':
                breadcrumbName = '郡县';
                break;
            case 'poi':
                breadcrumbName = 'POI';
                break;
            case 'custom':
                breadcrumbName = '未知';
                break;
            default:
                breadcrumbName = '国家';
        }

        this.pid = 0

        this.breadcrumbRoutes = [
            {
                breadcrumbName: breadcrumbName,
                id: 0,
                location_level: "",
                child_level: this.state.placeTab,
                index: 0
            }
        ];

        let checkList = this.state.checkList
        for(let key in checkList) {
            checkList[key] = placeTab == key
        }

        this.props.statistics.pagination = {
            ... this.props.statistics.pagination,
            current: 1
        }

        this.setState({
            checkList,
            placeTab
        }, () => {
            this.props.getPlaceImgNum(this.state.placeTab, this.pid);
        })
    }

    handlePage(pagination) {
        this.props.statistics.pagination = pagination;
        this.props.getPlaceImgNum(this.state.placeTab, this.pid);
    }

    componentDidMount() {
        this.props.getPlaceImgStatistic();
        this.changeTab(this.state.placeTab)
    }

    clickPlace(that, record, index) {
        return {
            onClick:(e) => {
                if (record.child_level) {
                    let location_name = record.location_name;
                    let breadcrumbRoutes = that.breadcrumbRoutes.filter(function(item){
                        return item.breadcrumbName == location_name
                    })
                    location_name = breadcrumbRoutes.length ? location_name + ' ' : location_name
                    that.breadcrumbRoutes.push({
                        id: record.id,
                        location_level: record.location_level,
                        child_level: record.child_level,
                        breadcrumbName: location_name,
                        index: that.breadcrumbRoutes.length
                    })
                    that.props.getPlaceImgNum(record.child_level, record.id);
                }
            }
        }
    }

    render() {
        let { breadcrumbRoutes, placeImgStatistic, placeImgNumData, pagination } = this.props.statistics;
        let checkList = this.state.checkList

        return (
            <div className="statistics-panel">
                <div className="panel-bar">
                    <div className={ `panel panel-tab ${ checkList.country ? 'check' : '' }` } onClick={() => this.changeTab('country')}>
                        <span className="main-title">国家</span>{ placeImgStatistic.country }
                    </div>
                    <div className={ `panel panel-tab ${ checkList.province ? 'check' : '' }` } onClick={() => this.changeTab('province')}>
                        <span className="main-title">省份</span>{ placeImgStatistic.province }
                    </div>
                    <div className={ `panel panel-tab ${ checkList.city ? 'check' : '' }` } onClick={() => this.changeTab('city')}>
                        <span className="main-title">城市</span>{ placeImgStatistic.city }
                    </div>
                    <div className={ `panel panel-tab ${ checkList.county ? 'check' : '' }` } onClick={() => this.changeTab('county')}>
                        <span className="main-title">郡县</span>{ placeImgStatistic.county }
                    </div>
                    <div className={ `panel panel-tab ${ checkList.poi ? 'check' : '' }` } onClick={() => this.changeTab('poi')}>
                        <span className="main-title">POI</span>{ placeImgStatistic.poi }
                    </div>
                    <div className={ `panel panel-tab ${ checkList.custom ? 'check' : '' }` } onClick={() => this.changeTab('custom')}>
                        <span className="main-title">未知</span>{ placeImgStatistic.custom }
                    </div>
                </div>
                <Row>
                    <Col span={3} offset={21}>
                        <Button className="export-btn" type="primary" href={ statisticExportApi['placeImgStatisticExport'] } target='_blank'>
                            导出全部
                        </Button>
                    </Col>
                </Row>
                <Breadcrumb itemRender={(route, params, routes, paths) => this.itemRender(this, route, params, routes, paths)} routes={this.breadcrumbRoutes.slice()} />
                <Table
                    columns={this.columns}
                    dataSource={placeImgNumData.slice()}
                    showHeader={false}
                    pagination={pagination}
                    rowKey="id"
                    size="small"
                    onRow={(record, index) => this.clickPlace(this, record, index)}
                    onChange={ (pagination, filters, sorter) => this.handlePage(pagination, filters, sorter) }
                    />
            </div>
        );
    }
}

export default Place;
