import React, { Component } from 'react'
import { Spin, Icon, Tag } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import QCRSelect from 'COMPONENT/qCRSelect';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    filterObj: state.store.google.filterObj,
    getFilterOption: state.store.google.getFilterOption,
    updateSearchParams: state.store.global.updateSearchParams,
    locationDetail: state.store.cart.locationDetail
}))
@withRouter
@observer
class CartImgModal extends Component {

    componentDidMount() {
        this.props.getFilterOption();
    }

    onTagClick(item) {
        // let pathname = this.props.history.location.pathname;
        this.props.updateSearchParams({
            keyword: item
        });
        // // pathname === '/list' ? '' : this.props.history.push(`/list`);
        this.props.history.push(`/list`);
    }

    clickJumpToList(key, value) {
        this.props.history.push({
            pathname: '/list',
            state:  {[key]: value}
        });
    }

    render() {
        let { visible } = this.props;
        let { title, big_img, eid, filesize, ext, purpose,
            location, width, height, small_type, keyword } = this.props.data;
            // debugger
        return (
            <div className={`q-img-modal ${visible ? '' : 'hidden'}`}>
                <div className={`modal-content`}>
                    <div className="img-detail-card">
                        <i className="icon-font-ourimg img-close-btn"
                        onClick={()=>this.props.onClose({visible: false, imgUrl: ''})}>&#xf3f3;</i>
                        <div className="container">
                            <div className="img-element-container">
                                <img src={big_img} className="img-element"/>
                            </div>
                            <div className="img-info">
                                <div className="title">{title}</div>
                                <div className="info-list">
                                    <p>图片ID：{eid}</p>
                                    <p>版权所有：<QCRSelect editFlag={false} purpose={purpose}/></p>
                                    <p onClick={()=>this.clickJumpToList('small_type', small_type)}>所属分类：<span className='detail-click-a'>{this.props.filterObj.small_type ?
                                    this.props.filterObj.small_type[small_type] : ''}</span></p>
                                    <p>体积：{filesize}</p>
                                    <p>规格：{`${width} x ${height} px`}</p>
                                    <p>格式：{ext}</p>
                                    <p>拍摄地点：
                                    {
                                        location && location.map((item,index) => <span>
                                            <span
                                                className='detail-click-a'
                                                onClick={()=>this.clickJumpToList('city', this.props.locationDetail[item])}>
                                                {item}</span>
                                            <span>{index === location.length - 1 ? '' : '/'}</span>
                                        </span>)
                                    }
                                    </p>
                                </div>
                                <div className="img-tags">
                                    <div className="tags-title">标签</div>
                                    <div className="tags">
                                    {
                                        keyword && keyword.map(item => <Tag key={item} onClick={()=>this.onTagClick(item)}>{item}</Tag>)
                                    }
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        )
    }
}

export default CartImgModal;
