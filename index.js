'use strict';

module.exports = function(ret, conf, setting, opt){
    if(feather.config.get('__cwd')){
        global.process.chdir(feather.config.get('__cwd'));
    }
    

    //process start
    var process = ['script2bottom'];

    if(!feather.config.get('staticMode')){
        process.push('create-static-template');
        process.push('create-plugins');
    }

    process.push('create-develop');

    process.forEach(function(process){
        require('./process/' + process + '.js')(ret, conf, setting, opt); 
    });
};