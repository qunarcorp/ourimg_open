import { observable, action, runInAction } from 'mobx';
import { componentApi } from 'CONST/api';
import { message } from 'antd';

export default class Component {
    @observable showRepeatModal = false;

    @action.bound
    openRepeatModal() {
        this.showRepeatModal = true;
    }

    @action.bound
    closeRepeatModal() {
        this.showRepeatModal = false;
    }
}