module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoEventLog/views/';

    sass.taoeventlog = { };
    sass.taoeventlog.files = { };
    sass.taoeventlog.files[root + 'css/eventlog.css'] = root + 'scss/eventlog.scss';

    watch.taoeventlogsass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['sass:taoeventlog', 'notify:taoeventlogsass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taoeventlogsass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taoeventlogsass', ['sass:taoeventlog']);
};
