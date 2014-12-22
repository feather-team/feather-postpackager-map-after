/*
DEV环境 FEATHER 结合模版引擎 进行本地调试所需要的资源生成
*/

'use strict';

module.exports = function(ret, conf, setting, opt){
    if(opt.dest != 'preview'){
        feather.util.map(ret.src, function(subpath, file){
            if(/^\/(?:feather_|test\/)/.test(subpath)){
                file.release = false;
            }
        });

        return;
    }

    var modulename = feather.config.get('project.modulename'), ns = feather.config.get('project.ns');
    var www = feather.project.getTempPath('www'), php = www + '/php', vendor = __dirname + '/../vendor/develop';
    
    if(!feather.config.get('staticMode')){
        var root = feather.project.getProjectPath();

        if(modulename){
            if(modulename != 'common'){
                feather.util.del(www + '/map/' + ns, null, /common\.php/);
            }
        }else{
            feather.util.del(www + '/map/' + ns);
        }
    }

    //生成conf
    var hash = {
        domain: opt.domain,
        ns: ns,
        staticMode: feather.config.get('staticMode'),
        template: {
            suffix: feather.config.get('template.suffix')
        }
    };

    feather.util.write(php + '/tmp/feather_conf.php', '<?php return ' + feather.util.toPhpArray(hash) + ';');
    feather.util.write(www + '/index.php', feather.file.wrap(vendor + '/index.php').getContent());

    //生成本地预览所需要的文件
    [   
        '/lib/Feather_View.class.php',
        '/lib/Feather_View_Plugin.class.php',
        '/lib/MagicData.class.php',
        '/plugins/feather_view_plugin_autoload_static.php',
        '/plugins/feather_view_plugin_static_position.php'
    ].forEach(function(path){
        feather.util.write(php + path, feather.file.wrap(vendor + path).getContent());
    });
};