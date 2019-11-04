import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import { CSSTransitionGroup } from 'react-transition-group';

const animDura = 200;

class LazyImage extends Component {
  static propTypes = {
    targetSrc: PropTypes.string.isRequired,
    defaultSrc: PropTypes.string.isRequired,
    height: PropTypes.number.isRequired, // image height
    offset: PropTypes.number,
  };
  static defaultProps = {
    offset: 100,
    className: '',
  };
  constructor(props) {
    super(props);
    this.state = {
      visible: false
    };
    this.onScroll = this.onScroll.bind(this);
    this.isRequesting = false;
  }
  componentDidMount() {
    window.addEventListener("scroll", this.onScroll);
    this.onScroll(); // in case page do not scroll
  }
  get isInViewport() {
    // get element position in viewport
    const node = ReactDOM.findDOMNode(this.ele);
    const rect = node.getBoundingClientRect();
    // get viewport height and width
    const viewportHeight =
      window.innerHeight || document.documentElement.clientHeight;
    const viewportWidth =
      window.innerWidth || document.documentElement.clientWidth;
    // check if the element is in the viewport (or near to them)
    return (
      rect.bottom >= 0 - this.props.offset &&
      rect.right >= 0 - this.props.offset &&
      rect.top < viewportHeight + this.props.offset &&
      rect.left < viewportWidth + this.props.offset
    );
  }
  onScroll() {
    if (!this.isRequesting && this.isInViewport) {
        this.fetch();
    }
  }
  fetch() {
    this.isRequesting = true;
    window.removeEventListener('scroll', this.onScroll);
    const { targetSrc } = this.props;
    const img = new Image();
    img.src = targetSrc;
    img.onload = () => {
      this.setState({
        visible: true,
      });
    };
  }

  render() {
    const { targetSrc, defaultSrc, className, height, style } = this.props;
    const { visible } = this.state;
    const style = {
        ...style,
      height,
    }
    return (
        <CSSTransitionGroup
          transitionName="fade"
          transitionEnterTimeout={animDura}
          transitionLeaveTimeout={animDura}
          ref={ele => this.ele = ele}
          className='lazyimage-holder'
          component='div'
          style={style}
        >
          {!visible ? <img src={defaultSrc} alt='default icon' className='lazyimage-img' className={className}/> : null}
          {visible ? <img src={targetSrc} alt='icon' className='lazyimage-img' className={className} /> : null}
        </CSSTransitionGroup>
    );
  }
}


export default LazyImage;