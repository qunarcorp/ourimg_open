import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import EditComponent from './edit'


@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash
}))
@withRouter
@observer
class Edit extends Component {

    render() {
        return (
            <section>
                <EditComponent></EditComponent>
            </section>
        )
    }
}

export default Edit;
