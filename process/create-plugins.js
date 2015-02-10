module.exports = function(ret, conf, setting, opt){
	var project = feather.project.getProjectPath(), vendor = __dirname + '/../vendor/develop';

	['feather_view_plugin_autoload_static.php'].forEach(function(file){
		var path = '/plugins/' + file;

		var file = feather.file.wrap(feather.project.getProjectPath() + path);
	    file.setContent(feather.file.wrap(vendor + path).getContent());
	    ret.pkg[file.subpath] = file;
	});
};