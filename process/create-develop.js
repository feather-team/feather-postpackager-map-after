/*
DEV环境 FEATHER 结合模版引擎 进行本地调试所需要的资源生成
*/

'use strict';

module.exports = function(ret, conf, setting, opt){
    var staticMode = feather.config.get('staticMode');

    if(opt.dest != 'preview'){
        feather.util.map(ret.src, function(subpath, file){
            if(/^\/(?:feather_|test\/)/.test(subpath)){
                file.release = false;
            }
        });

        return;
    }

    var modulename = feather.config.get('project.modulename'), ns = feather.config.get('project.name') || 'default';
    var www = feather.project.getTempPath('www'), php = www + '/php', vendor = __dirname + '/../vendor/develop';
    var proj = www + '/proj/' + ns;
    
    if(!feather.config.get('deploy.preview')){
        feather.config.set('deploy.preview', [
            {
                from: '/static',
                //本地目录
                to: www + '/static',
                subOnly: true
            },
            {
                from: '/',
                to: proj,
                exclude: /^\/static\//,
                subOnly: true
            }
        ]);
    }

    if(!staticMode){
        var root = feather.project.getProjectPath();

        if(modulename){
            if(modulename == 'common'){
                feather.util.del(proj + '/view/map/map.php');
            }
        }else{
            feather.util.del(proj + '/view/map');
        }
    }

    //生成conf
    var hash = {
        domain: opt.domain ? true : false,
        ns: ns,
        staticMode: feather.config.get('staticMode'),
        template: {
            suffix: feather.config.get('template.suffix')
        },
        comboDebug: feather.config.get('comboDebug'),
        pack: opt.pack ? true : false
    };

    feather.util.write(proj + '/feather_conf.php', feather.util.json(hash));
    feather.util.mkdir(proj + '/cache');
    feather.util.write(www + '/index.php', feather.file.wrap(vendor + '/index.php').getContent());
    feather.util.write(www + '/c_proj', ns);

    //生成本地预览所需要的文件
    [   
        '/lib/Feather_View.class.php',
        '/lib/Feather_View_Plugin_Abstract.class.php',
        '/lib/Feather_View_Plugin_Cache_Abstract.class.php',
        '/lib/Feather_View_Plugin_Cache_File.class.php',
        '/lib/MagicData.class.php',
        '/plugins/feather_view_plugin_static_position.php',
        '/plugins/feather_view_plugin_autoload_test_data.php'
    ].forEach(function(path){
        feather.util.write(php + path, feather.file.wrap(vendor + path).getContent());
    });
};
