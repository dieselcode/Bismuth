<?php

include '../vendor/autoload.php';

try {
    $github = new \Bismuth\Endpoint\GitHub(
        new \Bismuth\Core\Auth\Basic('<username>', '<password>'),
        new \Bismuth\Tools\HTTPCache([
            'cache_ext'     => '.bismuth',
            'cache_max_age' => 3600,
            'cache_path'    => dirname(__FILE__) . '/cache/'
        ])
    );

    // update our repo with the patch data
    $api = $github
            ->user()                    // user object
            ->current()                 // current user (logged in)
            ->repos()                   // repository object
            ->list(array(               // repository list (with options)
                'sort' => 'full_name',
                'direction' => 'asc'
            ));

    var_dump($api[0]->name);

} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>