import React, { Component } from 'react'
import { Layout } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
const { Footer } = Layout;
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash
}))
@withRouter
@observer
class QFooter extends Component {

    render() {
        return (
            <Footer className="q-footer">
                Copyright © 2004-2019 去哪儿 版权所有      京ICP备11017824号-7 京ICP证130164号
            </Footer>
        )
    }
}

export default QFooter;