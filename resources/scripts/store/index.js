import { observable } from 'mobx';
import Global from './global';
import Component from './component';
import Google from './google'
import Home from './home';
import List from './list';
import Detail from './detail';
import User from './user';
import Cart from './cart';
import MySerial from './mySerials';
import MyUpload from './myUpload';
import MyMessage from './myMessage';
import ImgAudit from './imgAudit';
import AuthManage from './authManage';
import StoreManage from './storeManage';
import RepeatModal from './repeatModal';
import Credits from './credits';
import ActiveManage from './activeManage';
import Rank from './rank';
import Statistics from './statistics';

export default class Store {
    @observable global = new Global();
    @observable google = new Google();
    @observable component = new Component();
    @observable home = new Home();
    @observable list = new List();
    @observable detail = new Detail();
    @observable user = new User();
    @observable cart = new Cart();
    @observable mySerial = new MySerial();
    @observable myUpload = new MyUpload();
    @observable myMessage = new MyMessage();
    @observable imgAudit = new ImgAudit();
    @observable authManage = new AuthManage();
    @observable repeatModal = new RepeatModal();
    @observable storeManage = new StoreManage();
    @observable credits = new Credits();
    @observable activeManage = new ActiveManage();
    @observable rank = new Rank();
    @observable statistics = new Statistics();
}
