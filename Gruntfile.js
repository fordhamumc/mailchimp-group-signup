'use strict';

module.exports = function (grunt) {
    // Show elapsed time after tasks run
    require('time-grunt')(grunt);
    // Load all Grunt tasks
    require('load-grunt-tasks')(grunt);

    grunt.initConfig({
        paths: {
            dev: 'app',
            dist: 'dist',
            tmp: '.tmp'
        },

        php: {
            dev: {
                options: {
                    hostname: '127.0.0.1',
                    port: 8010,
                    base: '<%= paths.tmp %>', // Project root
                    keepalive: false,
                    open: false
                }
            }
        },
        browserSync: {
            dev: {
                bsFiles: {
                    src: [
                        '<%= paths.tmp %>/**/*.{html,php,js,css,jpg,png,gif,svg}'
                    ]
                },
                options: {
                    proxy: '<%= php.dev.options.hostname %>:<%= php.dev.options.port %>',
                    port: 8080,
                    open: true,
                    watchTask: true
                }
            }
        },
        watch: {
            files: {
                files: ['<%= paths.dev %>/**/*.{ini,json,php,html,png,jpg,jpeg,gif,svg}'],
                tasks: ['copy:dev']
            }
        },

        clean: {
            dev: ['<%= paths.tmp %>/*'],
            dist: ['<%= paths.tmp %>/*','<%= paths.dist %>/*']
        },
        copy: {
            dev: {
                files: [{
                    expand: true,
                    dot: true,
                    cwd: '<%= paths.dev %>',
                    src: [
                        'img/**/*.{png,jpg,jpeg,gif,svg}',
                        '**/*.{html,php,json}',
                        '.htaccess',
                        'data-qa.ini'
                    ],
                    dest: '<%= paths.tmp %>'
                }]
            },
            dist: {
                files: [{
                    expand: true,
                    dot: true,
                    cwd: '<%= paths.dev %>',
                    src: [
                        'img/**/*.{png,jpg,jpeg,gif,svg}',
                        '**/*.{html,php,json}',
                        '.htaccess',
                        'data.ini'
                    ],
                    dest: '<%= paths.dist %>'
                }]
            }
        }

    });

    grunt.registerTask('serve', [
        'clean:dev',
        'copy:dev',
        'php',
        'browserSync',
        'watch'
    ]);

    grunt.registerTask('build', [
        'clean:dist',
        'copy:dist'
    ]);


    grunt.registerTask('default', ['serve']);
};
