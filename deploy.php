<?php

namespace Deployer;

//require 'recipe/common.php';
require 'recipe/drupal8.php';

// Project name
// TODO: Change this
set('application', 'sitename.be');

// Project repository
set('repository', 'git@bitbucket.org:zapdevelopers/{{application}}.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

set('keep_releases', 2);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);
set('allow_anonymous_stats', false);


// Hosts
// TODO: Change this
host('sitename.staging.zapallpeople.com')
    ->stage('staging')
    ->user('zap')
    ->forwardAgent(true)
    ->set('deploy_path', '/var/www/{{application}}/staging');

// TODO: Change this
host('sitename.production.voltaweb.be')
    ->stage('prod')
    ->user('zap')
    ->forwardAgent(true)
    ->set('deploy_path', '/var/www/{{application}}/prod');

//Set drupal site. Change if you use different site
set('drupal_site', 'default');
//Drupal 8 shared dirs
set('shared_dirs', [
    'web/sites/{{drupal_site}}/files',
]);
//Drupal 8 shared files
set('shared_files', [
    'web/sites/{{drupal_site}}/settings.php',
    'web/sites/{{drupal_site}}/settings.staging.php',
    'web/sites/{{drupal_site}}/settings.prod.php',
    'web/sites/{{drupal_site}}/services.yml',
]);
//Drupal 8 Writable dirs
set('writable_dirs', [
    'web/sites/{{drupal_site}}/files',
]);


task('composer-drupal', function () {
    run('cd {{release_path}} && composer update');
});

// Tasks
desc('Composer');
task('composer', [
    'deploy:vendors',
]);

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    's3-backup',
    'drush:import',
    'drush:cr',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);


desc('Quick deploy');
task('quick-deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'drush:cr',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

task('s3-backup', function () {
    $backup = ask('Database backup(s3)?');
    if (in_array(strtolower($backup), ["y", "yes"])) {
        cd('{{release_path}}/web');
        run("echo 'backup started' && /usr/local/bin/drush-sql-backup-s3 -n {{application}}", ["tty" => true]);
    }
});

task('drush:import', function () {
    cd('{{release_path}}/web');
    run("/usr/local/bin/drush cim sync", ["tty" => true]);
});

task('drush:cr', function () {
    run("cd {{release_path}}/web && /usr/local/bin/drush cr");
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

