/* jshint node:true */
'use strict';

module.exports = function (grunt) {

  // auto load grunt tasks
  require('load-grunt-tasks')(grunt);

  var pluginConfig = {

    // gets the package vars
    pkg: grunt.file.readJSON('package.json'),

    // plugin directories
    dirs: {
      admin: {
        js: 'admin/js',
        ts: 'admin/src',
        css: 'admin/css',
        sass: 'admin/sass',
        images: 'admin/images',
        fonts: 'admin/fonts'
      },
      public: {
        js: 'public/js',
        ts: 'public/src',
        css: 'public/css',
        sass: 'public/sass',
        images: 'public/images',
        fonts: 'public/fonts'
      }
    },

    ts: {
      // use to override the default options, See: http://gruntjs.com/configuring-tasks#options
      // these are the default options to the typescript compiler for grunt-ts:
      // see `tsc --help` for a list of supported options.
      options: {
        compile: true,                 // perform compilation. [true (default) | false]
        comments: false,               // same as !removeComments. [true | false (default)]
        target: 'es5',                 // target javascript language. [es3 | es5 (grunt-ts default) | es6]
        module: 'amd',                 // target javascript module style. [amd (default) | commonjs]
        sourceMap: true,               // generate a source map for every output js file. [true (default) | false]
        // sourceRoot: '',                // where to locate TypeScript files. [(default) '' == source ts location]
        // mapRoot: '',                   // where to locate .map.js files. [(default) '' == generated js location.]
        declaration: false,            // generate a declaration .d.ts file for every output js file. [true | false (default)]
        // htmlModuleTemplate: 'POF.Module.<%= filename %>',    // Template for module name for generated ts from html files [(default) '<%= filename %>']
        // htmlVarTemplate: '<%= ext %>',                      // Template for variable name used in generated ts from html files [(default) '<%= ext %>]
        // Both html templates accept the ext and filename parameters.
        noImplicitAny: true,          // set to true to pass --noImplicitAny to the compiler. [true | false (default)]
        // fast: 'watch'                  // see https://github.com/TypeStrong/grunt-ts/blob/master/docs/fast.md ["watch" (default) | "always" | "never"]
        /* ,compiler: './node_modules/grunt-ts/customcompiler/tsc'  */ //will use the specified compiler.
        lib: ['es2015','dom']
      },
      // a particular target
      admin: {
        src: ['<%= dirs.admin.ts %>/index.ts','<%= dirs.admin.ts %>/reference.ts'],          // The source typescript files, http://gruntjs.com/configuring-tasks#files
        // reference: '<%= dirs.admin.ts %>/reference.ts', // If specified, generate this file that you can use for your reference management
        outDir: '<%= dirs.admin.js %>',             // If specified, generate an out.js file which is the merged js file

        // watch: '<%= dirs.admin.ts %>',                  // If specified, watches this directory for changes, and re-runs the current target
        options: {
          rootDir: '<% dirs.admin.src %>'
        }
      },
      public: {
        src: ['<%= dirs.public.ts %>/index.ts','<%= dirs.public.ts %>/reference.ts'],          // The source typescript files, http://gruntjs.com/configuring-tasks#files
        // reference: '<%= dirs.public.ts %>/reference.ts', // If specified, generate this file that you can use for your reference management
        outDir: '<%= dirs.public.js %>',             // If specified, generate an out.js file which is the merged js file
        // watch: '<%= dirs.public.ts %>/public.js',                  // If specified, watches this directory for changes, and re-runs the current target
        options: {
          rootDir: '<%= dirs.public.src %>'
        }
      }
    },


    // uglify to concat and minify
    uglify: {
      options: {
        verbose: true
      },
      dist: {
        files: {
          '<%= dirs.admin.js %>/admin.min.js': ['<%= dirs.admin.js %>/admin.js'],
          '<%= dirs.public.js %>/public.min.js': ['<%= dirs.public.js %>/public.js']
        }
      }
    },

    sass: {
      admin: {
        src: '<%= dirs.admin.sass %>/admin.scss',
        dest: '<%= dirs.admin.css %>/admin.css'
      },
      public: {
        src: '<%= dirs.public.sass %>/public.scss',
        dest: '<%= dirs.public.css %>/public.css'
      }
    },

    // watch for changes and trigger compass, compile typescript, and uglify
    watch: {
      sass: {
        files: [
          '<%= dirs.admin.sass %>/**',
          '<%= dirs.public.sass %>/**'
        ],
        tasks: ['sass']
      },
      js: {
        files: [
          '<%= dirs.admin.ts %>/**', '!<%= dirs.admin.ts %>/reference.ts',
          '<%= dirs.public.ts %>/**', '!<%= dirs.public.ts %>/reference.ts'
        ],
        tasks: ['uglify']
      }
    },

    // image optimization
    imagemin: {
      dist: {
        options: {
          optimizationLevel: 7,
          progressive: true
        },
        files: [
          {
            expand: true,
            cwd: '<%= dirs.admin.images %>/',
            src: '**/*.{png,jpg,gif}',
            dest: '<%= dirs.admin.images %>/'
          },
          {
            expand: true,
            cwd: '<%= dirs.public.images %>/',
            src: '**/*.{png,jpg,gif}',
            dest: '<%= dirs.public.images %>/'
          },
          {
            expand: true,
            cwd: './',
            src: 'screenshot-*.png',
            dest: './'
          }
        ]
      }
    },

    // rsync commands used to take the files to svn repository
    rsync: {
      options: {
        args: ['--verbose'],
        exclude: '<%= svn_settings.exclude %>',
        syncDest: true,
        recursive: true
      },
      tag: {
        options: {
          src: './',
          dest: '<%= svn_settings.tag %>'
        }
      },
      trunk: {
        options: {
          src: './',
          dest: '<%= svn_settings.trunk %>'
        }
      }
    },

    // shell command to commit the new version of the plugin
    shell: {
      // Remove delete files.
      svn_remove: {
        command: 'svn st | grep \'^!\' | awk \'{print $2}\' | xargs svn --force delete',
        options: {
          stdout: true,
          stderr: true,
          execOptions: {
            cwd: '<%= svn_settings.path %>'
          }
        }
      },
      // Add new files.
      svn_add: {
        command: 'svn add --force * --auto-props --parents --depth infinity -q',
        options: {
          stdout: true,
          stderr: true,
          execOptions: {
            cwd: '<%= svn_settings.path %>'
          }
        }
      },
      // Commit the changes.
      svn_commit: {
        command: 'svn commit -m "updated the plugin version to <%= pkg.version %>"',
        options: {
          stdout: true,
          stderr: true,
          execOptions: {
            cwd: '<%= svn_settings.path %>'
          }
        }
      }
    }
  };

  // initialize grunt config
  // --------------------------
  grunt.initConfig(pluginConfig);

  // register tasks
  // --------------------------

  // default task
  grunt.registerTask('default', [
    'sass',
    'uglify'
  ]);

  // deploy task
  grunt.registerTask('deploy', [
    'default',
    // 'rsync:tag',
    // 'rsync:trunk',
    // 'shell:svn_remove',
    // 'shell:svn_add',
    // 'shell:svn_commit'
  ]);
};