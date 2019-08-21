require('jquery');
//import Resource from './vue-resource';
// import Draggable from './vue-draggable';
//
// export default {
//     Vuex,
//     Resource,
//     Draggable
// }

var x = function (...args) {
    console.log(args)
};

const stream = (url, options) => x(url, {...options, stream: true});