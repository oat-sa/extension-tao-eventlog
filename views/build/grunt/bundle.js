module.exports = function(grunt) {
    'use strict';

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    var paths = {
        'tao' : root + '/tao/views/js',
        'taoEventLog' : root + '/taoEventLog/views/js',
        'taoEventLogCss' : root + '/taoEventLog/views/css'
    };

    /**
     * Remove bundled and bundling files
     */
    clean.taoeventlogbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.taoeventlogbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : paths,
            modules : [{
                name: 'taoEventLog/controller/routes',
                include : ext.getExtensionsControllers(['taoEventLog']),
                exclude : ['mathJax'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taoeventlogbundle = {
        files: [
            { src: [ out + '/taoEventLog/controller/routes.js'],  dest: root + '/taoEventLog/views/js/controllers.min.js' },
            { src: [ out + '/taoEventLog/controller/routes.js.map'],  dest: root + '/taoEventLog/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('copy', copy);
    grunt.config('requirejs', requirejs);

    // bundle task
    grunt.registerTask('taoeventlogbundle', ['clean:taoeventlogbundle', 'requirejs:taoeventlogbundle', 'copy:taoeventlogbundle']);
};
