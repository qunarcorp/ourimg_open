import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { Link, withRouter } from 'react-router-dom';
import QCRSelect from 'COMPONENT/qCRSelect';
import { keywordFilterList, ignoreSmallType } from 'CONST/map';
import { Upload, Button, Checkbox, Row, Col, Input, Radio, Select, Modal, message, AutoComplete, Popover, Table } from 'antd';
const { Column } = Table;
const RadioGroup = Radio.Group;
const Option = Select.Option;
import QCopy from 'COMPONENT/qCopy';
let cityLock;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    userInfo: state.store.global.userInfo,
    filterObj: state.store.google.filterObj,
    getFilterOption: state.store.google.getFilterOption,
    imgData: state.store.component.imgData,
    editImg: state.store.component.editImg,
    delImg: state.store.component.delImg,
    // getCitySuggest: state.store.component.getCitySuggest,
    getCitySuggest: state.store.myUpload.getCitySuggest,
    cityList: state.store.myUpload.cityList,
    getBaseDept: state.store.myUpload.getBaseDept,
    baseDept: state.store.myUpload.baseDept,
}))

@withRouter
@observer
export default class UploadEdit extends Component {

    state = {
        colorParams: {
            title: false,
            city: false,
            titleLength: false,
            purchase_source: false,
            purchaseSourceLength: false,
            original_author: false,
            originalAuthorLength: false,
        },
        visible: false,
        confirm: true,
        confirmType: '',
        modalContent: '',
        modalCancelBtn: '',
        params: {
            title: '',
            purpose: '2',
            small_type: undefined,
            city: '',
            city_id: '',
            imgLocation: '',
            // place: '',
            keyword: [],
            upload_source: '',
            purchase_source: '',
            original_author: '',
            is_signature: false,
            upload_source_type: 'personal',
        }
    }

    componentDidMount() {
        this.props.getFilterOption();
        if (!this.props.imgData.img) {
            this.props.history.push('/upload')
        } else {
            this.props.imgData.city_id && this.props.getCitySuggest(this.props.imgData.place);
            let params = {
                ...this.state.params,
                city: this.props.imgData.place || this.props.imgData.city,
                city_id: this.props.imgData.city_id
            }

            if (this.props.imgData.file_name) {
                params.title = this.props.imgData.file_name
            }
            if (this.props.imgData.title) {
                params.title = this.props.imgData.title
            }
            if (params.title.length > 15) {
                this.setState({
                    colorParams: {
                        ... this.state.colorParams,
                        titleLength: true
                    }
                });
            }
            if (this.props.imgData.purpose) {
                params.purpose = this.props.imgData.purpose
            }
            if (this.props.imgData.keyword) {
                params.keyword = this.props.imgData.keyword.slice()
            }
            if (this.props.imgData.small_type) {
                params.small_type = this.props.imgData.small_type.slice()
            }
            params.place = params.city
            params.imgLocation = params.city
            params.upload_source = this.props.imgData.upload_source
            params.bind_upload_source = this.props.imgData.upload_source
            params.purchase_source = this.props.imgData.purchase_source
            params.original_author = this.props.imgData.original_author
            params.is_signature = this.props.imgData.is_signature
            params.upload_source_type = this.props.imgData.upload_source_type
            params.extend = this.props.imgData.extend

            this.setState({
                params: params
            });

            (this.props.baseDept.length == 0) && this.props.getBaseDept()
        }
    }

    onComfrimChange = (e) => {
        this.setState({
            confirm: e.target.checked
        })
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

    confirm = confirmType => {
        let modalContent = '', modalCancelBtn = '取消';
        switch(confirmType) {
            case 'del': modalContent = '确认删除正在编辑中的素材吗？'
                break;
            case 'sub': modalContent = '确认结束编辑并上传素材吗？'
                break;
            case 'finish': modalContent = '上传完成棒棒哒~'
                modalCancelBtn = '继续上传'
                break;
        }
        this.setState({
            visible: true,
            confirmType,
            modalContent,
            modalCancelBtn
        })
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
                }, () => {
                    this.props.getCitySuggest(value.trim())
                })
            }, 500)
        })
    }

    submit = () => {
        let confirmType = this.state.confirmType;
        if (confirmType === 'del') {
            this.deleteImg();
        } else if (confirmType === 'sub') {
            // this.upLoadImg(true)
            let res = this.upLoadImg(false)
            // console.log(res)
            if (res === false) {
                this.setState({
                    visible: false,
                })
            }
        } else {
            this.props.history.push('/material/myUpload?type=1');
        }
    }

    deleteImg = () => {
        let params = {
            eid: this.props.imgData.eid || 1
        }
        this.props.delImg(params, () => {
            this.onCancel();
            this.props.history.push('/upload')
        })
    }

    upLoadImg = (hideMsg) => {
        let checkObj = {
            title: '标题',
            purpose: "用途",
            small_type: "分类",
            city: '拍摄地点',
            // place: '拍摄地点',
            keyword: '关键词',
            upload_source: '上传来源',
            purchase_source: '采购来源',
            original_author: '原始作者',
        };

        let errArr = [];
        let params = this.state.params;
        let flag = false;
        if (!params.city_id) {
            params.city_id = 0;
            params.imgLocation = params.city;
        }
        if (params.upload_source_type == 'personal') {
            params.upload_source = '';
        }else{
            params.upload_source = params.bind_upload_source == '请选择上传来源' ? "" : params.bind_upload_source;
        }
        params.small_type = params.small_type === undefined ? [] : params.small_type
        for (let key in params) {
            if (key === 'city_id' || key === 'imgLocation' || key === 'bind_upload_source' || key === 'upload_source_type'
                || key === 'is_signature' || key === 'purchase_source' || key === 'original_author' || key === 'place'
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
            eid: this.props.imgData.eid,
            place: params.city,
            action: 'edit'
        }

        this.props.editImg(params, () => {
            this.confirm('finish')
        })
    }

    onCancel = () => {
        this.setState({
            visible: false
        })
    }

    onModalCancel = () => {
        if (this.state.confirmType === 'finish') {
            this.props.history.push('/upload')
        } else {
            this.onCancel();
        }
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

    isAdmin() {
       let userRole = new Set(this.props.userInfo.role.slice());
       let adminRole = new Set(["super_admin", "admin"]);
       // 交集
       let intersectionSet = new Set([...userRole].filter(x => adminRole.has(x)));
       return !! intersectionSet.size;
    }

    render() {
        let { params, confirm, visible, modalContent, modalCancelBtn, colorParams } = this.state;
        let { title, purpose, small_type, city_id, keyword, city,purchase_source,
          original_author, is_signature, upload_source_type,
          upload_source, bind_upload_source, imgLocation, extend
        } = params;
        let { imgData, cityList, baseDept } = this.props;

        const children = cityList.map(d => <AutoComplete.Option key={d.id}>{d.name}{d.city ? `(${d.city})` : ''}</AutoComplete.Option>);

        let exifData = []
        if (extend) {
            const { GPSLatitude_new: lat, GPSLongitude_new: lng, GPSLatitudeRef, GPSLongitudeRef } = extend.extif && extend.extif.GPS || {};
            const { filesize = '', img_ext = '', img_width = '', img_height = '', Make = '', Model = '', ColorSpace = '', FocalLength = '', FNumber = '', ExposureMode = '', ExposureProgram = '', ExposureTime = '' } = extend.detail || {};

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
            exifData = [
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
                {
                    key: '焦距',
                    value: `${FocalLength}`
                },
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
        }

        const content = (
            <div className={ 'img-audit-page-table' }>
                <Table dataSource={ exifData } showHeader={ false } pagination={ false } size={ 'small' }
                       rowClassName={ (record, index) => {
                           return index % 2 === 1 ? 'bg-gray img-audit-page-table--font' : 'bg-white img-audit-page-table--font'
                       } } className='img-audit-page-table'>
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

        return (<section className="upload-edit-container">
            <div className="upload-edit">
                <div className="upload-edit-content">
                    <div className="upload-edit-img-container">
                        <img className="upload-edit-img" src={imgData.img}></img>
                    </div>
                    <div className="upload-edit-bar">
                        <div className="img-bulk-items">
                            <div className="img-bulk-item">
                                <Row>
                                    <Col span={6} className="label">
                                        图片标题
                                    </Col>
                                    <Col span={18} className="info">
                                        <Input
                                            placeholder="请在这里输入标题"
                                            value={ title }
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
                                <Row>
                                    <Col span={6} className={ `label mt3` }>
                                        图片分类
                                    </Col>
                                    <Col span={18} className="info">
                                        <Select
                                            mode="multiple"
                                            className="upload-edit-bar-select"
                                            placeholder="请选择分类" value={ small_type }
                                            onChange={this.onParamsChange.bind(this, 'small_type')}>
                                            {
                                                this.getFilter('small_type')
                                            }
                                        </Select>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={6} className={ `label mt3` }>
                                        拍摄地点
                                    </Col>
                                    <Col span={18} className="info">
                                        <AutoComplete
                                            labelInValue={false}
                                            placeholder="输入地点关键词可选择suggest项"
                                            onSearch={this.handleSearch}
                                            onSelect={this.onPlaceChange}
                                            defaultActiveFirstOption={false}
                                            filterOption={false}
                                            value={ imgLocation }
                                            // onChange={this.onPlaceChange}
                                            >
                                            { children }
                                        </AutoComplete>
                                    </Col>
                                </Row>
                                <Row className="img-bulk-keyword">
                                    <Col span={6} className={ `label mt3` }>
                                        关键词语
                                    </Col>
                                    <Col span={18} className="info">
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
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={6} className={ `label mt3` }>图片用途</Col>
                                    <Col span={18} className="info">
                                        <QCRSelect
                                            editFlag={ true } purpose={purpose}
                                            onClick={this.onParamsChange}
                                        />
                                    </Col>
                                </Row>
                                {
                                    this.isAdmin()
                                    &&
                                    <Row>
                                        <Col span={6} className={ `label mt3` }>采购来源</Col>
                                        <Col span={18} className="info">
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
                                        </Col>
                                    </Row>
                                }
                                {
                                    this.isAdmin()
                                    &&
                                    <Row>
                                        <Col span={6} className={ `label mt3` }>原始作者</Col>
                                        <Col span={18} className="info">
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
                                        </Col>
                                    </Row>
                                }
                                {
                                    this.isAdmin()
                                    &&
                                    <Row>
                                        <Col span={6} className={ `label mt3` }>上传来源</Col>
                                        <Col span={18} className="info">
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
                                        </Col>
                                    </Row>
                                }
                            </div>
                        </div>
                    </div>
                </div>
                <div className="upload-edit-btn-container">
                    <Row>
                          <Col span={6}>
                              <div className={ 'exif-info' }>
                              {
                                  <Popover placement="right" content={ content } title="" trigger="hover"
                                           style={ { padding: 0 } }>
                                      <i className="icon-font-ourimg big-icon">&#xe10c;</i>
                                      <span style={ { marginLeft: '3px' } }>EXIF信息</span>
                                  </Popover>
                              }
                              </div>
                          </Col>
                          <Col span={6} offset={12}>
                              <div className="del-btn">
                                  <span onClick={this.confirm.bind(this, 'del')}><span className="icon-span">&#xf05b;</span>删除</span>
                              </div>
                          </Col>
                    </Row>
                </div>
            </div>
            <div className="upload-confirm">
                <Button type="primary" className="upload-confirm-button" disabled={!confirm} onClick={this.confirm.bind(this, 'sub')}>提交审核</Button>
                <Checkbox className="upload-confirm-checkbox" checked={this.state.confirm} onChange={this.onComfrimChange}>我已认真阅读<Link className="link" to="/help/copyright">《去哪儿图片库加入协议》</Link></Checkbox>
                <div className="hint-text">承诺对上传的素材拥有全部的知识产权，不存在版权不清晰或侵犯第三方合法权益的行为</div>
            </div>
            <Modal
                visible={visible}
                footer={null}
                title={null}
                onCancel={this.onCancel}
                width={380}
                >
                <p className="edit-confirm-content">{modalContent}</p>
                <div className="edit-confirm-btngroup">
                    <Button type="primary" onClick={this.submit} className='left-btn'>确认</Button>
                    <Button onClick={this.onModalCancel}>{modalCancelBtn}</Button>
                </div>
            </Modal>
        </section>)
    }
}
