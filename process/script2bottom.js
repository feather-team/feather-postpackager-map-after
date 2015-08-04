'use strict';

var STATIC_MODE = feather.config.get('staticMode');
var REG = /<script[\s\S]*?feather-script2bottom\b[\s\S]*?<\/script>/g;

module.exports = function(ret, conf, setting, opt){
    var debug = opt.dest == 'preview', suffix = '.' + feather.config.get('template.suffix');

    feather.util.map(ret.src, function(subpath, file){
        if(file.isHtmlLike){
            var content = file.getContent(), stack = [];

            content = content.replace(REG, function(all){
                if(STATIC_MODE){
                    stack.push(all);
                    return '';
                }else{
                    return "<?php ob_start();?>" 
                    + all 
                    + "<?php "
                    + "if(!$FEATHER_SCRIPT2BOTTOMS = $this->get('FEATHER_SCRIPT2BOTTOMS')){"
                    + "$FEATHER_SCRIPT2BOTTOMS = array();"
                    + "}"
                    + "$FEATHER_SCRIPT2BOTTOMS[] = ob_get_contents();ob_end_clean();"
                    + "$this->set('FEATHER_SCRIPT2BOTTOMS', $FEATHER_SCRIPT2BOTTOMS);"
                    + "?>";
                }
            });


            if(STATIC_MODE){
                if(!file.isPageletLike){
                    if(/<\/body>/i.test(content)){
                        content = content.replace(/<\/body>/i, function(){
                            return stack.join('') + '</body>';
                        });
                    }else{
                        content += stack.join('');
                    }
                }else{
                    content += stack.join('');
                }
            }else{
                if(file.isPageletLike){
                    content += "<!--FEATHER STATIC2BOTTOM--><?php $this->load('/component/resource/usescript" + suffix + "', array('inline' => $this->get('FEATHER_SCRIPT2BOTTOMS')));?><!--FEATHER STATIC POSITION END-->";
                }else{
                    content = content.replace(/<!--FEATHER STATIC POSITION:BOTTOM-->[\s\S]*?<!--FEATHER STATIC POSITION END-->/i, function(all){
                        return all + [
                            "<!--FEATHER STATIC2BOTTOM--><?php " + (debug ? " if(!$this->get('FEATHER_SCRIPT2BOTTOMS_LOADED')){" : ""),
                            "$this->load('/component/resource/usescript" + suffix + "', array('inline' => $this->get('FEATHER_SCRIPT2BOTTOMS')));",
                            (debug ? "$this->set('FEATHER_SCRIPT2BOTTOMS_LOADED', true);}" : "") + "?>",
                            "<!--FEATHER_SCRIPT2BOTTOMS END-->"
                        ].join("");
                    });
                }
            }

            file.setContent(content);
        }
    });
};