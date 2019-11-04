import React, { Component } from 'react'
import { Input, Button } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash
}))
@withRouter
@observer
class QNumberInput extends Component {

    numberFilterChange = (e) => {
        let { value } = e.target;
        const reg = /^([1-9][0-9]*)?$/;
        if (value === '') {
            value = 1;
        }
        if ((!Number.isNaN(value) && reg.test(value))) {
            this.props.onChange(value);
        }
    }

    render() {
        const { value, onChange, min, max, desc } = this.props;
        return (
            <div className="q-num-input">
                <Input
                    className="num-input text-center"
                    value={ value }
                    onChange={ this.numberFilterChange }
                    addonBefore={
                        <Button
                            type="primary"
                            className="num-btn"
                            disabled={ value <= min }
                            onClick={ () => onChange(parseInt(value) - 1) }
                        >-</Button> }
                    addonAfter={
                        <Button
                            type="primary"
                            className="num-btn"
                            disabled={ value >= max }
                            onClick={ () => onChange(parseInt(value) + 1) }
                        >+</Button> }/>

                {
                    desc ? <span className={ 'text-gray m-l' }> { desc }</span>
                        : null
                }
            </div>
        )
    }
}

export default QNumberInput;
