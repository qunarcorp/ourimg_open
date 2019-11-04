import React, { Component } from 'react'
import { Spin, Icon } from 'antd';

const antIcon = <Icon type="loading" style={{ fontSize: 24 }} spin />;

class QImgModal extends Component {

    state = {
        loading: true
    }

    componentWillReceiveProps(nextprops) {
        if (nextprops.url !== this.props.url) {
            this.setState({
                loading: true
            });
        }
    }

    stopLoading = () => {
        this.setState({
            loading: false
        });
    }

    render() {
        let { loading } = this.state;
        let { url, visible } = this.props;
        return (
            <div className={`q-img-modal ${visible ? '' : 'hidden'}`} onClick={()=>this.props.onClose({visible: false, imgUrl: ''})}>
                {
                    loading && <Spin indicator={antIcon} />
                }
                <div className={`modal-content ${loading ? 'hidden' : ''}`} onClick={(e)=>e.stopPropagation()}>
                    <img src={url} onLoad={this.stopLoading} className="img-element"/>
                    <i className="icon-font-ourimg close-btn" onClick={()=>this.props.onClose({visible: false, imgUrl: ''})}>&#xe4d6;</i>
                </div>
            </div>
        )
    }
}

export default QImgModal;