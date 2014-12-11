module.exports = function(ret, conf, setting, opt){
	var modulename = feather.config.get('modulename');

	if(modulename == 'common' || !modulename){
        [
            '/component/resource/usescript.tpl', 
            '/component/resource/usestyle.tpl'
        ].forEach(function(path){
            var tmpPath = path.replace(/\.tpl$/, '.' + feather.config.get('template.suffix'));
            var file = new feather.file(feather.project.getProjectPath() + tmpPath);
            file.setContent(feather.file.wrap(__dirname + '/../vendor/' + path).getContent());
            ret.pkg[tmpPath] = file;
        });
    }
};