import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { HELPLIST } from 'CONST/navList'
import { HELP_MAP } from 'CONST/router';
import QMenu from 'COMPONENT/qMenu';
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname
}))

@withRouter
@observer
class Help extends Component {
    render() {
        return (
            <QMenu className="help-page" routerMap={HELP_MAP} menuMap={HELPLIST}/>
        );
    }
}

export default Help;
