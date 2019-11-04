import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    userInfo: state.store.global.userInfo
}))
@withRouter
@observer
class CopyrightFile extends Component {

    getTime = (authDate) => {
        let day = authDate ? new Date(authDate) : new Date();
        return `${day.getFullYear()}年${day.getMonth() + 1}月${day.getDate()}日`;
    }

    render() {
        let { authDate, userInfo } = this.props;
        let { realName, userName } = userInfo;
        return (
            <div className="q-copyright-file">
                <h1 className="copyright-title">著作权统一授权声明书</h1>
                <div className="copyright-user">
                    <p className="copyright-info">声明人：<span className="hightlight">{realName}</span></p>
                    <p className="copyright-info">声明日期：<span className="hightlight">{this.getTime(authDate)}</span></p>
                </div>
            </div>
        )
    }
}

export default CopyrightFile;
