import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { withRouter } from 'react-router-dom';
import { Modal, Button } from "antd";

import { ownerMessageMap } from "CONST/map";

@inject(state => ({
    duplicateImg: state.store.component.duplicateImg,
    userName: state.store.global.userInfo.userName
}))
@withRouter
@observer
export default class RepeatModal extends Component {
    clickButton(audit_state, img_eid, is_login_user) {
        if(audit_state === "2") {
            window.open(`/#/detail?eid=${img_eid}`);
        }else if(is_login_user) {
            const { userName } = this.props;
            window.open(`/#/material/myUpload?uid=${userName}&type=${audit_state}`);
        }
    }

    getMessage(is_login_user, realname, audit_state) {
        // "2" 已通过	
        return is_login_user || audit_state === "2"
            ? ownerMessageMap[audit_state]
            : `作者: ${realname}已上传过`;
    }

    render() {
        const { duplicateImg, clickJumpToRepeat } = this.props;
        // debugger
        return (
            <Modal
                title="上传提醒"
                className="q-modal upload-modal-container"
                visible={true}
                footer={null}
                onCancel={clickJumpToRepeat}
                width='600'
            >
                <p className="warning">以下图片存在问题，请按要求上传～</p>
                <p className="mention-text m-t-2">
                    【重复上传】：图片已存在，重复上传；
                </p>
                <p className="mention-text">
                    【尺寸过小】：图片宽度小于2000px；
                </p>
                <div className="img-detail-container">
                    {duplicateImg.map(imgData => {
                        const {
                            small_img120 = "",
                            audit_state = "",
                            img_eid = "",
                            file_name = "",
                            userinfo = {}
                        } = imgData;
                        const { is_login_user = "", realname = "" } = userinfo;
                        // is_login_user 有这个值status 是108 否则是103
                        return (
                            <div
                                className="img-detail"
                                key={`${small_img120}${userinfo}${img_eid}`}
                            >
                                    <img src={small_img120} className="img" />
                                    <div className="img-icon">{is_login_user ? "重复上传" : "尺寸过小"}</div>
                                <div className="file-name">{file_name}</div>
                                {is_login_user && <div
                                    onClick={() =>
                                        this.clickButton(audit_state, img_eid, is_login_user)
                                    }
                                    className={`description-link ${is_login_user || audit_state === "2" ? 'link' : ''}`}
                                >
                                    {this.getMessage(
                                        is_login_user,
                                        realname,
                                        audit_state
                                    )}
                                </div>}
                            </div>
                        );
                    })}
                </div>
                <div className="footer">
                    <Button className="footer-button" type="primary" onClick={clickJumpToRepeat}>
                        跳过问题图片
                    </Button>
                    <div className="message">
                        <i className="icon-font-ourimg">&#xe159;</i>
                        <span className="message-warning">
                            如您对以上图片的版权归属存在异议,请邮件反馈至
                        </span>
                    </div>
                </div>
            </Modal>
        );
    }
}
