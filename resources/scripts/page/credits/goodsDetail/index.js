import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { Input, Button, Modal, message } from 'antd';
import QImgModal from 'COMPONENT/qImgModal';
import QNumberInput from 'COMPONENT/qNumberInput';
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    userInfo: state.store.global.userInfo,
    checkLogin: state.store.global.checkLogin,
    goodsInfo: state.store.credits.goodsInfo,
    getGoodsdetail: state.store.credits.getGoodsdetail,
    exchangeGoods: state.store.credits.exchangeGoods
}))

@withRouter
@observer
class GoodsDetail extends Component {

    state = {
        imgVisible: false,
        master_img: '',
        big_img: '',
        confirmVisible: false,
        addressVisible: false,
        resultVisible: false,
        resultTitle: '',
        resultContent: '',
        leftCredits: '',
        exchangeNum: 1,
        userAddress: '',
        userMobile: ''
    }

    componentDidMount() {
        let nextparams = new URLSearchParams(this.props.history.location.search);
        let eid = nextparams.get("id");
        this.props.getGoodsdetail({eid}, (data) => {
            this.setState({
                master_img: data.img_url[0].middle,
                big_img: data.img_url[0].big
            });
        });
    }

    handleNumChange = (value) => {
        this.setState({
            exchangeNum: value
        });
    }

    showBigImg = (visible) => {
        this.setState({
            imgVisible: visible
        });
    }

    showConfirmModal = () => {
        this.setState({
            confirmVisible: true
        });
    }

    onConfirmCancel = () => {
        this.setState({
            confirmVisible: false
        });
    }

    onConfirm = () => {
        this.setState({
            confirmVisible: false,
            addressVisible: true,
            userAddress: '',
            userMobile: ''
        });
    }

    onAddressCancel = () => {
        this.setState({
            addressVisible: false
        });
    }

    onSubmit = () => {
        const { userAddress, userMobile, exchangeNum } = this.state;
        if (!userAddress) {
            message.warning('请填写地址！');
            return;
        } else if (!userMobile) {
            message.warning('请填写联系方式！');
            return;
        }

        const { eid, points } = this.props.goodsInfo;
        let params = {
            address: userAddress,
            mobile: userMobile,
            product_points: points,
            product_num: exchangeNum,
            product_eid: eid
        };
        this.props.exchangeGoods(params, (res) => {
            if (res.status === 0) {
                this.setState({
                    addressVisible: false,
                    resultVisible: true,
                    resultTitle: '兑换成功',
                    resultContent: '兑换商品将在1-3个工作日与您联系收取（如遇节假日顺延）',
                    leftCredits: res.data.current_points
                });
                this.props.checkLogin();
            } else {
                this.setState({
                    addressVisible: false,
                    resultVisible: true,
                    resultTitle: '兑换失败',
                    resultContent: res.message,
                    leftCredits: this.props.userInfo.points_info.current_points
                });
            }
        });
    }

    onResultCancel = () => {
        this.setState({
            resultVisible: false
        });
        if (this.state.resultTitle === '兑换成功') {
            this.props.history.push('/credits/myCredits?tab=EXCHANGE');
        }
    }

    getGoodsStatus = () => {
        const myPoints = this.props.userInfo.points_info.current_points;
        const { on_sale, points, remain_stock }  = this.props.goodsInfo;
        if (on_sale === '') {
            return '未上架';
        } else if (on_sale === false) {
            return '已下架';
        } else if (remain_stock <= 0) {
            return '已兑完';
        } else if (on_sale === true && parseInt(myPoints) < parseInt(points)) {
            return '积分不足';
        } else {
            return '马上兑换';
        }
    }

    render() {
        const { title, description, img_url, price, points, detail_title, detail, on_sale,
            remain_stock, detail_img, exchange_end_time, exchange_description } = this.props.goodsInfo;
        let { imgVisible, big_img, confirmVisible, addressVisible, resultVisible,
            resultTitle, resultContent, exchangeNum, userAddress, userMobile,
            master_img, leftCredits } = this.state;
        const myPoints = this.props.userInfo.points_info.current_points;
        const myMaxNum = parseInt(myPoints/points);
        const maxNum = Math.min(remain_stock, myMaxNum);
        return (
            <div className="credits-content goods-detail-content">
                <div className="goods-info">
                    <div className="goods-imgs">
                        <div className="hover-btn">
                            <Button size="small" onClick={()=>this.showBigImg(true)}>
                                <i className="icon-font-ourimg">&#xf407;</i>
                            </Button>
                        </div>
                        <img className="goods-img" src={master_img}/>
                        <div className="img-list">
                        {
                            img_url.map(item =>
                                <img
                                    className={`small-img ${this.state.master_img === item.middle ? 'selected' : ''}`} src={item.small}
                                    onClick={() => this.setState({
                                        master_img: item.middle,
                                        big_img: item.big
                                    })}/>
                            )
                        }
                        </div>
                    </div>
                    <div className="goods-intro">
                        <div className="goods-title">{title}</div>
                        <div className="goods-subtitle">{description}</div>
                        <div className="goods-price">
                            <div className="price">参考售价：￥{price}</div>
                            <div className="goods-credits">
                                <span className="credits">{points}</span>积分
                            </div>
                        </div>
                        <div className="goods-number">
                            <div className="label">数量：</div>
                            <QNumberInput
                                min={1}
                                max={maxNum}
                                value={exchangeNum}
                                onChange={this.handleNumChange}/>
                            <div className="hint">库存{remain_stock}件</div>
                            {
                                exchangeNum >= myMaxNum &&
                                <div className="hint highlight">当前积分仅可兑换{myMaxNum}件</div>
                            }
                        </div>
                        {
                            exchange_end_time &&
                            <div className="goods-time">
                                <div className="label">期限：</div>
                                <div className="value">本商品{exchange_end_time}日前可兑</div>
                            </div>
                        }
                        <Button
                            disabled={!(on_sale === true && maxNum > 0)}
                            className="link-btn" type="primary"
                            onClick={this.showConfirmModal}>
                            <i className="icon-font-ourimg">&#xe4fc;</i>
                            {this.getGoodsStatus()}
                        </Button>
                    </div>
                </div>
                <div className="goods-detail">
                    <div className="detail-title">· 商品详情</div>
                    <div className="detail-main-desc">{detail_title}</div>
                    <div className="detail-desc">{detail ? detail.split("\n").map(text => <p>{text}</p>):''}</div>
                    {
                        detail_img.map(item => <img className="detail-img" src={item}/>)
                    }
                    <div className="exchange-desc">
                        <div className="exchange-detail-title">
                            <div className="inner-box">兑换说明</div>
                        </div>
                        <div className="exchange-detail-desc">
                            {exchange_description ? exchange_description.split("\n").map(text => <p>{text}</p>):''}
                        </div>
                    </div>
                </div>
                <QImgModal visible={imgVisible} url={big_img} onClose={()=>this.showBigImg(false)}/>
                <Modal
                    className="q-modal goods-info-modal"
                    visible={confirmVisible}
                    footer={null}
                    title="确认兑换商品信息"
                    onCancel={this.onConfirmCancel}
                    width={510}>
                        <div className="edit-confirm-content">
                            <img className="goods-img" src={this.props.goodsInfo.master_img}/>
                            <div className="goods-info">
                                <div className="info-row">
                                    <span className="label">商品名称：</span>
                                    <span className="text">{title}</span>
                                </div>
                                <div className="info-row">
                                    <span className="label">兑换数量：</span>
                                    <span className="text">x{exchangeNum}</span>
                                </div>
                                <div className="info-row">
                                    <span className="label">消耗积分：</span>
                                    <span className="text">{parseInt(exchangeNum) * parseInt(points)}积分</span>
                                </div>
                            </div>
                        </div>
                        <div className="edit-confirm-btngroup">
                            <Button type="primary" onClick={this.onConfirm} className='left-btn'>确认</Button>
                            <Button onClick={this.onConfirmCancel}>取消</Button>
                        </div>
                </Modal>
                <Modal
                    className="q-modal my-address-modal"
                    visible={addressVisible}
                    footer={null}
                    title="填写收货信息"
                    onCancel={this.onAddressCancel}
                    width={510}>
                        <div className="edit-confirm-content">
                            <div className="address-info">
                                <div className="info-row">
                                    <span className="label">姓名</span>
                                    <span className="text">{this.props.userInfo.realName}</span>
                                </div>
                                <div className="info-row">
                                    <span className="label">收货地址</span>
                                    <Input
                                        className="input"
                                        placeholder="填写详细收货地址"
                                        value={userAddress}
                                        onChange={(e)=>this.setState({userAddress: e.target.value})}/>
                                </div>
                                <div className="info-row">
                                    <span className="label">联系方式</span>
                                    <Input
                                        className="input"
                                        placeholder="填写正确联系方式"
                                        value={userMobile}
                                        onChange={(e)=>this.setState({userMobile: e.target.value})}/>
                                </div>
                            </div>
                        </div>
                        <div className="edit-confirm-btngroup">
                            <Button
                                type="primary" onClick={this.onSubmit}
                                className='confirm-btn'>确认
                            </Button>
                        </div>
                </Modal>
                <Modal
                    className="q-modal exchange-result-modal"
                    visible={resultVisible}
                    footer={null}
                    title={null}
                    onCancel={this.onResultCancel}
                    width={380}>
                        <div className="edit-confirm-content">
                            <div className="result-title">{resultTitle}</div>
                            <div className="result-text">{resultContent}</div>
                            <div className="result-text">剩余积分：{leftCredits}分</div>
                        </div>
                        <div className="edit-confirm-btngroup">
                            <Button type="primary" onClick={this.onResultCancel}>确认</Button>
                        </div>
                </Modal>
            </div>
        );
    }
}

export default GoodsDetail;
