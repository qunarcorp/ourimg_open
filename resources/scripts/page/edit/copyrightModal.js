import React, { Component } from 'react'
import { Modal, Button } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import CopyrightFile from 'COMPONENT/copyrightFile';
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    setCopyrightAuth: state.store.global.setCopyrightAuth
}))
@withRouter
@observer
class CopyrightModal extends Component {

    state = {
        count: 10
    }

    componentDidMount() {
        if (this.props.visible) {
            this.setTimer();
        }
    }

    componentWillReceiveProps(nextprops) {
        if (!this.props.visible && nextprops.visible) {
            this.setState({
                count: 10
            });
            this.setTimer();
        }
    }

    handleCancel = () => {
        // if (!this.timer) {
            this.props.history.push("/");
        // }
    }

    setTimer = () => {
        this.timer = setInterval(()=>{
            let { count } = this.state;
            if (count === 0) {
                clearInterval(this.timer); 
                this.timer = null;
            } else {
                this.setState({
                    count: count -1
                });
            }
        }, 1000);
    }

    handleSubmit = () => {
        this.props.setCopyrightAuth();
    }

    render() {
        let { count } = this.state;
        let { visible } = this.props;
        return (
            <Modal
                className="q-modal q-copyright-modal"
                title="版权声明"
                visible={visible}
                footer={null}
                width={760}
                onCancel={this.handleCancel}
                >
                <div className="edit-confirm-content">
                    <CopyrightFile/>
                </div>
                <div className="edit-confirm-btngroup">
                    <Button 
                        type="primary" className="close-btn" 
                        onClick={this.handleSubmit}
                        disabled={count > 0}
                    >确认授权</Button>
                    <p className="hint-text">
                        请仔细阅读授权声明书，阅读剩余<span className="hightlight">{count}s</span>
                    </p>
                </div>
            </Modal>
        )
    }
}

export default CopyrightModal;