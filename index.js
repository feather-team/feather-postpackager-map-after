'use strict';

module.exports = function(ret, conf, setting, opt){
    //process start
    var process = [];

    if(!feather.config.get('staticMode')){
        process.push('create-static-template');
        process.push('create-plugins');
    }

    process.push('create-develop');

    process.forEach(function(process){
        require('./process/' + process + '.js')(ret, conf, setting, opt); 
    });
};