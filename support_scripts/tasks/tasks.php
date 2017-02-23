<?php

define( 'PROJECT_ROOT', realpath( dirname(__FILE__)  ) . DIRECTORY_SEPARATOR . '../../' );
require( PROJECT_ROOT . 'inc/Bootstrap.php' );

Bootstrap::start();

use Symfony\Component\Console\Application;

$app = new Application("Tasks for instantquote", "1.0");

$app->add( new \CommandLineTasks\CreateWorkspaceTask() ) ;
$app->add( new \CommandLineTasks\CreateTeamTask() ) ;
$app->add( new \CommandLineTasks\CreateTeamMembershipTask() ) ;
$app->add( new \CommandLineTasks\OwnerFeatures\AssignFeatureTask() ) ;
$app->add( new  \CommandLineTasks\Test\PrepareDatabaseTask() ) ;
$app->add( new \CommandLineTasks\DumpSchemaTask()) ;
$app->run();

