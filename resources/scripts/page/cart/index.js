import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import CartCard from './cartCard';
import CartImgModal from './cartImgModal';
import QBlank from 'COMPONENT/qBlank';
import { Button, Modal, Checkbox, message, Tabs } from 'antd';

const TabPane = Tabs.TabPane;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    getList: state.store.cart.getList,
    dataList: state.store.cart.list,
    selectedItems: state.store.cart.selectedItems,
    deleteItems: state.store.cart.deleteItems,
    downloadImg: state.store.cart.downloadImg,
    dataCount: state.store.cart.dataCount,
    getCartCount: state.store.global.getCartCount,
    detailData: state.store.cart.detailData,
    getImgDetail: state.store.cart.getImgDetail,
    allCheck: state.store.cart.allCheck,
    checkAllSelected: state.store.cart.checkAllSelected,
    clearAllSelected: state.store.cart.clearAllSelected,
    onlyEditPurposeVisible: state.store.cart.onlyEditPurposeVisible,
    onEditPurposeCancel: state.store.cart.onEditPurposeCancel,
    downloadZip: state.store.cart.downloadZip,
    downloadData: state.store.cart.downloadData,
}))

@withRouter
@observer
export default class UserCart extends Component {

    state = {
        delVisible: false,
        delItems: '',
        tabKey: '1',
        imgVisible: false,
        downloadLoading: false
    }

    componentDidMount() {
        this.props.getList({ big_type: this.state.tabKey });
    }

    onTabChange = (key) => {
        this.setState({
            tabKey: key
        });
        this.props.getList({ big_type: key });
        this.props.clearAllSelected();
    }

    getTabHead = (type) => {
        // let mockNumObj = {
        //     '照片': 12,
        //     '矢量图': 13,
        //     'PSD': 14,
        //     'PPT模板': 15
        // }
        switch (type) {
            case '照片':
                return <span className="tab-title">照片<span
                    className="color-primary">({ this.props.dataCount[1].num })</span></span>
            case '矢量图':
                return <span className="tab-title">矢量图<span
                    className="color-primary">({ this.props.dataCount[2].num })</span></span>
            case 'PSD':
                return <span className="tab-title">PSD<span
                    className="color-primary">({ this.props.dataCount[3].num })</span></span>
            case 'PPT模板':
                return <span className="tab-title">PPT模板<span
                    className="color-primary">({ this.props.dataCount[4].num })</span></span>
        }
    }

    download = () => {
        const self = this;
        if (this.props.selectedItems.length === 0) {
            message.warning('请选择需要下载的素材');
            return;
        }
        if (this.state.downloadLoading) {
            return;
        }
        let noPermisson = '';
        this.props.dataList.map(item => {
            if (self.props.selectedItems.indexOf(item.sc_id) >= 0 && !item.download_permission) {
                noPermisson += item.title + '、'
            }
        })
        if (noPermisson) {
            Modal.warning({
                // title: `${noPermisson}等没有下载权限`,
                title: `没有下载权限`,
                content: (
                    <div>
                        <p>申请开通图片下载权限</p>
                        <p>请邮件申请，注明申请原因</p>
                    </div>
                ),
                onOk() {
                }
            });
            return
        }
        let params = {
            eids: this.props.selectedItems.join(',')
        };
        this.setState({
            downloadLoading: true
        });
        this.props.downloadImg(params, () => {
            this.props.getCartCount();
            this.setState({
                downloadLoading: false
            });
            this.props.clearAllSelected();
        });
    }

    onDelItems = (ids) => {
        if (!ids) {
            message.warning('请选择需要删除的素材');
            return;
        }
        this.setState({
            delVisible: true,
            delItems: ids
        })
    }

    onDelCancel = () => {
        this.setState({
            delVisible: false
        })
    }

    showImgDetail = (eid) => {
        this.props.getImgDetail({ eid });
        this.setState({
            imgVisible: true
        });
    }

    closeImgDetail = () => {
        this.setState({
            imgVisible: false
        });
    }

    render() {
        let { delItems, delVisible, imgVisible, tabKey } = this.state;
        let { dataList, selectedItems, deleteItems, detailData, allCheck, checkAllSelected, onlyEditPurposeVisible, downloadData } = this.props;

        return (<section className="user-cart-container">
            <div className="user-cart">
                <Tabs defaultActiveKey="1" onChange={ this.onTabChange }>
                    <TabPane tab={ this.getTabHead('照片') } key="1">
                        {
                            dataList.length === 0 ?
                                <QBlank type="cart"/>
                                : <div className="user-cart-list-container">
                                    {
                                        dataList.map(item =>
                                            <div key={ item.eid }>
                                                <CartCard
                                                    clickImg={ this.showImgDetail }
                                                    onDel={ this.onDelItems }
                                                    data={ item }>
                                                </CartCard>
                                            </div>)
                                    }
                                </div>
                        }
                    </TabPane>
                    <TabPane tab={ this.getTabHead('矢量图') } key="2">
                        { /* {
                            dataList.length === 0 ?  */ }
                        <QBlank type="loading"/>
                        { /* : ''
                        } */ }
                    </TabPane>
                    <TabPane tab={ this.getTabHead('PSD') } key="3">
                        { /* {
                            dataList.length === 0 ?  */ }
                        <QBlank type="loading"/>
                        { /* : ''
                        } */ }
                    </TabPane>
                    <TabPane tab={ this.getTabHead('PPT模板') } key="4">
                        { /* {
                            dataList.length === 0 ?  */ }
                        <QBlank type="loading"/>
                        { /* : ''
                        } */ }
                    </TabPane>
                </Tabs>
                {
                    dataList.length !== 0 && tabKey == '1' &&
                    <div className="user-cart-toolbar">
                        <div className="cart-checkbox-all">
                            <Checkbox
                                checked={ allCheck }
                                onChange={ checkAllSelected }
                            >全选
                            </Checkbox>
                        </div>
                        <span>总计：<span
                            className="user-cart-toolbar-count">{ selectedItems.length }/{ dataList.length }</span></span>
                        <Button type="primary" onClick={ this.download }>确认下载</Button>
                        <Button className="del-btn"
                                onClick={ () => this.onDelItems(selectedItems.join(',')) }>删除</Button>
                    </div>
                }
            </div>
            <Modal
                visible={ delVisible }
                footer={ null }
                title={ null }
                onCancel={ this.onDelCancel }
                width={ 380 }>
                <p className="edit-confirm-content">确认删除所选素材吗？</p>
                <div className="edit-confirm-btngroup">
                    <Button type="primary" onClick={ () => deleteItems({ ids: delItems }, () => {
                        this.setState({ delVisible: false });
                        this.props.getCartCount();
                        this.props.clearAllSelected();
                    }) } className='left-btn'>确认</Button>
                    <Button onClick={ this.onDelCancel }>取消</Button>
                </div>
            </Modal>
            <Modal
                visible={ onlyEditPurposeVisible }
                footer={ null }
                title={ null }
                onCancel={ this.props.onEditPurposeCancel }
                width={ 380 }>
                <div className="edit-confirm-content link-modal">
                    <div dangerouslySetInnerHTML={{__html: downloadData.noticeMsg}}></div>
                </div>
                <div className="edit-confirm-btngroup">
                    <Button type="primary" onClick={ () => this.props.downloadZip(downloadData.downloadUrl, downloadData.callback) }>继续下载</Button>
                </div>
            </Modal>
            <CartImgModal visible={ imgVisible } data={ detailData } onClose={ this.closeImgDetail }/>
        </section>)
    }
}
