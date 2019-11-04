import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { NAVLIST } from 'CONST/navList'
import { MATERIAL_MAP } from 'CONST/router';
import QMenu from 'COMPONENT/qMenu';
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname
}))

@withRouter
@observer
class Material extends Component {
    render() {
        return (
            <QMenu className="material-page" routerMap={MATERIAL_MAP} menuMap={NAVLIST}/>
        );
    }
}

export default Material;
