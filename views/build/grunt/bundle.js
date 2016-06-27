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
    clean.taoventlogbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.taoeventlogbundle = {
        options: {
            baseUrl : '../js',
            mainConfigFile : './config/requirejs.build.js',
            paths : paths,
            include : ['lib/require'].concat(ext.getExtensionsControllers(['taoEventLog'])),
            exclude : ['history'],
            name: 'taoEventLog/main',
            out : out + '/main.min.js'
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taoeventlogbundle = {
        files: [
            { src: [out + '/main.min.js'],  dest: root + '/taoEventLog/views/js/main.min.js' },
            { src: [out + '/main.min.js.map'],  dest: root + '/taoEventLog/views/js/main.min.js.map' }
        ]
    };


    grunt.config('clean', clean);
    grunt.config('copy', copy);
    grunt.config('requirejs', requirejs);

    // bundle task
    grunt.registerTask('taoeventlogbundle', ['clean:taoeventlogbundle', 'requirejs:taoeventlogbundle', 'copy:taoeventlogbundle']);
};
