import React, { Component } from 'react'
import { Button, Modal } from 'antd';
import { Link } from 'react-router-dom';
class QImgInfo extends Component {
    state = {
    }

    render() {
        // let { eid, small_img, big_img, title, praise, download, user_praised,
        //     ext, user_favorited, user_shopcart} = this.props.data;
        // let { isLogin, isMyUser } = this.props;
        // let edFlag;
        // edFlag = (isLogin && isMyUser)
        return (
            <div className="q-img-info">
                <div className={'q-img-info-content'}>
                    <div>icon</div>
                    <div>
                        <div className={''}>
                            <img className={'info-img'} src="/img/48c372788e85a116dfda8620762d0633.jpg" alt=""/>
                        </div>
                        <span>EXIF信息</span>
                    </div>
                    <div>info</div>
                </div>
                <div className={'q-img-info-action'}>
                    按钮
                </div>
            </div>
        )
    }
}

export default QImgInfo;
