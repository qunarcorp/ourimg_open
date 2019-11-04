function getParams (obj) {
    let params = '?';
    Object.keys(obj || {}).forEach((item) => {
      params += [item, '=', obj[item], '&'].join('');
    });
    return params.slice(0, -1);
  }
  function deepCopy (obj) {
    return JSON.parse(JSON.stringify(obj));
  }
  const cookie = {
    set:function  (cname, cvalue, exdays) {
      var d = new Date();
      d.setTime(d.getTime() + (exdays*24*60*60*1000));
      var expires = "expires="+d.toUTCString();
      document.cookie = cname + "=" + cvalue + "; " + expires;
    },
    get:function (cname) {
      var name = cname + "=";
      var ca = document.cookie.split(';');
      for(var i=0; i<ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0)==' '){
            c = c.substring(1);
          }
          if (c.indexOf(name) != -1){
            return c.substring(name.length, c.length);
          }
      }
      return "";
    }
  };
  export default {
    cookie,
    getParams,
    deepCopy
  };
  