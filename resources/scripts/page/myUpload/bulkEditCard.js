import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { Link, withRouter } from 'react-router-dom';
import { keywordFilterList, rejectReasonMap, ignoreSmallType } from 'CONST/map';
import { objEqual } from 'UTIL/util';
import { Button, Checkbox, Radio, Select, Row, Col, Input, message, Tag, Modal, AutoComplete, Table, Popover } from 'antd';
const Column = Table.Column;
import QCRSelect from 'COMPONENT/qCRSelect';
import QCopy from 'COMPONENT/qCopy';
const RadioGroup = Radio.Group;
// const CheckboxGroup = Checkbox.Group;
const Option = Select.Option;
let cityLock;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    filterObj: state.store.google.filterObj,
    userInfo: state.store.global.userInfo,
    myUpload: state.store.myUpload,
    getCitySuggest: state.store.myUpload.getCitySuggest,
    imgDataCheckList: state.store.myUpload.imgDataCheckList,
    changeCheckStatus: state.store.myUpload.changeCheckStatus,
    setReadyStatus: state.store.myUpload.setReadyStatus,
    setEditStatus: state.store.myUpload.setEditStatus,
    saveImg: state.store.myUpload.saveImg,
    editImgs: state.store.myUpload.editImgs,
    cityList: state.store.myUpload.cityList,
    batchEditParams: state.store.myUpload.batchEditParams,
    changeImgStatus: state.store.myUpload.changeImgStatus,
    resetCard: state.store.myUpload.resetCard,
    batchOkList: state.store.myUpload.batchOkList,
    prepareBatchData: state.store.myUpload.prepareBatchData,
    getBaseDept: state.store.myUpload.getBaseDept,
    baseDept: state.store.myUpload.baseDept,
}))

@withRouter
@observer
export default class BulkEditCard extends Component {

    state = {
        backFlag: false,
        colorParams: {
            title: false,
            city: false,
            titleLength: false,
            purchase_source: false,
            purchaseSourceLength: false,
            original_author: false,
            originalAuthorLength: false,
        },
        params: {
            title: '',
            purpose: '',
            small_type: undefined,
            city: '',
            imgLocation: '',
            city_id: '',
            keyword: [],
            upload_source: '',
            purchase_source: '',
            original_author: '',
            is_signature: false,
            upload_source_type: 'personal',
        },
        cancelVisible: false,
        saveVisible: false
    }

    componentDidMount() {
        if (this.props.item) {
            this.props.item.city_id && this.props.getCitySuggest(this.props.item.place);
            let colorParams = {};
            let title = this.props.item.title || this.props.item.file_name;
            if (title !== null &&
                title !== undefined && title.length > 15) {
                colorParams = {
                    ...this.state.colorParams,
                    titleLength: true
                }
            } else {
                colorParams = {
                    ...this.state.colorParams,
                    titleLength: false
                }
            }
            this.initParams = {
                ...this.state.params,
                colorParams,
                purpose: !this.props.item.purpose || this.props.item.purpose == 0 ? '2' : this.props.item.purpose,
                small_type: !this.props.item.small_type || this.props.item.small_type.length == 0 ? [] : this.props.item.small_type.slice().map(item => item.toString()),
                title,
                city: this.props.item.place,
                city_id: this.props.item.city_id,
                imgLocation: this.props.item.location ? this.props.item.location.join('/') : '',
                keyword: this.props.item.keyword ? this.props.item.keyword.slice() : [],
                upload_source: this.props.item.upload_source,
                bind_upload_source: this.props.item.upload_source,
                purchase_source: this.props.item.purchase_source,
                original_author: this.props.item.original_author,
                is_signature: this.props.item.is_signature,
                upload_source_type: this.props.item.upload_source.length == 0 ? 'personal' : 'department',
            };

            this.setState({
                params: { ... this.initParams },
                colorParams
            }, () => {
                this.checkDataReady(this.initParams, this.props.index)
            });
            (this.props.baseDept.length == 0) && this.props.getBaseDept()
        }
    }

    checkDataReady = (paramsObj, index) => {
        let editObj = {
            eid: this.props.item.eid,
            ...paramsObj,
            place: paramsObj.city
        };
        let param = this.upLoadImg(true);
        if (!param) {
            return ;
        }
        this.props.setEditStatus(index, false, editObj);
        this.props.setReadyStatus(index);
    }

    componentWillReceiveProps(nextprops) {
        if (!this.props.batchEditParams.setData &&
            nextprops.batchEditParams.setData &&
            nextprops.imgDataCheckList[nextprops.index].check) {
              let params = {...nextprops.batchEditParams.params};
              if (params.keyword && params.keyword.length > 0) {
                  params.keyword = params.keyword.slice(0);
              }
              if (nextprops.batchEditParams.params.small_type && nextprops.batchEditParams.params.small_type.length > 0) {
                  params.small_type = params.small_type.slice(0).map(item => item.toString());
              }

              this.setState({
                  params: {
                      ...this.state.params,
                      ...params
                  },
                  colorParams: {
                      ...this.state.colorParams,
                      titleLength: (params.title !== undefined  && params.title) ? (params.title.length > 15) : (this.state.params.title.length > 15)
                  }
              }, () => {
                  // this.onEdit(true);
                  this.onBatchEdit();
              })
          }
    }

    getFilter = key => {
        let { filterObj } = this.props;
        let arr = [];
        if (!filterObj[key]) {
            return null
        } else if (key === 'purpose') {
            for (let target in filterObj[key]) {
                arr.push(<Radio key={target} value={target}>{filterObj[key][target]}</Radio>)
            }
            return arr;
        } else if (key === 'small_type') {
            for (let target in filterObj[key]) {
                arr.push(<Option key={target} value={target}>{filterObj[key][target]}</Option>)
            }
            return arr;
        }
        return null
    }

    getValue = (type, key) => {
        return this.props.filterObj[type][key];
    }

    handleSearch = (value) => {
        if (cityLock) {
            clearTimeout(cityLock)
        }

        this.setState({
            params: {
                ...this.state.params,
                imgLocation: value
            }
        }, () => {
            cityLock = setTimeout(() => {
                this.setState({
                    params: {
                        ...this.state.params,
                        city: value.trim(),
                        city_id: '',
                        imgLocation: value
                    }
                })
                this.props.getCitySuggest(value.trim())
            }, 500)
        })
    }

    upLoadImg = (hideMsg) => {
        let checkObj = {
            title: '图片标题',
            purpose: "图片用途",
            small_type: "图片分类",
            city: '拍摄地点',
            // place: '拍摄地点',
            keyword: '关键词语',
            upload_source: '上传来源',
            purchase_source: '采购来源',
            original_author: '原始作者',
        };

        let errArr = [];
        let params = this.state.params;
        let flag = false;
        if (!params.city_id) {
            params.city_id = '';
            params.imgLocation = params.city;
        }
        if (params.upload_source_type == 'personal') {
            params.upload_source = '';
        }else{
            params.upload_source = params.bind_upload_source == '请选择上传来源' ? "" : params.bind_upload_source;
        }
        for (let key in params) {
            if (key === 'city_id' || key === 'imgLocation' || key === 'bind_upload_source' || key === 'upload_source_type'
                || key === 'is_signature' || key === 'purchase_source' || key === 'original_author'
                || (key === 'upload_source' && params.upload_source_type == "personal")) {
                continue ;
            }
            if (key === 'city' && ignoreSmallType.filter(v => params.small_type.includes(v)).length > 0) {
                continue ;
            }
            if (!params[key] || params[key].length === 0) {
                errArr.push(checkObj[key])
                flag = true
            }
        }
        if (flag) {
            !hideMsg && message.warning('请填写' + errArr.join('、') + '项');
            return false;
        }
        if (params.title.length > 15) {
            !hideMsg && message.warning('标题不可超过15个字符');
            return false;
        }
        params = {
            ...params,
            eid: this.props.item.eid,
            action: 'edit'
        }
        if (!parseInt(params.city_id)) {
            params.city_id = this.props.item.city_id
        }
        return params;
    }

    onParamsChange = (key, e) => {
        let obj = {};
        let tmpParams = this.state.params;
        if (key === 'title') {
            if (e.target.value.length > 15) {
                obj.colorParams = {
                    ...this.state.colorParams,
                    titleLength: true
                }
            } else {
                obj.colorParams = {
                    ...this.state.colorParams,
                    titleLength: false
                }
            }
        } else if (key === 'keyword') {
            let arr = [], isFilter = false;
            e.map(item => {
                if (keywordFilterList.indexOf(item) !== -1) {
                    isFilter = true;
                } else if (item.length <= 20) {
                    arr.push(item);
                }
            })
            isFilter && message.warning('关键词中含无效字符已过滤');
            if (!isFilter && arr.length < e.length) {
                message.warning('关键词超过20字符');
            }
            e = arr;
        } else if (key === 'is_signature') {
            e = e
        } else if (key === 'upload_source_type') {
            e = e
        } else if (key === 'purpose') {
            e = e
        }
        obj.params = {
            ...tmpParams,
            [key]: e.target ? e.target.value : e
        }
        this.setState({
            ...obj
        })
    }

    colorChange = (key) => {
        this.setState({
            colorParams: {
                ... this.state.colorParams,
                [key]: !this.state.colorParams[key]
            }
        })
    }

    titleChange = () => {
        if (this.state.params.title && this.state.params.title.length > 15) {
            message.warning('标题不可超过15个字符');
        }
        this.colorChange('title')
    }

    purchaseSourceChange = () => {
        if (this.state.params.purchase_source && this.state.params.purchase_source.length > 15) {
            message.warning('采购来源不可超过15个字符');
        }
        this.colorChange('purchase_source')
    }

    onBatchEdit = () => {
        let editObj = {
            eid: this.props.item.eid,
            ...this.state.params,
            place: this.state.params.city
        };
        let param = this.upLoadImg(true);
        if (!param) {
            this.props.prepareBatchData({editObj, checkObj: false, index: this.props.index});
            return;
        }
        if (objEqual(this.initParams, this.state.params)) {
            message.warning('没有修改内容，请编辑后再保存');
            this.props.prepareBatchData({editObj, checkObj: false, index: this.props.index});
            return;
        }
        this.props.prepareBatchData({editObj, checkObj: true, index: this.props.index});
    }

    onEdit = (hideMsg) => {
        let editFlag = this.props.imgDataCheckList[this.props.index].edit;
        let editObj = {
            eid: this.props.item.eid,
            ...this.state.params,
            place: this.state.params.city
        };
        //保存
        if (editFlag) {
            let param = this.upLoadImg(hideMsg);
            if (!param) {
                return ;
            }
            if (objEqual(this.initParams, this.state.params)) {
                message.warning('没有修改内容，请编辑后再保存');
                return ;
            }
            //保存接口
            this.props.saveImg(editObj, () => {
                this.props.setEditStatus(this.props.index, !editFlag, editObj);
                this.props.setReadyStatus(this.props.index);
                !hideMsg && this.setState({
                    saveVisible: true
                });
                this.initParams = {...this.state.params};
            });
        } else { //编辑
            this.props.setEditStatus(this.props.index, !editFlag, editObj);
        }

    }

    onCheck = (e) => {
        this.props.changeCheckStatus(this.props.index)
    }

    onPlaceChange = (value, e) => {
        value = {
            key: value,
            label: e.props.children
        }

        this.setState({
            params: {
                ...this.state.params,
                city: value.label[0],
                imgLocation: this.getWholeLocation(value.key),
                city_id: value.key

            }
        })
    }

    getWholeLocation = (id) => {
        var obj = this.props.cityList.filter(item => item.id == id)[0];
        return obj ? ((obj.country ? obj.country + '/' : '') +
        (obj.province ? obj.province + '/' : '') +
        (obj.city ? obj.city + '/' : '') + obj.name) : '';
    }

    imgEditStatus = () => {
        let { item, imgDataCheckList, index, type } = this.props;
        let editFlag = imgDataCheckList[index].edit;
        return type==0 && item.show_edit_label==1 &&
        !imgDataCheckList[index].ready && !editFlag;
    }

    handleCancel = () => {
        if (objEqual(this.initParams, this.state.params)) {
            this.props.setEditStatus(this.props.index, false, {
                eid: this.props.item.eid,
                ... this.state.params,
                place: this.state.params.city,
            });
        } else {
            this.setState({
                cancelVisible: true
            });
        }
    }

    confirmCancel = () => {
        this.setState({
            params: {...this.initParams},
            cancelVisible: false
        });
        this.props.setEditStatus(this.props.index, false, {
            eid: this.props.item.eid,
            ... this.state.params,
            place: this.state.params.city,
        });
    }

    onModalCancel = () => {
        this.setState({
            cancelVisible: false,
            saveVisible: false
        });
    }

    confirmAudit = () => {
        this.props.editImgs([this.props.index], this.props.reload);
        this.onModalCancel();
    }

    saveDraft = () => {
        if (this.props.type != 0) {
            this.props.changeImgStatus({eid: this.props.item.eid}, () => {
                message.success('图片为您保存至草稿箱');
                this.props.reload();
            });
        }
        this.setState({
            saveVisible: false
        });
    }

    getDataSource(cityList) {
        return cityList.map(item => {
            const { id, city, name} = item;
            return {value: id, text: `${name} (${city})`};
        });
    }

    isAdmin() {
       let userRole = new Set(this.props.userInfo.role.slice());
       let adminRole = new Set(["super_admin", "admin"]);
       // 交集
       let intersectionSet = new Set([...userRole].filter(x => adminRole.has(x)));
       return !! intersectionSet.size;
    }

    passImgTab = () => {
        return this.props.type == 2;
    }

    render() {
        let { params, colorParams, cancelVisible, saveVisible } = this.state;
        let { title, purpose, small_type, city_id, keyword, city, place, imgLocation, purchase_source, upload_source, original_author, is_signature, upload_source_type } = params;
        let { userInfo, baseDept, cityList, item, imgDataCheckList, index, type, keyN } = this.props;
        // debugger
        let editFlag = imgDataCheckList[index].edit;
        const children = cityList.map(d => <AutoComplete.Option key={d.id}>{d.name}{d.city ? `(${d.city})` : ''}</AutoComplete.Option>);
        const { filesize = '', uploader_realname } = item;
        const { GPSLatitude_new: lat, GPSLongitude_new: lng, GPSLatitudeRef, GPSLongitudeRef } = item.extend.extif && item.extend.extif.GPS || {};
        const { img_ext = '', img_width = '', img_height = '', Make = '', Model = '', ColorSpace = '', FocalLength = '', FNumber = '', ExposureMode = '', ExposureProgram = '', ExposureTime = '' } = item.extend.detail || {};
        const getDirection = (x) => {
            if (!x) {
                return ''
            }
            let res = '';
            switch (x) {
                case 'N':
                    res = '北'
                    break;
                case 'E':
                    res = '东'
                    break;
                case 'W':
                    res = '西'
                    break;
                case 'S':
                    res = '南'
                    break;
                default:
                    res = ''
            }
            return res
        }
        const data = [
            {
                key: '体积',
                value: `${filesize}`
            }, {
                key: '规格',
                value: img_width && img_height ? `${img_width}X${img_height}` : ''
            }, {
                key: '格式',
                value: `${img_ext}`
            }, {
                key: '设备制造商',
                value: `${Make}`
            }, {
                key: '设备型号',
                value: `${Model}`
            }, {
                key: '颜色空间',
                value: `${ColorSpace}`
            },
            // {
            //     key: '颜色描述文件',
            //     value: `${img_width}X${img_height}`
            // },
            {
                key: '焦距',
                value: `${FocalLength}`
            },
            // {
            //     key: 'Alpha通道',
            //     value: `${img_width}X${img_height}`
            // },
            // {
            //     key: '红眼',
            //     value: `${img_width}X${img_height}`
            // },
            {
                key: '光圈',
                value: `${FNumber}`
            }, {
                key: '测光模式',
                value: `${ExposureMode}`
            }, {
                key: '曝光程序',
                value: `${ExposureProgram}`
            }, {
                key: '曝光时间',
                value: `${ExposureTime}`
            }, {
                key: '经度',
                value: `${lng} ${getDirection(GPSLongitudeRef)}`
            }, {
                key: '纬度',
                value: `${lat} ${getDirection(GPSLatitudeRef)}`
            }
        ].filter(item => item.value.length && item.value !== 'null' && item.value.trim() !== 'undefined');

        const content = (
            <div className={ 'img-audit-page-table' }>
                <Table dataSource={ data } showHeader={ false } pagination={ false } size={ 'small' }
                       rowClassName={ (record, index) => {
                           return index % 2 === 1 ? 'bg-gray' : 'bg-white'
                       } }>
                    <Column
                        className={ 'text-center' }
                        width="100px"
                        title=""
                        dataIndex="key"
                        key="key"
                    />
                    <Column
                        title=""
                        width="440px"
                        dataIndex="value"
                        key="value"
                    />
                </Table>
            </div>
        );

        return (
          <div className="img-bulk-card-panel" key={keyN}>
              <div className={`img-bulk-card ${imgDataCheckList[index].check || editFlag ? 'checked' : ''}`}>
                {
                    this.imgEditStatus()
                    &&
                    <div className="edit-hint">未编辑</div>
                }
                <div className="img-bulk-checkbox">
                    <i className={`icon-font-checkbox big ${imgDataCheckList[index].check ? 'checked' : 'none-checked'}`}
                        onClick={this.onCheck}>
                        &#xe337;
                    </i>
                </div>
                <div className="img-bulk-container">
                    {
                        item.star
                        &&
                        <div className="star-hint">精选推荐</div>
                    }
                    <img className="upload-bulk-img" src={item.small_img}></img>
                    {
                        <Popover placement="right" content={ content } title="" trigger="hover"
                                 style={ { padding: 0 } }>
                            <div className={ 'exif-info' }>
                                <i className="icon-font-ourimg big-icon">&#xe10c;</i>
                                <span style={ { marginLeft: '3px' } }>EXIF信息</span>
                            </div>
                        </Popover>
                    }
                    <div className="hover-btn">
                        <Button className="img-btn" size="small" onClick={()=>this.props.onShow({visible: true, imgUrl: item.big_img})}>
                            <i className="icon-font-ourimg">&#xf407;</i>
                        </Button>
                    </div>
                </div>
                <div className="img-bulk-info-panel">
                  <div className="img-bulk-title">
                      {
                          editFlag
                          ?
                          <Row>
                              <Col span={3} className="label">
                                  图片标题
                              </Col>
                              <Col span={21} className="info">
                                  <Input
                                      placeholder="请在这里输入标题"
                                      value={title}
                                      style={{
                                          background: colorParams.title ? '#ffffff' : '#f8f8f8',
                                          color: colorParams.titleLength ? '#f5222d' : 'rgba(0, 0, 0, 0.65)'
                                      }}
                                      onFocus={this.colorChange.bind(this, 'title')}
                                      onBlur={this.titleChange}
                                      onChange={this.onParamsChange.bind(this, 'title')}>
                                  </Input>
                              </Col>
                          </Row>
                          : (
                              (title === '' || title === null || title === undefined) ? '无' : title
                          )
                      }
                  </div>
                  <div className="img-bulk-items">
                      <div className="img-bulk-item">
                          <Row>
                              <Col span={6} className="label">图片编号</Col><Col span={18} className="info eid">
                                  { item.eid }
                              </Col>
                          </Row>
                          <Row>
                              <Col span={6} className={ `label ${editFlag ? 'mt3' : ''}` }>图片分类</Col><Col span={18} className="info">
                                  {
                                      editFlag ? <Select
                                          mode="multiple"
                                          className="upload-edit-bar-select"
                                          placeholder="请选择分类" value={small_type}
                                          onChange={this.onParamsChange.bind(this, 'small_type')}>
                                          {
                                              this.getFilter('small_type')
                                          }
                                      </Select> :
                                      (!small_type || small_type.length === 0) ? '无' :
                                      small_type.map(key =>this.getValue('small_type', key)).join(',')

                                  }
                              </Col>
                          </Row>
                          <Row>
                              <Col span={6} className={ `label ${editFlag ? 'mt3' : ''}` }>拍摄地点</Col><Col span={18} className="info">
                                  {
                                      editFlag ? <AutoComplete
                                          labelInValue={false}
                                          placeholder="输入地点关键词可选择suggest项"
                                          onSearch={this.handleSearch}
                                          onSelect={this.onPlaceChange}
                                          defaultActiveFirstOption={false}
                                          filterOption={false}
                                          value={ imgLocation }
                                          // onChange={this.onPlaceChange}
                                          >{children}</AutoComplete>:
                                          !imgLocation ? '未填写' : imgLocation
                                  }
                              </Col>
                          </Row>
                          <Row className="img-bulk-keyword">
                              <Col span={6} className={ `label ${editFlag ? 'mt3' : ''}` }>关键词语</Col><Col span={18} className="info">
                                  {
                                      editFlag ?
                                      <div className="keyword-content">
                                          <Select
                                              mode="tags"
                                              className="upload-edit-bar-textarea"
                                              onChange={this.onParamsChange.bind(this, 'keyword')}
                                              tokenSeparators={[',', '，']}
                                              dropdownStyle={{display: 'none'}}
                                              placeholder="可输入图片元素相关的文字，按逗号或回车键生成标签"
                                              value={keyword}
                                              >
                                          </Select>
                                          <QCopy keyword={keyword}/>
                                       </div>
                                      : <div className="tag-container">{
                                          !keyword || keyword.length === 0 ? '空' : keyword.map(item => {
                                              return <Tag key={item + this.props.index}>{item}</Tag>
                                          })
                                      }</div>
                                  }
                              </Col>
                          </Row>
                          <Row>
                              <Col span={6} className={ `label ${editFlag ? 'mt3' : ''}` }>图片用途</Col>
                              <Col span={18} className="info">
                                  <QCRSelect
                                      editFlag={editFlag} purpose={purpose}
                                      onClick={this.onParamsChange}
                                  />
                              </Col>
                          </Row>
                      </div>
                      <div className="img-bulk-item">
                          {
                              this.isAdmin()
                              &&
                              <Row>
                                  <Col span={8} className={ `label ${editFlag ? 'mt3' : ''}` }>采购来源</Col>
                                  <Col span={16} className="info">
                                      {
                                          editFlag
                                          ?
                                          (
                                              <Input
                                                  placeholder="请输入采购来源"
                                                  value={purchase_source}
                                                  style={{
                                                      background: colorParams.purchase_source ? '#ffffff' : '#f8f8f8',
                                                      color: colorParams.purchaseSourceLength ? '#f5222d' : 'rgba(0, 0, 0, 0.65)'
                                                  }}
                                                  onFocus={this.colorChange.bind(this, 'purchase_source')}
                                                  onBlur={this.purchaseSourceChange}
                                                  onChange={this.onParamsChange.bind(this, 'purchase_source')}>
                                              </Input>
                                          )
                                          : (
                                              (purchase_source === '' || purchase_source === null || purchase_source === undefined) ? '无' : purchase_source
                                          )
                                      }
                                  </Col>
                              </Row>
                          }
                          {
                              this.isAdmin()
                              &&
                              <Row>
                                  <Col span={8} className={ `label ${editFlag ? 'mt3' : ''}` }>原始作者</Col>
                                  <Col span={16} className="info">
                                      {
                                          editFlag
                                          ?
                                          (
                                              <div className="original-author-content">
                                                  <Input
                                                      style={{
                                                          background: colorParams.original_author ? '#ffffff' : '#f8f8f8',
                                                          color: colorParams.originalAuthorLength ? '#f5222d' : 'rgba(0, 0, 0, 0.65)'
                                                      }}
                                                      onChange={this.onParamsChange.bind(this, 'original_author')}
                                                      placeholder="请输入原始作者"
                                                      value={original_author}
                                                      >
                                                  </Input>
                                                  <span className="signature-row">
                                                      <i className={`icon-font-checkbox small ${is_signature ? 'checked' : 'none-checked'}`}
                                                          onClick={ () => this.onParamsChange('is_signature', ! is_signature) }>
                                                          &#xe337;
                                                      </i>
                                                      <span onClick={ () => this.onParamsChange('is_signature', ! is_signature) }>需署名</span>
                                                  </span>
                                               </div>
                                          )
                                          : (
                                              (original_author === '' || original_author === null || original_author === undefined)
                                                  ? '无'
                                                  : original_author + ( is_signature ? "（需署名）" : "" )
                                          )
                                      }
                                  </Col>
                              </Row>
                          }
                          {
                              this.isAdmin()
                              &&
                              <Row>
                                  <Col span={8} className={ `label ${editFlag ? 'mt3' : ''}` }>上传来源</Col>
                                  <Col span={16} className="info">
                                      {
                                          editFlag
                                          ?
                                          (
                                              <div className="upload-source-content">
                                                  <div className="upload-source-radio">
                                                      <Row>
                                                          <Col span={10}>
                                                              <div className="radio-row">
                                                                  <i className={`icon-font-checkbox small ${upload_source_type == "personal" ? 'checked' : 'none-checked'}`} onClick={ () => this.onParamsChange('upload_source_type', 'personal') }>
                                                                      &#xe337;
                                                                  </i>
                                                                  <span onClick={ () => this.onParamsChange('upload_source_type', 'personal') }>个人</span>
                                                              </div>
                                                          </Col>
                                                          <Col span={14}>
                                                              <div className="radio-row">
                                                                  <i className={`icon-font-checkbox small ${upload_source_type == "department" ? 'checked' : 'none-checked'}`}
                                                                       onClick={ () => this.onParamsChange('upload_source_type', 'department') }>
                                                                      &#xe337;
                                                                  </i>
                                                                  <span onClick={ () => this.onParamsChange('upload_source_type', 'department') }>部门自采</span>
                                                              </div>
                                                          </Col>
                                                      </Row>
                                                  </div>
                                                  <div className="upload-source-select">
                                                      <Select
                                                          style={{ width: 200 }}
                                                          defaultValue={ upload_source.length > 0 ? upload_source : "请选择上传来源" }
                                                          onChange={ this.onParamsChange.bind(this, 'bind_upload_source') }
                                                          disabled={ upload_source_type == 'personal' }
                                                          >
                                                          {
                                                              baseDept.map((item, index) => (<Option value={ item }　key={index}>{ item }</Option>))
                                                          }
                                                      </Select>
                                                  </div>
                                               </div>
                                          )
                                          : (
                                              (upload_source === '' || upload_source === null || upload_source === undefined)
                                                  ? '个人'
                                                  : upload_source
                                          )
                                      }
                                  </Col>
                              </Row>
                          }
                          <Row>
                              <Col span={8} className="label">上传作者</Col>
                              <Col span={16} className="info author-txt">
                                  { uploader_realname }
                              </Col>
                          </Row>
                          <Row>
                              <Col span={8} className="label">上传时间</Col>
                              <Col span={16} className="info">
                                  {item.upload_time}
                              </Col>
                          </Row>
                      </div>
                  </div>
                  <div className="reject-box">
                      {
                          type === '3'
                          &&
                          <Row className="img-audit-nopass">
                              <Col span={3} className="label">驳回原因</Col>
                              <Col span={20} className="info"
                                  dangerouslySetInnerHTML={{__html: (item.reject_reason ? item.reject_reason.join('，') : '') + '。<span class="operator">-- @' + item.audit_username + '</span>'}}>
                              </Col>
                          </Row>
                      }
                  </div>
              </div>
              </div>
              <div className="upload-bulk-itembtn-container">
              {
                  item.system_check_img != 2 &&
                  <Button type="primary" onClick={() => this.onEdit(false)}>{editFlag ? '保存' : (this.imgEditStatus() ? '编辑' : '修改')}</Button>
              }
              {
                  editFlag ?
                  <Button className="del-btn" onClick={this.handleCancel}>取消</Button>
                  : <Button className="del-btn" onClick={this.props.delImg.bind(this, [this.props.index])}>删除</Button>
              }
              </div>
              <div className="img-bulk-card-interval"></div>
              <Modal
                  visible={cancelVisible}
                  footer={null}
                  title={null}
                  onCancel={this.onModalCancel}
                  width={380}
                  className="upload-operate-modal"
                  >
                  <div className="edit-confirm-content">
                      <div>图片修改的信息未保存，是否仍操作取消？</div>
                  </div>
                  <div className="edit-confirm-btngroup">
                      <Button type="primary" onClick={this.confirmCancel} className='left-btn'>继续取消</Button>
                      <Button onClick={this.onModalCancel}>返回保存</Button>
                  </div>
              </Modal>
              <Modal
                  visible={saveVisible}
                  footer={null}
                  title={null}
                  onCancel={this.onModalCancel}
                  width={380}
                  className="upload-operate-modal"
                  >
                  <div className="edit-confirm-content">
                      <div>保存成功，是否提交审核？</div>
                  </div>
                  <div className="edit-confirm-btngroup">
                      <Button type="primary" onClick={()=>this.confirmAudit()} className='left-btn'>提交审核</Button>
                      <Button onClick={this.saveDraft}>暂不提交</Button>
                  </div>
              </Modal>
          </div>
      )
    }
}
