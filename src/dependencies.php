  <?php
// DIC configuration

require_once( 'database.php' );
//require_once( '../controllers/areas.php' );
//require_once( '../controllers/mobs.php' );
//require_once( '../controllers/objects.php' );
//require_once( '../controllers/rooms.php' );
require_once( '../controllers/users.php' );


$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};


$container['db'] = function( $c ) {
  $db_config = $c->get( 'settings' )['database'];

  return new Database(
    "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']}",
    $db_config['user'],
    $db_config['pass']
  );
};
/*
$container['market'] = function ( $c ) {
  return new App\Controller\marketController( $c );
};

$container['station'] = function ( $c ) {
  return new App\Controller\stationController( $c );
};

$container['copies'] = function ( $c ) {
  return new App\Controller\copyController( $c );
};

$container['listeners'] = function ( $c ) {
  return new App\Controller\listenersController( $c );
};

$container['contests'] = function ( $c ) {
  return new App\Controller\contestsController( $c );
};
*/
$container['users'] = function ( $c ) {
  return new App\Controller\usersController( $c );
};

$container['jwt'] = function ( $c ) {
  return new StdClass;
};