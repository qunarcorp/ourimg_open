import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import { DatePicker } from 'antd';
import moment from 'moment';

const RangePicker = DatePicker.RangePicker;

class FmtRangePicker extends Component {
    constructor(props) {
        super(props);
    }
    render() {
        const { placeholder = '' , className = '', value = '', onChange } = this.props;
        return (
            <RangePicker
                className={className}
                placeholder={placeholder}
                value={value && value.length > 0 ? [moment(value[0]), moment(value[1])] : null}
                onChange={val => {
                    const time = val.length > 0 ? [val[0].format('YYYY-MM-DD'), val[1].format('YYYY-MM-DD')] : '';
                    onChange(time)
                }}
            />
        )
    }
}
export default FmtRangePicker;
