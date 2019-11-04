import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {withRouter} from "react-router-dom";
import CopyrightFile from 'COMPONENT/copyrightFile';

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    userInfo: state.store.global.userInfo
}))

@withRouter
@observer
class MyFile extends Component {
    render() {
        let { auth_date } = this.props.userInfo;
        return (
            <div className="my-file-page content">
                <CopyrightFile authDate={auth_date}/>
            </div>
        );
    }
}

export default MyFile;
