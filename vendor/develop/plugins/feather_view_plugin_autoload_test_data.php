<?php
class Feather_View_Plugin_Autoload_Test_Data extends Feather_View_Plugin_Abstract{
	const TEST_FILE_SUFFIX = '.php';

	private $map = array();

	private function initMap(){
		//合并map表
		foreach((array)$this->getOption('maps') as $resource){
			$resource = require($resource);
			$this->map = array_merge($this->map, $resource['map']);
		}
	}

	//获取页面所有用到的components
	private function getComponents($path){
		$selfMap = isset($this->map[$path]) ? $this->map[$path] : array();

		if(isset($selfMap['components'])){
			$selfComponents = $selfMap['components'];

			foreach($selfComponents as $component){
				$selfComponents = array_merge($selfComponents, $this->getComponents($component));
			}
		}else{
			$selfComponents = array();
		}

		return $selfComponents;
	}

	public function exec($content, $info){
		if($info['isLoad']) return $content;

		$this->initMap();

		$testRoot = rtrim($this->getOption('data_dir'), '/') . '/';
		$fData = array();

		$path = $info['path'];
		$components = $this->getComponents($path);
		array_unshift($components, $path);

		foreach($components as $path){
			$info = pathinfo($path);
			$path = "{$testRoot}{$info['dirname']}/{$info['filename']}" . self::TEST_FILE_SUFFIX;

			if($data = @include($path)){
				$fData = array_merge($data, $fData);
			}
		}

		$this->view->set($fData);

		return $content;
	}
}